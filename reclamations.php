<?php
// Point d'entrée autonome : le routeur existant reste intact.
define('ROOT', __DIR__ . '/');
define('link', 'http://localhost:8080/');
define('dir', '');

session_start();
require_once ROOT . 'BD/connexion.php';
require_once ROOT . 'BD/declarationConsommation.php';
require_once ROOT . 'BD/reclamationsConsommateur.php';
require_once ROOT . 'Traitement/consommateur/reclamations.php';
require_once ROOT . 'Traitement/notification.php';

traiterReclamationsConsommateur();
