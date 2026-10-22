<?php
function cheminStockage()
{
    return ROOT.'stockage_consommation/';
}

function photoValide($photo)
{
    if (!isset($photo['error']) || $photo['error'] !== UPLOAD_ERR_OK || $photo['size'] > 3 * 1024 * 1024 || $photo['size'] < 100 || !is_uploaded_file($photo['tmp_name'])) {
        throw new InvalidArgumentException('Chaque photo JPEG doit faire moins de 3 Mo.');
    }
    $info = @getimagesize($photo['tmp_name']);
    $type = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
    if (!$info || $type !== 'image/jpeg' || $info[2] !== IMAGETYPE_JPEG || ($info['channels'] ?? 0) !== 3 || $info[0] > 6000 || $info[1] > 6000) {
        throw new InvalidArgumentException('Photo JPEG couleur invalide.');
    }
}

function sauverPhoto($photo)
{
    photoValide($photo);
    $nom = bin2hex(random_bytes(16)).'.jpg';
    $chemin = cheminStockage().$nom;
    if (!move_uploaded_file($photo['tmp_name'], $chemin)) throw new RuntimeException('Impossible de sauvegarder la photo.');
    return 'stockage_consommation/'.$nom;
}

function afficherFichier($db, $idClient)
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $type = $_GET['fichier'] ?? '';
    if (!$id || !in_array($type, array('facture', 'eau', 'electricite'), true)) {
        http_response_code(404); exit;
    }
    $relatif = fichierDuClient($db, $idClient, $id, $type);
    $racine = realpath(cheminStockage());
    $fichier = $relatif ? realpath(ROOT.$relatif) : false;
    if (!$racine || !$fichier || strpos($fichier, $racine.DIRECTORY_SEPARATOR) !== 0 || !is_file($fichier)) {
        http_response_code(404); exit;
    }
    header('Content-Type: '.($type === 'facture' ? 'application/pdf' : 'image/jpeg'));
    header('Content-Disposition: attachment; filename="'.($type === 'facture' ? 'facture-'.$id.'.pdf' : 'compteur-'.$type.'-'.$id.'.jpg').'"');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: '.filesize($fichier));
    readfile($fichier);
    exit;
}

function traiterConsommation()
{
    if (empty($_SESSION['user']['id']) || !is_numeric($_SESSION['user']['id'])) {
        http_response_code(403); echo 'Connexion requise.'; return;
    }
    $idClient = (int)$_SESSION['user']['id'];
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $db = connexion(getenv('FATOURTI_DB_HOST') ?: 'localhost', getenv('FATOURTI_DB_USER') ?: 'root', getenv('FATOURTI_DB_NAME') ?: 'fatourti');
        if (!$db) throw new RuntimeException('Connexion à la base impossible.');
        mysqli_set_charset($db, 'utf8mb4');
        $client = clientConnecte($db, $idClient);
        if (!$client) { http_response_code(403); echo 'Accès réservé aux consommateurs.'; return; }
        if (isset($_GET['fichier'])) afficherFichier($db, $idClient);
        $date = dateConsommation();
        $message = '';
        $erreur = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (empty($_SESSION['csrf_consommation']) || !isset($_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals($_SESSION['csrf_consommation'], $_POST['csrf'])) throw new InvalidArgumentException('Session expirée : rechargez la page.');
                if (!declarationOuverte($date)) throw new InvalidArgumentException('Les déclarations ouvrent le 18 de chaque mois.');
                $eau = lireIndex($_POST['indexEau'] ?? null);
                $electricite = lireIndex($_POST['indexElectricite'] ?? null);
                photoValide($_FILES['photoEau'] ?? array());
                photoValide($_FILES['photoElectricite'] ?? array());
                $premier = $date->format('Y-m-01');
                $suivant = $date->modify('first day of next month')->format('Y-m-d');
                $jour = $date->format('Y-m-d');
                $sauves = array();
                mysqli_begin_transaction($db);
                try {
                    if (!verrouillerClient($db, $idClient)) throw new RuntimeException('Client introuvable.');
                    if (declarationDuMois($db, $idClient, $premier, $suivant)) throw new InvalidArgumentException('Une déclaration existe déjà pour ce mois.');
                    $precedente = derniereDeclarationValide($db, $idClient);
                    $calcul = calculerDeclaration($eau, $electricite, $precedente);
                    $statut = $calcul['statut'] === 'Anomalie' ? 'Anomalie : '.$calcul['motif'] : $calcul['statut'];
                    $photoEau = sauverPhoto($_FILES['photoEau']); $sauves[] = ROOT.$photoEau;
                    $photoElectricite = sauverPhoto($_FILES['photoElectricite']); $sauves[] = ROOT.$photoElectricite;
                    $debut = $precedente ? $precedente['dateSaisie'] : $jour;
                    $fin = $precedente ? $jour : null;
                    $idDeclaration = enregistrerDeclaration($db, (int)$client['idFournisseur'], $idClient, $photoEau, $photoElectricite, $eau, $electricite, $jour, $statut, $debut, $fin, $calcul['ht'], $calcul['ttc']);
                    if ($calcul['statut'] === 'Validée') {
                        $pdfRelatif = 'stockage_consommation/'.bin2hex(random_bytes(16)).'.pdf';
                        $idFacture = enregistrerFacture($db, $idDeclaration, $pdfRelatif, $jour);
                        $courante = array('indexEau' => $eau, 'indexElectricite' => $electricite, 'dateSaisie' => $jour);
                        $sauves[] = ROOT.$pdfRelatif;
                        creerPdfFacture(ROOT.$pdfRelatif, $idFacture, $client, $precedente, $courante, $calcul, ROOT.$photoEau, ROOT.$photoElectricite);
                    }
                    mysqli_commit($db);
                    $_SESSION['message_consommation'] = $calcul['statut'] === 'Validée' ? 'Déclaration enregistrée et facture créée.' : ($calcul['statut'] === 'Anomalie' ? $statut.' — aucune facture créée.' : 'Premier relevé de référence enregistré. Aucune facture créée.');
                    header('Location: '.$_SERVER['SCRIPT_NAME']); exit;
                } catch (Throwable $e) {
                    mysqli_rollback($db);
                    foreach ($sauves as $fichier) if (is_file($fichier)) unlink($fichier);
                    throw $e;
                }
            } catch (InvalidArgumentException $e) {
                $erreur = $e->getMessage();
            } catch (Throwable $e) {
                error_log('Fatourti consommation : '.$e->getMessage());
                $erreur = 'Enregistrement impossible. Réessayez ou contactez le fournisseur.';
            }
        }
        $message = $_SESSION['message_consommation'] ?? '';
        unset($_SESSION['message_consommation']);
        $precedente = derniereDeclarationValide($db, $idClient);
        $declarations = declarationsDuClient($db, $idClient);
        if (empty($_SESSION['csrf_consommation'])) $_SESSION['csrf_consommation'] = bin2hex(random_bytes(32));
        include ROOT.'IHM/consommateur/declarationConsommation.php';
    } catch (Throwable $e) {
        error_log('Fatourti consommation : '.$e->getMessage());
        http_response_code(503);
        echo 'Service temporairement indisponible.';
    }
}
