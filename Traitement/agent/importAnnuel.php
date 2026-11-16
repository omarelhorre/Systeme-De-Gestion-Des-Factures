<?php
require_once ROOT.'BD/annuelle.php';
require_once ROOT.'Traitement/agent/calculAnnuel.php';
require_once ROOT.'Traitement/agent/rappelsAnnuels.php';

function lireLignesFichierAnnuel($contenu, $agent, $annee, $clients)
{
    if (str_contains($contenu, "\0")) throw new InvalidArgumentException('Le fichier doit être un texte simple.');
    $lignes = preg_split('/\R/', trim($contenu));
    $resultat = [];
    $vus = [];
    foreach ($lignes as $numero => $ligne) {
        $ligne = trim($ligne);
        if ($ligne === '') continue;
        if ($numero === 0 && str_starts_with($ligne, 'ID_Client')) continue;
        $champs = preg_split('/\s+/', $ligne);
        if (count($champs) !== 6 || !ctype_digit($champs[0]) || !ctype_digit($champs[1])
            || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $champs[2])
            || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $champs[3])
            || !ctype_digit($champs[4])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $champs[5])) {
            throw new InvalidArgumentException('Ligne '.($numero + 1).' invalide : six champs séparés par des espaces sont requis.');
        }
        [$client, $idAgent, $eau, $electricite, $anneeLigne, $date] = $champs;
        $dateValide = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ((int)$idAgent !== $agent || (int)$anneeLigne !== $annee || !$dateValide
            || $dateValide->format('Y-m-d') !== $date || (int)$dateValide->format('Y') !== $annee
            || !in_array((int)$client, $clients, true) || isset($vus[$client])) {
            throw new InvalidArgumentException('Ligne '.($numero + 1).' : agent, année, date ou client incorrect, ou client répété.');
        }
        $vus[$client] = true;
        $resultat[] = [(int)$client, (float)$eau, (float)$electricite, $date];
    }
    if (!$resultat) throw new InvalidArgumentException('Le fichier ne contient aucun client.');
    return $resultat;
}

function traiterImportAnnuel()
{
    $agent = (int)($_SESSION['user']['id'] ?? 0);
    if (!$agent || !checkAgent($agent)) { http_response_code(403); echo 'Accès réservé aux agents.'; return; }
    $annee = (int)(new DateTimeImmutable('now', new DateTimeZone('Africa/Casablanca')))->format('Y');
    $db = connexion();
    mysqli_set_charset($db, 'utf8mb4');
    $erreur = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if (!hash_equals($_SESSION['csrf_annuel'] ?? '', $_POST['csrf'] ?? '')) throw new InvalidArgumentException('Rechargez la page et réessayez.');
            if (fichierAnnuelDeAgent($db, $agent, $annee)) throw new InvalidArgumentException('Votre fichier de cette année a déjà été importé.');
            $fichier = $_FILES['fichier'] ?? null;
            if (!$fichier || $fichier['error'] !== UPLOAD_ERR_OK || $fichier['size'] < 1
                || $fichier['size'] > 2 * 1024 * 1024 || !is_uploaded_file($fichier['tmp_name'])
                || strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION)) !== 'txt') {
                throw new InvalidArgumentException('Choisissez un fichier .txt de moins de 2 Mo.');
            }
            $contenu = file_get_contents($fichier['tmp_name']);
            $clients = array_map('intval', clientsAnnuels($db));
            $lignes = lireLignesFichierAnnuel($contenu, $agent, $annee, $clients);
            $totaux = consommationsDeclareesAnnee($db, $annee);
            $chemin = 'stockage_annuel/'.bin2hex(random_bytes(12)).'.txt';
            if (!move_uploaded_file($fichier['tmp_name'], ROOT.$chemin)) throw new RuntimeException('Sauvegarde du fichier impossible.');
            try {
                mysqli_begin_transaction($db);
                enregistrerFichierAnnuel($db, $chemin);
                $dateLimite = (new DateTimeImmutable('today', new DateTimeZone('Africa/Casablanca')))->modify('+10 days')->format('Y-m-d');
                foreach ($lignes as [$client, $eau, $electricite, $date]) {
                    $idAnnuel = enregistrerConsommationAnnuelle($db, $client, $chemin, $agent, $annee, $eau, $electricite, $date);
                    $declare = $totaux[$client] ?? ['eau' => 0.0, 'electricite' => 0.0];
                    $differenceEau = round($eau - $declare['eau'], 4);
                    $differenceElectricite = round($electricite - $declare['electricite'], 4);
                    $montant = estimerRegularisationAnnuelle($eau, $electricite, $declare['eau'], $declare['electricite']);
                    if ($montant > 0) enregistrerRegularisationAnnuelle($db, $idAnnuel, $differenceEau, $differenceElectricite, $montant, $dateLimite);
                }
                mysqli_commit($db);
            } catch (Throwable $e) {
                mysqli_rollback($db);
                unlink(ROOT.$chemin);
                throw $e;
            }
            $envoyes = envoyerRappelsAnnuels($db);
            $_SESSION['message_annuel'] = 'Fichier importé pour '.$annee.' ('.count($lignes).' client(s)). '.$envoyes.' notification(s) envoyée(s).';
            header('Location:'.link.'agent/tableauDeBord', true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            $erreur = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $erreur = $e->getCode() === 1062 ? 'Une déclaration annuelle existe déjà pour un des clients.' : 'Importation impossible.';
            error_log('Import annuel : '.$e->getMessage());
        } catch (Throwable $e) {
            $erreur = 'Importation impossible.';
            error_log('Import annuel : '.$e->getMessage());
        }
    }
    if (empty($_SESSION['csrf_annuel'])) $_SESSION['csrf_annuel'] = bin2hex(random_bytes(32));
    $message = $_SESSION['message_annuel'] ?? '';
    unset($_SESSION['message_annuel']);
    $fichierImporte = fichierAnnuelDeAgent($db, $agent, $annee);
    include ROOT.'IHM/tableauA.php';
}
