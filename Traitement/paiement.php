<?php
// Paiement en ligne d'une facture par carte, via la passerelle CMI (3D Pay Hosting).
//
// Le parcours :
//  1. paiement/payer/{facture}    : (POST, client connecté) prépare le formulaire signé et l'envoie à CMI ;
//  2. le client paie sur la page de CMI ;
//  3. paiement/ok/{facture}       : CMI renvoie le navigateur ici avec le résultat signé.
//     paiement/callback/{facture} : CMI prévient aussi le serveur directement (quand le site est en ligne).
//     Si la signature et le montant sont bons, la facture passe à "Payée" : le fournisseur n'a rien à saisir.
//  4. paiement/recu/{facture} ou paiement/refuse/{facture} : message au client, retour à ses relevés.
//
// Réglages (.env, transmis par docker-compose.yml) :
//  cmi_client_id, cmi_store_key : identifiants du marchand de test fournis par CMI ;
//  cmi_passerelle               : adresse de la passerelle (par défaut celle de test de CMI).
// Sans identifiants CMI, en mode démo (demo=1), un simulateur local remplace la page de CMI,
// avec exactement les mêmes échanges signés. Hors démo et sans identifiants : pas de bouton.

require_once ROOT.'BD/paiement.php';

const CMI_PASSERELLE_TEST = 'https://testpayment.cmi.co.ma/fim/est3Dgate';
const CMI_CLE_SIMULATEUR = 'simulateur-fatourti';

// 'cmi', 'simulateur' ou '' (paiement en ligne indisponible)
function paiementMode(): string
{
    if (getenv('CMI_CLIENT_ID') && getenv('CMI_STORE_KEY')) return 'cmi';
    return getenv('DEMO') === '1' ? 'simulateur' : '';
}

function paiementCle(): string
{
    return paiementMode() === 'cmi' ? (string)getenv('CMI_STORE_KEY') : CMI_CLE_SIMULATEUR;
}

// Signature CMI "ver3" : les valeurs triées par nom de champ (sans hash ni encoding),
// "\" et "|" protégés, séparées par "|", suivies de la clé du magasin ; SHA-512 puis base64.
function paiementSignature(array $champs, string $cle): string
{
    $noms = array_keys($champs);
    natcasesort($noms);
    $texte = '';
    foreach ($noms as $nom) {
        if (in_array(strtolower($nom), array('hash', 'encoding'), true)) continue;
        $valeur = trim(html_entity_decode((string)$champs[$nom], ENT_QUOTES, 'UTF-8'));
        $texte .= str_replace('|', '\\|', str_replace('\\', '\\\\', $valeur)).'|';
    }
    $texte .= str_replace('|', '\\|', str_replace('\\', '\\\\', $cle));
    return base64_encode(pack('H*', hash('sha512', $texte)));
}

// Une page qui envoie tout de suite un formulaire POST (vers CMI ou vers notre site)
function paiementEnvoyer(string $adresse, array $champs): void
{
    $e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>Paiement · Fatourti</title></head>'
        .'<body onload="document.forms[0].submit()"><form method="post" action="'.$e($adresse).'">';
    foreach ($champs as $nom => $valeur) echo '<input type="hidden" name="'.$e($nom).'" value="'.$e($valeur).'">';
    echo '<noscript><button type="submit">Continuer vers le paiement</button></noscript></form></body></html>';
    exit;
}

// Le résultat renvoyé par CMI est-il authentique et accepté pour cette facture et ce montant ?
function paiementAccepte(array $retour, array $facture): bool
{
    if (!isset($retour['HASH']) || !hash_equals(paiementSignature($retour, paiementCle()), (string)$retour['HASH'])) return false;
    return ($retour['ProcReturnCode'] ?? '') === '00'
        && ($retour['Response'] ?? '') === 'Approved'
        && strpos((string)($retour['oid'] ?? ''), 'FAC'.$facture['id'].'-') === 0
        && abs((float)($retour['amount'] ?? 0) - round((float)$facture['prixTTC'], 2)) < 0.005;
}

// Paiement accepté : facture payée + notifications au client et au fournisseur
function paiementValider(array $facture, array $retour): void
{
    $db = connexion();
    $reference = substr((string)($retour['TransId'] ?? $retour['oid']), 0, 64);
    if (enregistrerPaiementEnLigne($db, (int)$facture['id'], $reference)) {
        $montant = number_format((float)$facture['prixTTC'], 2, ',', ' ').' DH';
        notifier($facture['idConsommateur'], 'facture_payee', 'Paiement reçu pour la facture n° '.$facture['id'],
            'Votre paiement en ligne de '.$montant.' est enregistré.', 'declaration/all', array('idObjet' => $facture['id'], 'email' => true));
        notifier($facture['idFournisseur'], 'facture_payee', 'Facture n° '.$facture['id'].' payée en ligne',
            $facture['prenom'].' '.$facture['nom'].' a payé '.$montant.' par carte.', 'facture/detail/'.$facture['id'], array('idObjet' => $facture['id']));
    }
}

function paiement($action, $id)
{
    if (!ctype_digit((string)$id) || (int)$id <= 0) introuvable();
    $id = (int)$id;
    $mode = paiementMode();
    if ($mode === '') introuvable();

    // 1. Le client clique sur "Payer en ligne"
    if ($action === 'payer') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user'])) introuvable();
        if (!hash_equals($_SESSION['csrf_consommation'] ?? '', (string)($_POST['csrf'] ?? ''))) introuvable();
        $facture = factureAPayer(connexion(), $id, (int)$_SESSION['user']['id']);
        if (!$facture) {
            $_SESSION['message_consommation'] = 'Cette facture est déjà payée ou ne peut pas être payée en ligne.';
            header('Location: '.link.'declaration/all', true, 303);
            exit;
        }
        $champs = array(
            'clientid'      => $mode === 'cmi' ? getenv('CMI_CLIENT_ID') : 'simulateur',
            'amount'        => number_format((float)$facture['prixTTC'], 2, '.', ''),
            'currency'      => '504',                                  // dirham marocain
            'oid'           => 'FAC'.$id.'-'.time(),                   // numéro de commande unique par tentative
            'okUrl'         => link.'paiement/ok/'.$id,
            'failUrl'       => link.'paiement/echec/'.$id,
            'callbackUrl'   => link.'paiement/callback/'.$id,
            'shopurl'       => link.'declaration/all',
            'TranType'      => 'PreAuth',
            'storetype'     => '3D_PAY_HOSTING',
            'hashAlgorithm' => 'ver3',
            'lang'          => 'fr',
            'rnd'           => bin2hex(random_bytes(10)),
            'encoding'      => 'UTF-8',
            'BillToName'    => $facture['prenom'].' '.$facture['nom'],
            'email'         => $facture['email'],
            'refreshtime'   => '5',
        );
        $champs['HASH'] = paiementSignature($champs, paiementCle());
        $passerelle = $mode === 'cmi' ? (getenv('CMI_PASSERELLE') ?: CMI_PASSERELLE_TEST) : link.'paiement/simulateur/'.$id;
        paiementEnvoyer($passerelle, $champs);
    }

    // Simulateur (démo sans identifiants CMI) : affiche la commande, puis renvoie un résultat signé comme CMI
    if ($action === 'simulateur') {
        if ($mode !== 'simulateur' || $_SERVER['REQUEST_METHOD'] !== 'POST') introuvable();
        $commande = $_POST;
        unset($commande['decision']);   // le choix "Accepter / Refuser" ne fait pas partie de la commande signée
        if (!isset($commande['HASH']) || !hash_equals(paiementSignature($commande, paiementCle()), (string)$commande['HASH'])) introuvable();
        if (isset($_POST['decision'])) {
            $accepte = $_POST['decision'] === 'accepter';
            $retour = $commande;
            $retour['ProcReturnCode'] = $accepte ? '00' : '05';
            $retour['Response'] = $accepte ? 'Approved' : 'Declined';
            $retour['TransId'] = 'SIM'.strtoupper(bin2hex(random_bytes(6)));
            unset($retour['HASH']);
            $retour['HASH'] = paiementSignature($retour, paiementCle());
            paiementEnvoyer($accepte ? $commande['okUrl'] : $commande['failUrl'], $retour);
        }
        include ROOT.'IHM/consommateur/simulateurPaiement.php';
        exit;
    }

    // 3. Retour de CMI. Pas de session ici (le navigateur revient d'un autre site) :
    //    seule la signature fait foi, puis on redirige vers une page qui, elle, a la session.
    if (in_array($action, array('ok', 'echec', 'callback'), true)) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') introuvable();
        $facture = factureAPayer(connexion(), $id);
        $accepte = $facture && paiementAccepte($_POST, $facture);
        if ($accepte) paiementValider($facture, $_POST);
        if ($action === 'callback') {
            // réponse attendue par CMI
            echo $accepte ? 'ACTION=POSTAUTH' : 'APPROVED';
            exit;
        }
        // déjà payée (par le callback juste avant) : c'est aussi un succès
        $payee = $accepte || !$facture;
        header('Location: '.link.'paiement/'.($payee && $action === 'ok' ? 'recu' : 'refuse').'/'.$id, true, 303);
        exit;
    }

    // 4. Message au client
    if ($action === 'recu' || $action === 'refuse') {
        $_SESSION['message_consommation'] = $action === 'recu'
            ? 'Paiement accepté : la facture n° '.$id.' est payée.'
            : 'Paiement non abouti pour la facture n° '.$id.'. Aucun montant n\'a été débité.';
        header('Location: '.link.'declaration/all', true, 303);
        exit;
    }

    introuvable();
}
