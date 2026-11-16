<?php
// Menu du fournisseur. La page définit $coqueActif avant l'include :
// 'tableau', 'clients', 'factures', 'anomalies', 'annuel' ou 'reclamations'.
$coqueRole = 'Fournisseur';
$coqueAccueil = 'fournisseur/tableauDeBord';
$coqueMenu = array(
    array('tableau',      'fournisseur/tableauDeBord', 'fa-house',                'Tableau de bord'),
    array('clients',      'consommateur/all',          'fa-user-group',           'Clients'),
    array('factures',     'facture/all',               'fa-file-invoice',         'Factures'),
    array('anomalies',    'declaration/anomalies',     'fa-triangle-exclamation', 'Anomalies'),
    array('annuel',       'annuelle/all',              'fa-calendar-check',       'Bilan annuel'),
    array('reclamations', 'reclamation/all',           'fa-comment-dots',         'Réclamations'),
);
include ROOT.'IHM/commun/coque.php';
