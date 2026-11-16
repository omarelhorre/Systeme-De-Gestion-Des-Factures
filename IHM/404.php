<?php
// Page introuvable.
$coqueTitre = 'Page introuvable';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<main class="page-seule entree">
    <section class="carte">
        <p class="marque">Fatourti</p>
        <p class="chiffre geant" style="margin-top: 1rem">404</p>
        <p class="legende">Cette page n'existe pas ou a été déplacée.</p>
        <div class="actions" style="margin-top: 0.75rem">
            <a class="bouton accent" href="<?= link ?>utilisateur/authentification">Retour à la connexion <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </section>
</main>
</body>
</html>
