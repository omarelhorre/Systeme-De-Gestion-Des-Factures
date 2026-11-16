<?php
define('ROOT', dirname(__DIR__).'/');
require_once ROOT.'BD/connexion.php';
require_once ROOT.'Traitement/agent/rappelsAnnuels.php';
try {
    $db = connexion();
    mysqli_set_charset($db, 'utf8mb4');
    echo envoyerRappelsAnnuels($db)." rappel(s) envoyé(s).\n";
} catch (Throwable $e) {
    error_log('Rappels annuels : '.$e->getMessage());
    exit(1);
}
