<?php
require_once ROOT . 'BD/declarationsFournisseur.php';
require_once ROOT . 'BD/declarationConsommation.php';
require_once ROOT . 'Traitement/consommateur/reglesConsommation.php';
require_once ROOT . 'Traitement/consommateur/pdfFacture.php';

function texteAnomalie($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function erreurAnomalie(int $code, string $message): void
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}

function photoAnomalie(array $anomalie, string $action): void
{
    $relatif = $action === 'photoEau' ? $anomalie['cheminCompteurEauImg'] : $anomalie['cheminCompteurElectriciteImg'];
    $stockage = realpath(ROOT . 'stockage_consommation');
    $fichier = is_string($relatif) ? realpath(ROOT . $relatif) : false;
    if (!$stockage || !$fichier || !str_starts_with($fichier, $stockage . DIRECTORY_SEPARATOR)
        || !is_file($fichier) || !is_readable($fichier)
        || strtolower(pathinfo($fichier, PATHINFO_EXTENSION)) !== 'jpg'
        || @getimagesize($fichier)['mime'] !== 'image/jpeg') {
        erreurAnomalie(404, 'Photo indisponible.');
    }
    header('Content-Type: image/jpeg');
    header('Content-Disposition: inline; filename="compteur-' . (int)$anomalie['id'] . '.jpg"');
    header('Content-Length: ' . filesize($fichier));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    readfile($fichier);
    exit;
}

function traiterAnomaliesFournisseur(string $action, ?int $id = null): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (($action === 'corriger' && !in_array($method, ['GET', 'POST'], true))
        || ($action !== 'corriger' && $method !== 'GET')) {
        header('Allow: ' . ($action === 'corriger' ? 'GET, POST' : 'GET'));
        erreurAnomalie(405, 'Méthode non autorisée.');
    }
    $fournisseur = filter_var($_SESSION['user']['id'] ?? null, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);
    if (!$fournisseur) erreurAnomalie(401, 'Connexion requise.');
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $db = connexion();
        $db->set_charset('utf8mb4');
        if (!fournisseurAutoriseDeclarations($db, $fournisseur)) erreurAnomalie(403, 'Accès réservé aux fournisseurs.');
        if (empty($_SESSION['csrf_anomalies'])) $_SESSION['csrf_anomalies'] = bin2hex(random_bytes(32));
        $anomalie = null;
        $precedente = null;
        $erreur = '';
        if ($action !== 'anomalies') {
            if (!$id) erreurAnomalie(404, 'Déclaration introuvable.');
            $anomalie = anomalieDuFournisseur($db, $fournisseur, $id);
            if (!$anomalie) erreurAnomalie(404, 'Anomalie introuvable.');
            if ($action === 'photoEau' || $action === 'photoElectricite') photoAnomalie($anomalie, $action);
            $precedente = lecturePrecedenteFournisseur($db, (int)$anomalie['idAdresseCompteur'], $anomalie['dateSaisie']);
        }
        if ($action === 'corriger' && $method === 'POST') {
            try {
                if (!is_string($_POST['csrf'] ?? null)
                    || !hash_equals($_SESSION['csrf_anomalies'], $_POST['csrf'])) {
                    throw new InvalidArgumentException('Session expirée. Rechargez la page.');
                }
                $eau = lireIndex($_POST['indexEau'] ?? null);
                $electricite = lireIndex($_POST['indexElectricite'] ?? null);
                if (($_POST['confirmation'] ?? '') !== 'oui') {
                    throw new InvalidArgumentException('Confirmez avoir vérifié les photos et les index corrigés.');
                }
                $db->begin_transaction();
                try {
                    if (!verrouillerClient($db, (int)$anomalie['idConsommateur'])) {
                        throw new RuntimeException('Client introuvable.');
                    }
                    $anomalie = anomalieDuFournisseur($db, $fournisseur, $id, true);
                    if (!$anomalie) throw new InvalidArgumentException('Cette anomalie a déjà été corrigée ou n’est plus accessible.');
                    $clientId = (int)$anomalie['idConsommateur'];
                    if (existeDeclarationUlterieure($db, (int)$anomalie['idAdresseCompteur'], $anomalie['dateSaisie'])) {
                        throw new InvalidArgumentException('Une déclaration plus récente existe. Corrigez les relevés dans l’ordre chronologique.');
                    }
                    if (factureDeDeclarationExiste($db, $id)) {
                        throw new InvalidArgumentException('Une facture existe déjà pour cette déclaration.');
                    }
                    $precedente = lecturePrecedenteFournisseur($db, (int)$anomalie['idAdresseCompteur'], $anomalie['dateSaisie']);
                    if (!$precedente) throw new InvalidArgumentException('Aucun relevé précédent valide pour calculer la facture.');
                    if ($eau === (float)$anomalie['indexEau'] && $electricite === (float)$anomalie['indexElectricite']) {
                        throw new InvalidArgumentException('Modifiez au moins un index avant de valider la correction.');
                    }
                    $calcul = calculerDeclaration($eau, $electricite, $precedente, true);
                    if ($calcul['statut'] !== 'Validée') {
                        throw new InvalidArgumentException('Les index corrigés sont inférieurs au dernier relevé valide : ' . $calcul['motif']);
                    }
                    $photoEau = photoAnomalieChemin($anomalie['cheminCompteurEauImg']);
                    $photoElectricite = photoAnomalieChemin($anomalie['cheminCompteurElectriciteImg']);
                    if (!mettreAJourAnomalie($db, $fournisseur, $id, $eau, $electricite,
                        $precedente['dateSaisie'], $anomalie['dateSaisie'], $calcul['ht'], $calcul['ttc'])) {
                        throw new RuntimeException('La déclaration n’a pas été mise à jour.');
                    }
                    $pdfRelatif = 'stockage_consommation/' . bin2hex(random_bytes(16)) . '.pdf';
                    $dateFacture = dateConsommation()->format('Y-m-d');
                    $factureId = enregistrerFacture($db, $id, $pdfRelatif, $dateFacture);
                    $pdf = ROOT . $pdfRelatif;
                    $client = ['id' => $clientId, 'nom' => $anomalie['nom'],
                        'prenom' => $anomalie['prenom'], 'adresse' => $anomalie['adresse']];
                    creerPdfFacture($pdf, $factureId, $client, $precedente,
                        ['indexEau' => $eau, 'indexElectricite' => $electricite,
                         'dateSaisie' => $anomalie['dateSaisie']], $calcul, $photoEau, $photoElectricite, $dateFacture);
                    $db->commit();
                    // Notifications : le client est prévenu, et la demande du fournisseur est marquée traitée.
                    notifier($clientId, 'anomalie_corrigee', 'Votre relevé a été corrigé', 'La facture n° '.$factureId.' est disponible : '.number_format((float)$calcul['ttc'], 2, ',', ' ').' DH TTC.', 'declaration/all/'.(int)$anomalie['idAdresseCompteur'], array('idObjet' => $factureId, 'email' => true));
                    resoudreNotification($fournisseur, 'anomalie_a_corriger', $id);
                    // C'était le relevé final d'un contrat en résiliation : la facture finale existe, le contrat se ferme.
                    $idAdresseAnomalie = (int)$anomalie['idAdresseCompteur'];
                    if (statutContratAdresse($idAdresseAnomalie) === CONTRAT_EN_RESILIATION && cloturerContratAdresse($idAdresseAnomalie)) {
                        resoudreNotification($clientId, 'contrat_fin', $idAdresseAnomalie);
                        notifier($clientId, 'contrat_resilie', 'Contrat résilié', 'Votre contrat pour '.$anomalie['adresse'].' est terminé. Facture finale : n° '.$factureId.'.', 'declaration/all/'.$idAdresseAnomalie, array('idObjet' => $idAdresseAnomalie, 'email' => true));
                    }
                    $_SESSION['message_factures'] = 'Anomalie corrigée. La facture n° ' . $factureId . ' est maintenant disponible pour le client.';
                    header('Location: ' . link . 'facture/detail/' . $factureId, true, 303);
                    exit;
                } catch (Throwable $e) {
                    $db->rollback();
                    if (isset($pdf) && is_file($pdf)) unlink($pdf);
                    throw $e;
                }
            } catch (InvalidArgumentException $e) {
                $erreur = $e->getMessage();
            }
        }
        $data = $action === 'anomalies' ? anomaliesDuFournisseur($db, $fournisseur) : [];
        $message = $_SESSION['message_anomalies'] ?? '';
        unset($_SESSION['message_anomalies']);
        require ROOT . 'IHM/fournisseur/anomaliesList.php';
    } catch (mysqli_sql_exception $e) {
        error_log('Anomalies fournisseur : ' . $e->getMessage());
        erreurAnomalie(503, 'La base de données est indisponible.');
    } catch (Throwable $e) {
        error_log('Anomalies fournisseur : ' . $e->getMessage());
        erreurAnomalie(503, 'Correction indisponible. Réessayez plus tard.');
    }
}

function photoAnomalieChemin(string $relatif): string
{
    $stockage = realpath(ROOT . 'stockage_consommation');
    $fichier = realpath(ROOT . $relatif);
    if (!$stockage || !$fichier || !str_starts_with($fichier, $stockage . DIRECTORY_SEPARATOR)
        || !is_file($fichier) || @getimagesize($fichier)['mime'] !== 'image/jpeg') {
        throw new InvalidArgumentException('Une photo du compteur est indisponible.');
    }
    return $fichier;
}
