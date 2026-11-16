<?php
// Confirmation avant de résilier un contrat. Le formulaire renvoie en POST sur la même adresse (resilier()).
$coqueTitre = 'Résilier le contrat';
$coqueActif = 'clients';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <div class="centre">
        <form class="carte" method="post">
            <h1 class="titre-carte">Résilier ce contrat ?</h1>
            <p class="legende">Cette opération ne peut pas être annulée une fois confirmée.</p>
            <div class="actions fin" style="margin-top: 0.75rem">
                <a class="bouton contour" href="<?= link ?>consommateur/all">Retour</a>
                <button class="bouton danger" type="submit"><i class="fa-solid fa-xmark"></i> Résilier le contrat</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
