<?php
// Liste des clients du fournisseur. $data vient de afficherClients() : [id, nom, prénom, adresse].
$coqueTitre = 'Clients';
$coqueActif = 'clients';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <header class="entete ligne">
        <div>
            <h1 class="titre">Clients</h1>
            <p class="sous-titre"><?= count($data) ?> client<?= count($data) > 1 ? 's' : '' ?></p>
        </div>
        <a class="bouton accent" href="<?= link ?>consommateur/ajouter"><i class="fa-solid fa-plus"></i> Ajouter un client</a>
    </header>

    <section class="carte">
        <?php if ($data) { ?>
            <table class="tableau">
                <thead>
                    <tr><th>Client</th><th class="secondaire">Adresse</th><th></th></tr>
                </thead>
                <tbody data-pagine="8">
                    <?php foreach ($data as $V) { ?>
                        <tr>
                            <td>
                                <div class="cellule-nom">
                                    <span class="avatar" data-avatar="<?= (int)$V[0] ?>"></span>
                                    <div class="tronque">
                                        <span class="fort"><?= $e($V[2].' '.$V[1]) ?></span>
                                        <small>N° <?= (int)$V[0] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="secondaire"><div class="tronque" title="<?= $e($V[3]) ?>"><?= $e($V[3]) ?></div></td>
                            <td class="fin">
                                <div class="actions fin">
                                    <a class="bouton contour petit" href="<?= link ?>consommateur/modifier/<?= (int)$V[0] ?>"><i class="fa-solid fa-pen"></i> Modifier</a>
                                    <a class="bouton danger petit" href="<?= link ?>consommateur/supprimer/<?= (int)$V[0] ?>"><i class="fa-solid fa-trash"></i> Supprimer</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="vide">Aucun client pour le moment. Ajoutez le premier avec le bouton ci-dessus.</p>
        <?php } ?>
    </section>
</main>
</body>
</html>
