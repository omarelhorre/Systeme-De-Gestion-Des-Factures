<?php
// Entrée indépendante jusqu'à l'intégration explicite dans le routeur d'Omar.
define('ROOT', __DIR__.'/');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once ROOT.'BD/connexion.php';
require_once ROOT.'BD/declarationConsommation.php';
require_once ROOT.'Traitement/consommateur/reglesConsommation.php';
require_once ROOT.'Traitement/consommateur/pdfFacture.php';
require_once ROOT.'Traitement/consommateur/declarationConsommation.php';
traiterConsommation();
