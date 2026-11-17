<?php

function validerReclamationConsommateur(array $donnees): array
{
    $types = ['fuite_interne', 'fuite_externe', 'facture', 'autre'];
    $type = $donnees['type'] ?? null;
    $description = $donnees['description'] ?? null;
    if (!is_string($type) || !in_array($type, $types, true)) {
        throw new InvalidArgumentException('Choisissez un type de réclamation valide.');
    }
    if (!is_string($description)) {
        throw new InvalidArgumentException('Saisissez une description.');
    }
    $description = trim($description);
    if ($description === '' || !preg_match('//u', $description) || preg_match_all('/./us', $description) > 1000) {
        throw new InvalidArgumentException('La description doit contenir entre 1 et 1000 caractères.');
    }
    $idFacture = null;
    if ($type === 'facture') {
        $valeur = $donnees['idFacture'] ?? null;
        if (!is_string($valeur) || !ctype_digit($valeur) || (int)$valeur < 1 || (string)(int)$valeur !== $valeur) {
            throw new InvalidArgumentException('Choisissez une facture valide.');
        }
        $idFacture = (int)$valeur;
    }
    return [$type, $description, $idFacture];
}

function traiterReclamationsConsommateur(): void
{
    $methode = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($methode, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        http_response_code(405);
        return;
    }
    $idSession = $_SESSION['user']['id'] ?? null;
    if ((!is_int($idSession) && (!is_string($idSession) || !ctype_digit($idSession))) || (int)$idSession < 1 || (string)(int)$idSession !== (string)$idSession) {
        http_response_code(403);
        echo 'Connexion requise.';
        return;
    }
    $idConsommateur = (int)$idSession;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $db = connexion();
        if (!$db) throw new RuntimeException('Connexion à la base impossible.');
        mysqli_set_charset($db, 'utf8mb4');
        if (!clientConnecte($db, $idConsommateur)) {
            http_response_code(403);
            echo 'Accès réservé aux consommateurs.';
            return;
        }
        if (empty($_SESSION['csrf_reclamations'])) {
            $_SESSION['csrf_reclamations'] = bin2hex(random_bytes(32));
        }
        $erreur = '';
        $valeurs = ['type' => '', 'description' => '', 'idFacture' => ''];
        if ($methode === 'POST') {
            $valeurs = [
                'type' => is_string($_POST['type'] ?? null) ? $_POST['type'] : '',
                'description' => is_string($_POST['description'] ?? null) ? $_POST['description'] : '',
                'idFacture' => is_string($_POST['idFacture'] ?? null) ? $_POST['idFacture'] : '',
            ];
            try {
                $csrf = $_POST['csrf'] ?? null;
                if (!is_string($csrf) || !hash_equals($_SESSION['csrf_reclamations'], $csrf)) {
                    throw new InvalidArgumentException('Session expirée : rechargez la page.');
                }
                [$type, $description, $idFacture] = validerReclamationConsommateur($_POST);
                enregistrerReclamationConsommateur($db, $idConsommateur, $type, $description, $idFacture);
                // Notification : le fournisseur du client est prévenu de la nouvelle réclamation.
                $idNouvelle = notificationDerniereReclamation($idConsommateur);
                notifier(notificationFournisseurDe($idConsommateur), 'reclamation_nouvelle', 'Nouvelle réclamation n° '.$idNouvelle, mb_substr($description, 0, 200), 'reclamation/detail/'.$idNouvelle, array('aTraiter' => true, 'idObjet' => $idNouvelle));
                $_SESSION['csrf_reclamations'] = bin2hex(random_bytes(32));
                $_SESSION['message_reclamations'] = 'Réclamation enregistrée. Son statut est « En attente ».';
                header('Location: ' . link . 'reclamation/all', true, 303);
                return;
            } catch (InvalidArgumentException $exception) {
                $erreur = $exception->getMessage();
            }
        }
        $message = $_SESSION['message_reclamations'] ?? '';
        unset($_SESSION['message_reclamations']);
        $factures = facturesDuConsommateur($db, $idConsommateur);
        $reclamations = historiqueReclamationsConsommateur($db, $idConsommateur);
        include ROOT . 'IHM/consommateur/reclamations.php';
    } catch (Throwable $exception) {
        error_log('Fatourti réclamations : ' . $exception->getMessage());
        http_response_code(503);
        echo 'Service temporairement indisponible.';
    }
}
