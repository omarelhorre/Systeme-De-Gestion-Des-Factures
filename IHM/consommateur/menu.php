<?php
$coqueRole = 'Consommateur';
$coqueAccueil = 'consommateur/tableauDeBord';
$coqueMenu = array(
    array('tableau',      'consommateur/tableauDeBord', 'fa-house',        'Tableau de bord'),
    array('releves',      'declaration/all',            'fa-gauge-high',   'Mes relevés'),
    array('reclamations', 'reclamation/all',            'fa-comment-dots', 'Mes réclamations'),
);
include ROOT.'IHM/commun/coque.php';
