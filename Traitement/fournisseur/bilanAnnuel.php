<?php
require_once ROOT.'BD/annuelle.php';
require_once ROOT.'Traitement/agent/calculAnnuel.php';

function afficherBilanAnnuel($anneeDemandee = null)
{
    $fournisseur = (int)($_SESSION['user']['id'] ?? 0);
    if (!$fournisseur || !checkFournisseur($fournisseur)) { http_response_code(403); echo 'Accès réservé aux fournisseurs.'; return; }
    $anneeActuelle = (int)(new DateTimeImmutable('now', new DateTimeZone('Africa/Casablanca')))->format('Y');
    $annee = $anneeDemandee === null ? $anneeActuelle : (int)$anneeDemandee;
    if ($annee < 2000 || $annee > $anneeActuelle) { http_response_code(404); echo 'Année introuvable.'; return; }
    $db = connexion();
    mysqli_set_charset($db, 'utf8mb4');
    $data = bilanAnnuelFournisseur($db, $fournisseur, $annee);
    $declares = consommationsDeclareesAnnee($db, $annee);
    foreach ($data as &$ligne) {
        $total = $declares[(int)$ligne['idConsommateur']] ?? ['eau' => 0.0, 'electricite' => 0.0];
        $ligne['eauDeclaree'] = $total['eau'];
        $ligne['electriciteDeclaree'] = $total['electricite'];
        $ligne['ecartEau'] = $ligne['idAnnuel'] ? round((float)$ligne['ConsommationEau'] - $total['eau'], 4) : null;
        $ligne['ecartElectricite'] = $ligne['idAnnuel'] ? round((float)$ligne['ConsommationElectricite'] - $total['electricite'], 4) : null;
    }
    unset($ligne);
    if (empty($_SESSION['csrf_bilan_annuel'])) $_SESSION['csrf_bilan_annuel'] = bin2hex(random_bytes(32));
    $message = $_SESSION['message_bilan_annuel'] ?? '';
    unset($_SESSION['message_bilan_annuel']);
    include ROOT.'IHM/fournisseur/bilanAnnuel.php';
}

function reglerBilanAnnuel($id)
{
    $fournisseur = (int)($_SESSION['user']['id'] ?? 0);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$fournisseur || !checkFournisseur($fournisseur)
        || !$id || !hash_equals($_SESSION['csrf_bilan_annuel'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); echo 'Action non autorisée.'; return;
    }
    $db = connexion();
    $_SESSION['message_bilan_annuel'] = reglerRegularisationAnnuelle($db, $fournisseur, $id)
        ? 'Régularisation marquée comme payée. Les rappels sont arrêtés.'
        : 'Régularisation introuvable ou déjà payée.';
    $annee = (int)($_POST['annee'] ?? date('Y'));
    header('Location:'.link.'annuelle/all/'.$annee, true, 303);
    exit;
}
