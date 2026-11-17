<?php
// Paiement en ligne d'une facture par carte, via la passerelle de test CMI (3D Pay Hosting).
//
// Le parcours :
//  1. Le bouton "Payer" est un formulaire déjà signé (paiementChamps) : un clic envoie le client
//     directement sur la page de paiement de CMI, sans page intermédiaire ;
//  2. le client paie sur la page de CMI ;
//  3. paiement/ok/{facture}       : CMI renvoie le navigateur ici avec le résultat signé ;
//     paiement/callback/{facture} : CMI prévient aussi le serveur directement (quand le site est en ligne).
//     Si la signature et le montant sont bons, la facture passe à "Payée" : le fournisseur n'a rien à saisir ;
//  4. paiement/recu/{facture} ou paiement/refuse/{facture} : message au client, retour à ses relevés.
//
// Réglages (.env, transmis par docker-compose.yml) :
//  cmi_client_id, cmi_store_key : identifiants du marchand de test fournis par CMI ;
//  cmi_passerelle               : adresse de la passerelle (par défaut celle de test de CMI).
// Le bouton s'affiche si ces identifiants sont réglés, ou en mode démo (demo=1).

require_once ROOT.'BD/paiement.php';

const CMI_PASSERELLE_TEST = 'https://testpayment.cmi.co.ma/fim/est3Dgate';

function paiementActif(): bool
{
    return (getenv('CMI_CLIENT_ID') && getenv('CMI_STORE_KEY')) || getenv('DEMO') === '1';
}

function paiementPasserelle(): string
{
    return getenv('CMI_PASSERELLE') ?: CMI_PASSERELLE_TEST;
}

// Signature CMI "ver3" : les valeurs triées par nom de champ (sans hash ni encoding),
// "\" et "|" protégés, séparées par "|", suivies de la clé du magasin ; SHA-512 puis base64.
function paiementSignature(array $champs): string
{
    $noms = array_keys($champs);
    natcasesort($noms);
    $texte = '';
    foreach ($noms as $nom) {
        if (in_array(strtolower($nom), array('hash', 'encoding'), true)) continue;
        $valeur = trim(html_entity_decode((string)$champs[$nom], ENT_QUOTES, 'UTF-8'));
        $texte .= str_replace('|', '\\|', str_replace('\\', '\\\\', $valeur)).'|';
    }
    $texte .= str_replace('|', '\\|', str_replace('\\', '\\\\', (string)getenv('CMI_STORE_KEY')));
    return base64_encode(pack('H*', hash('sha512', $texte)));
}

// Les champs signés envoyés à CMI pour payer une facture
function paiementChamps($idFacture, $montant, $nom, $email): array
{
    $idFacture = (int)$idFacture;
    $champs = array(
        'clientid'      => (string)getenv('CMI_CLIENT_ID'),
        'amount'        => number_format((float)$montant, 2, '.', ''),
        'currency'      => '504',                                       // dirham marocain
        'oid'           => 'FAC'.$idFacture.'-'.time(),                 // numéro de commande unique par tentative
        'okUrl'         => link.'paiement/ok/'.$idFacture,
        'failUrl'       => link.'paiement/echec/'.$idFacture,
        'callbackUrl'   => link.'paiement/callback/'.$idFacture,
        'shopurl'       => link.'declaration/all',
        'TranType'      => 'PreAuth',
        'storetype'     => '3D_PAY_HOSTING',
        'hashAlgorithm' => 'ver3',
        'lang'          => 'fr',
        'rnd'           => bin2hex(random_bytes(10)),
        'encoding'      => 'UTF-8',
        'BillToName'    => (string)$nom,
        'email'         => (string)$email,
        'refreshtime'   => '5',
    );
    $champs['HASH'] = paiementSignature($champs);
    return $champs;
}

// Le résultat renvoyé par CMI est-il authentique et accepté pour cette facture et ce montant ?
function paiementAccepte(array $retour, array $facture): bool
{
    if (!isset($retour['HASH']) || !hash_equals(paiementSignature($retour), (string)$retour['HASH'])) return false;
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

    // 3. Retour de CMI. Pas de session ici (le navigateur revient d'un autre site) :
    //    seule la signature fait foi, puis on redirige vers une page qui, elle, a la session.
    if (in_array($action, array('ok', 'echec', 'callback'), true)) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') introuvable();
        $facture = factureAPayer(connexion(), $id);
        $accepte = $facture && paiementAccepte($_POST, $facture);
        if ($accepte) paiementValider($facture, $_POST);
        if ($action === 'callback') {
            echo $accepte ? 'ACTION=POSTAUTH' : 'APPROVED';   // réponse attendue par CMI
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
