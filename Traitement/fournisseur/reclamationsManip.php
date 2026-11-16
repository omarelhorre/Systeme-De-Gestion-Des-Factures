<?php
require_once __DIR__.'/../../BD/connexion.php';
require_once __DIR__.'/../../BD/reclamationsFournisseur.php';

function texteReclamation($valeur)
{
    return htmlspecialchars((string)($valeur ?? ''), ENT_QUOTES, 'UTF-8');
}

function afficherReclamationsFournisseur()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    header('Cache-Control: no-store, private');
    $idFournisseur = filter_var($_SESSION['user']['id'] ?? null,
        FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$idFournisseur) {
        header('Location: '.link.'utilisateur/authentification');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        exit('Méthode non autorisée.');
    }
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db = connexion();
        mysqli_set_charset($db, 'utf8mb4');
        if (!estFournisseurReclamation($db, $idFournisseur)) {
            mysqli_close($db);
            http_response_code(403);
            exit('Accès réservé aux fournisseurs.');
        }
        $reclamations = listeReclamationsFournisseur($db, $idFournisseur);
        mysqli_close($db);
    } catch (mysqli_sql_exception $erreur) {
        error_log('Réclamations : '.$erreur->getMessage());
        http_response_code(503);
        exit('Impossible de charger les réclamations. Réessayez plus tard.');
    }
    include __DIR__.'/../../IHM/fournisseur/reclamationsList.php';
}
