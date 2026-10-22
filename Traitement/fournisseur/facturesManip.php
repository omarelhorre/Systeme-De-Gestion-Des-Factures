<?php
require_once __DIR__.'/../../BD/facturesFournisseur.php';

function texteFacture($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function erreurFacture($code, $message)
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}

function envoyerPdfFacture($facture, $telecharger)
{
    $racine = realpath(__DIR__.'/../..');
    $chemin = $facture['cheminGenere'];
    if (preg_match('~^[a-z][a-z0-9+.-]*:|^[/\\\\]~i', $chemin)) {
        erreurFacture(404, 'PDF indisponible : chemin relatif au projet requis.');
    }
    $fichier = realpath($racine.DIRECTORY_SEPARATOR.$chemin);
    $prefixe = $racine.DIRECTORY_SEPARATOR;
    if (!$fichier || strncmp($fichier, $prefixe, strlen($prefixe)) !== 0
        || !is_file($fichier) || !is_readable($fichier)
        || strtolower(pathinfo($fichier, PATHINFO_EXTENSION)) !== 'pdf') {
        erreurFacture(404, 'PDF indisponible.');
    }
    $handle = fopen($fichier, 'rb');
    if (!$handle || fread($handle, 5) !== '%PDF-') {
        if ($handle) fclose($handle);
        erreurFacture(404, 'PDF invalide.');
    }
    rewind($handle);
    session_write_close();
    header('Content-Type: application/pdf');
    header('Content-Disposition: '.($telecharger ? 'attachment' : 'inline').'; filename="facture-'.(int)$facture['id'].'.pdf"');
    header('Content-Length: '.fstat($handle)['size']);
    fpassthru($handle);
    fclose($handle);
    exit;
}

function afficherFacturesFournisseur()
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    $fournisseur = filter_var($_SESSION['user']['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$fournisseur) erreurFacture(401, 'Connectez-vous avec votre compte fournisseur via la connexion habituelle.');
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db = connexion();
        mysqli_set_charset($db, 'utf8mb4');
        if (!verifierFournisseurFactures($db, $fournisseur)) erreurFacture(403, 'Accès réservé aux fournisseurs.');
        $action = $_GET['action'] ?? 'liste';
        if (!is_string($action) || !in_array($action, ['liste', 'detail', 'payer', 'pdf'], true)) erreurFacture(404, 'Page introuvable.');
        $methode = $_SERVER['REQUEST_METHOD'];
        if (($action === 'payer' && $methode !== 'POST') || ($action !== 'payer' && $methode !== 'GET')) {
            header('Allow: '.($action === 'payer' ? 'POST' : 'GET'));
            erreurFacture(405, 'Méthode non autorisée.');
        }
        if (empty($_SESSION['csrf_factures'])) $_SESSION['csrf_factures'] = bin2hex(random_bytes(32));
        $etat = $_GET['etat'] ?? '';
        if (!is_string($etat) || !in_array($etat, ['', 'Payée', 'Impayée'], true)) erreurFacture(400, 'Filtre invalide.');
        $facture = null;
        if ($action !== 'liste') {
            $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id) erreurFacture(400, 'Numéro de facture invalide.');
            $resultat = selectFacturesFournisseur($db, $fournisseur, '', $id);
            if (!$resultat) erreurFacture(404, 'Facture introuvable.');
            $facture = $resultat[0];
        }
        if ($action === 'payer') {
            if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf_factures'], $_POST['csrf'])) erreurFacture(403, 'Confirmation expirée. Rechargez la facture.');
            if (($_POST['confirmation'] ?? '') !== 'oui') erreurFacture(400, 'Confirmez la réception du paiement en espèces.');
            $_SESSION['message_factures'] = payerFactureFournisseur($db, $fournisseur, $id)
                ? 'Paiement en espèces enregistré.' : 'Aucun changement : paiement déjà enregistré ou état incompatible.';
            header('Location: ?action=detail&id='.$id, true, 303);
            exit;
        }
        if ($action === 'pdf') envoyerPdfFacture($facture, ($_GET['telecharger'] ?? '') === '1');
        $data = $action === 'liste' ? selectFacturesFournisseur($db, $fournisseur, $etat) : [];
        $message = $_SESSION['message_factures'] ?? '';
        unset($_SESSION['message_factures']);
        include __DIR__.'/../../IHM/fournisseur/facturesList.php';
        mysqli_close($db);
    } catch (mysqli_sql_exception $e) {
        error_log('Factures fournisseur : '.$e->getMessage());
        erreurFacture(503, 'La base de données est indisponible. Réessayez plus tard.');
    }
}
