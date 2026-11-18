<?php
$coqueTitre = 'Page introuvable';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<main class="page-404 entree">
    <h1 class="code-404">404</h1>
    <p class="legende">Cette page n'existe pas.</p>
    <a class="lien-retour" href="<?= link ?>utilisateur/authentification">Retour à l'accueil</a>
</main>
</body>
</html>
