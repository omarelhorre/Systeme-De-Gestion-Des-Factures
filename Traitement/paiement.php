<?php

require_once ROOT.'BD/paiement.php';

const CMI_PASSERELLE_TEST = 'https://testpayment.cmi.co.ma/fim/est3Dgate';
const STRIPE_API = 'https://api.stripe.com';

function paiementPrestataire(): string
{
    return getenv('PAIEMENT') === 'cmi' ? 'cmi' : 'stripe';
}

function paiementActif(): bool
{
    if (paiementPrestataire() === 'stripe') return (bool)getenv('STRIPE_SECRET_KEY');
    return (getenv('CMI_CLIENT_ID') && getenv('CMI_STORE_KEY')) || getenv('DEMO') === '1';
}

function stripeAppel(string $methode, string $chemin, array $parametres = array()): array
{
    $base = getenv('STRIPE_API_BASE') ?: STRIPE_API;
    $requete = curl_init($base.$chemin.($methode === 'GET' && $parametres ? '?'.http_build_query($parametres) : ''));
    curl_setopt_array($requete, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => getenv('STRIPE_SECRET_KEY').':',
        CURLOPT_TIMEOUT        => 20,
    ));
    if ($methode === 'POST') {
        curl_setopt($requete, CURLOPT_POST, true);
        curl_setopt($requete, CURLOPT_POSTFIELDS, http_build_query($parametres));
    }
    $reponse = curl_exec($requete);
    $code = (int)curl_getinfo($requete, CURLINFO_HTTP_CODE);
    curl_close($requete);
    $donnees = json_decode((string)$reponse, true);
    if ($code !== 200 || !is_array($donnees)) {
        error_log('Fatourti Stripe : '.$code.' '.substr((string)$reponse, 0, 300));
        throw new RuntimeException($donnees['error']['message'] ?? 'Stripe ne répond pas.');
    }
    return $donnees;
}

function stripeCentimes($montant): int
{
    return (int)round((float)$montant * 100);
}

function stripeCreerPaiement(array $facture): array
{
    $id = (int)$facture['id'];
    return stripeAppel('POST', '/v1/checkout/sessions', array(
        'mode'                 => 'payment',
        'locale'               => 'fr',
        'customer_email'       => $facture['email'],
        'client_reference_id'  => (string)$id,
        'metadata'             => array('idFacture' => (string)$id),
        'line_items'           => array(array(
            'quantity'   => 1,
            'price_data' => array(
                'currency'     => getenv('STRIPE_DEVISE') ?: 'mad',
                'unit_amount'  => stripeCentimes($facture['prixTTC']),
                'product_data' => array('name' => 'Facture n° '.$id.' · eau et électricité'),
            ),
        )),
        'success_url'          => link.'paiement/retour/'.$id,
        'cancel_url'           => link.'paiement/annule/'.$id,
    ));
}

function stripePaye(array $session, array $facture): bool
{
    return ($session['payment_status'] ?? '') === 'paid'
        && (string)($session['metadata']['idFacture'] ?? '') === (string)$facture['id']
        && (int)($session['amount_total'] ?? -1) === stripeCentimes($facture['prixTTC'])
        && strtolower((string)($session['currency'] ?? '')) === strtolower(getenv('STRIPE_DEVISE') ?: 'mad');
}

function paiementPasserelle(): string
{
    return getenv('CMI_PASSERELLE') ?: CMI_PASSERELLE_TEST;
}

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

function paiementChamps($idFacture, $montant, $nom, $email): array
{
    $idFacture = (int)$idFacture;
    $champs = array(
        'clientid'      => (string)getenv('CMI_CLIENT_ID'),
        'amount'        => number_format((float)$montant, 2, '.', ''),
        'currency'      => '504',
        'oid'           => 'FAC'.$idFacture.'-'.time(),
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

function paiementAccepte(array $retour, array $facture): bool
{
    if (!isset($retour['HASH']) || !hash_equals(paiementSignature($retour), (string)$retour['HASH'])) return false;
    return ($retour['ProcReturnCode'] ?? '') === '00'
        && ($retour['Response'] ?? '') === 'Approved'
        && strpos((string)($retour['oid'] ?? ''), 'FAC'.$facture['id'].'-') === 0
        && abs((float)($retour['amount'] ?? 0) - round((float)$facture['prixTTC'], 2)) < 0.005;
}

function paiementValider(array $facture, string $reference, string $moyen): void
{
    $db = connexion();
    if (enregistrerPaiementEnLigne($db, (int)$facture['id'], substr($reference, 0, 64))) {
        $montant = number_format((float)$facture['prixTTC'], 2, ',', ' ').' DH';
        notifier($facture['idConsommateur'], 'facture_payee', 'Paiement reçu pour la facture n° '.$facture['id'],
            'Votre paiement en ligne de '.$montant.' est enregistré.', 'declaration/all', array('idObjet' => $facture['id'], 'email' => true));
        notifier($facture['idFournisseur'], 'facture_payee', 'Facture n° '.$facture['id'].' payée en ligne',
            $facture['prenom'].' '.$facture['nom'].' a payé '.$montant.' par carte ('.$moyen.').', 'facture/detail/'.$facture['id'], array('idObjet' => $facture['id']));
    }
}

function paiementMessage(string $texte): void
{
    $_SESSION['message_consommation'] = $texte;
    header('Location: '.link.'declaration/all', true, 303);
    exit;
}

function paiement($action, $id)
{
    if (!ctype_digit((string)$id) || (int)$id <= 0) introuvable();
    $id = (int)$id;
    $paye = 'Paiement accepté : la facture n° '.$id.' est payée.';
    $nonPaye = 'Paiement non abouti pour la facture n° '.$id.'. Aucun montant n\'a été débité.';

    if ($action === 'payer') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user']) || paiementPrestataire() !== 'stripe' || !paiementActif()) introuvable();
        if (!hash_equals($_SESSION['csrf_consommation'] ?? '', (string)($_POST['csrf'] ?? ''))) introuvable();
        $facture = factureAPayer(connexion(), $id, (int)$_SESSION['user']['id']);
        if (!$facture) paiementMessage('Cette facture est déjà payée ou ne peut pas être payée en ligne.');
        try {
            $session = stripeCreerPaiement($facture);
        } catch (RuntimeException $e) {
            paiementMessage('Le paiement en ligne est indisponible pour le moment : '.$e->getMessage());
        }
        $_SESSION['paiements_stripe'][$id] = $session['id'];
        header('Location: '.$session['url'], true, 303);
        exit;
    }

    if ($action === 'retour') {
        if (!isset($_SESSION['user'])) introuvable();
        $idSession = $_SESSION['paiements_stripe'][$id] ?? null;
        $facture = factureAPayer(connexion(), $id, (int)$_SESSION['user']['id']);
        if (!$facture) paiementMessage($paye);
        if (!$idSession) paiementMessage($nonPaye);
        try {
            $session = stripeAppel('GET', '/v1/checkout/sessions/'.rawurlencode($idSession));
        } catch (RuntimeException $e) {
            paiementMessage('Vérification du paiement impossible pour le moment. Réessayez dans un instant.');
        }
        if (!stripePaye($session, $facture)) paiementMessage($nonPaye);
        unset($_SESSION['paiements_stripe'][$id]);
        paiementValider($facture, (string)($session['payment_intent'] ?? $session['id']), 'Stripe');
        paiementMessage($paye);
    }

    if ($action === 'annule') {
        unset($_SESSION['paiements_stripe'][$id]);
        paiementMessage($nonPaye);
    }

    if (in_array($action, array('ok', 'echec', 'callback'), true)) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') introuvable();
        $facture = factureAPayer(connexion(), $id);
        $accepte = $facture && paiementAccepte($_POST, $facture);
        if ($accepte) paiementValider($facture, (string)($_POST['TransId'] ?? $_POST['oid']), 'CMI');
        if ($action === 'callback') {
            echo $accepte ? 'ACTION=POSTAUTH' : 'APPROVED';
            exit;
        }
        $reussi = ($accepte || !$facture) && $action === 'ok';
        header('Location: '.link.'paiement/'.($reussi ? 'recu' : 'refuse').'/'.$id, true, 303);
        exit;
    }
    if ($action === 'recu') paiementMessage($paye);
    if ($action === 'refuse') paiementMessage($nonPaye);

    introuvable();
}
