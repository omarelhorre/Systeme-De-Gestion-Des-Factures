<?php
// Menu de l'agent. La page définit $coqueActif avant l'include : 'tableau'.
$coqueRole = 'Agent';
$coqueAccueil = 'agent/tableauDeBord';
$coqueMenu = array(
    array('tableau', 'agent/tableauDeBord', 'fa-file-arrow-up', 'Importation annuelle'),
);
include ROOT.'IHM/commun/coque.php';
