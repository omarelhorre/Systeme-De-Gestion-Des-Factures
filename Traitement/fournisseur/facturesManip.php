<?php
include_once(ROOT.'BD/facture.php');
function gestionFactures($fournisseur) {
    if (empty($_SESSION['user']['id'])) {
        header('Location:'.link.'utilisateur/authentification');
        exit;
    }
    $userId = (int) $_SESSION['user']['id'];
    $role = $fournisseur ? checkFournisseur($userId) : checkConsommateur($userId);
    if (!$role) { http_response_code(403); echo 'Accès refusé.'; return; }
    $clientId = $fournisseur ? (int) filter_var($_GET['client'] ?? 0, FILTER_VALIDATE_INT) : 0;
    if (empty($_SESSION['factures_csrf'])) $_SESSION['factures_csrf'] = bin2hex(random_bytes(32));
    $message = $_SESSION['factures_message'] ?? '';
    unset($_SESSION['factures_message']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$fournisseur || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['factures_csrf'], $_POST['csrf'])) {
            http_response_code(403); echo 'Action non autorisée.'; return;
        }
        $id = filter_var($_POST['facture'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id || ($_POST['confirmation'] ?? '') !== '1') {
            $message = 'Confirmez la réception du paiement en espèces.';
        } else {
            $message = facturePayerEspeces($id, $userId) ? 'Paiement en espèces enregistré.' : 'Aucun paiement enregistré : facture déjà payée, indisponible ou non payable.';
        }
        $_SESSION['factures_message'] = $message;
        header('Location:'.link.'fournisseur/factures'.($clientId > 0 ? '?client='.$clientId : ''));
        exit;
    }
    $clients = [];
    if ($fournisseur) {
        global $link;
        $stmt = mysqli_prepare($link, 'SELECT c.id, u.nom, u.prenom FROM consommateur c JOIN utilisateur u ON u.id=c.id WHERE c.idFournisseur=? ORDER BY u.nom, u.prenom');
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $clients = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    }
    $factures = facturesListe($userId, $fournisseur, $clientId);
    include(ROOT.'IHM/factures.php');
}
