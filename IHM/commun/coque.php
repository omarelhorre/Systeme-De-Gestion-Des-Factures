<?php
// Barre du haut + menu d'icônes, communs à tous les rôles.
// Les fichiers menu.php de chaque rôle définissent :
//   $coqueMenu    : liste de [clé, adresse, icône Font Awesome, libellé]
//   $coqueRole    : nom du rôle affiché à droite
//   $coqueAccueil : adresse du tableau de bord du rôle
// et la page définit $coqueActif (la clé de l'entrée active).
$coqueNom = trim(($_SESSION['user']['prenom'] ?? '').' '.($_SESSION['user']['nom'] ?? ''));
?>
<header class="barre">
    <a class="marque" href="<?= link.$coqueAccueil ?>">Fatourti</a>
    <p class="compte">
        <span class="compte-nom"><?= htmlspecialchars($coqueNom, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="compte-role"><?= $coqueRole ?></span>
    </p>
</header>

<nav class="menu" aria-label="Menu <?= strtolower($coqueRole) ?>">
    <div class="menu-liens">
        <?php foreach ($coqueMenu as $coqueEntree) { ?>
            <a class="<?= ($coqueActif ?? '') === $coqueEntree[0] ? 'actif' : '' ?>" href="<?= link.$coqueEntree[1] ?>"
               title="<?= $coqueEntree[3] ?>" aria-label="<?= $coqueEntree[3] ?>"<?= ($coqueActif ?? '') === $coqueEntree[0] ? ' aria-current="page"' : '' ?>>
                <i class="fa-solid <?= $coqueEntree[2] ?>"></i>
            </a>
        <?php } ?>
    </div>
    <a class="sortie" href="<?= link ?>utilisateur/deconnexion" title="Déconnexion" aria-label="Déconnexion">
        <i class="fa-solid fa-door-open"></i>
    </a>
</nav>
