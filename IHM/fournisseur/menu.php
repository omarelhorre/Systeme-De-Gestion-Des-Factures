<?php
// Barre du haut + menu d'icônes du fournisseur.
// Avant l'include, la page définit $actif : 'tableau', 'clients', 'factures', 'anomalies', 'annuel' ou 'reclamations'.
$menuFournisseur = array(
    'tableau'      => array('fournisseur/tableauDeBord', 'fa-house',                'Tableau de bord'),
    'clients'      => array('consommateur/all',          'fa-user-group',           'Clients'),
    'factures'     => array('facture/all',               'fa-file-invoice',         'Factures'),
    'anomalies'    => array('declaration/anomalies',     'fa-triangle-exclamation', 'Anomalies'),
    'annuel'       => array('annuelle/all',              'fa-calendar-check',       'Bilan annuel'),
    'reclamations' => array('reclamation/all',           'fa-comment-dots',         'Réclamations'),
);
$nomComplet = trim(($_SESSION['user']['prenom'] ?? '').' '.($_SESSION['user']['nom'] ?? ''));
?>
<header class="barre">
    <a class="marque" href="<?= link ?>fournisseur/tableauDeBord">Fatourti</a>
    <p class="compte">
        <span class="compte-nom"><?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="compte-role">Fournisseur</span>
    </p>
</header>

<nav class="menu" aria-label="Menu fournisseur">
    <div class="menu-liens">
        <?php foreach ($menuFournisseur as $cle => $entree) { ?>
            <a class="<?= ($actif ?? '') === $cle ? 'actif' : '' ?>" href="<?= link.$entree[0] ?>"
               title="<?= $entree[2] ?>" aria-label="<?= $entree[2] ?>"<?= ($actif ?? '') === $cle ? ' aria-current="page"' : '' ?>>
                <i class="fa-solid <?= $entree[1] ?>"></i>
            </a>
        <?php } ?>
    </div>
    <a class="sortie" href="<?= link ?>utilisateur/deconnexion" title="Déconnexion" aria-label="Déconnexion">
        <i class="fa-solid fa-door-open"></i>
    </a>
</nav>
