<?php
// Résilier des contrats d'un client, en deux temps.
// Variables fournies par resilier() : $client, $contratsActifs, $contratsEnResiliation, $erreur.
//  - Contrats actifs : cochés puis "Lancer la résiliation" -> le client doit déclarer son relevé final.
//  - Contrats en résiliation : en attente du relevé final ; "Clôturer sans relevé final" les ferme tout de suite.
$coqueTitre = 'Résilier des contrats';
$coqueActif = 'clients';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
// Les cases déjà cochées sont conservées si le formulaire revient avec une erreur
$coches = is_array($_POST['contrats'] ?? null) ? array_map('intval', $_POST['contrats']) : array();
$ligneContrat = function ($ligne, $etiquette, $classe) use ($e, $coches) { ?>
    <li>
        <!-- toute la ligne est cliquable : c'est le libellé de la case -->
        <label class="ligne choix">
            <input type="checkbox" name="contrats[]" value="<?= (int)$ligne['id'] ?>" <?= in_array((int)$ligne['id'], $coches, true) ? 'checked' : '' ?>>
            <span class="ligne-texte">
                <span class="ligne-nom" style="display: block"><?= $ligne['adresse'] !== null ? $e($ligne['adresse']) : 'Adresse non renseignée' ?></span>
                <span class="ligne-detail" style="display: block">Contrat N° <?= (int)$ligne['id'] ?><?= $ligne['idAdresseCompteur'] ? ' · compteur N° '.(int)$ligne['idAdresseCompteur'] : '' ?><?= $ligne['categorie'] ? ' · '.$e($ligne['categorie']) : '' ?> · depuis le <?= $e(date('d/m/Y', strtotime($ligne['dateDebut']))) ?></span>
            </span>
            <span class="etat <?= $classe ?>"><?= $etiquette ?></span>
        </label>
    </li>
<?php };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <a class="lien-retour" href="<?= link ?>consommateur/modifier/<?= (int)$client['id'] ?>"><i class="fa-solid fa-arrow-left"></i> Retour au client</a>
    <header class="entete">
        <h1 class="titre">Résilier des contrats</h1>
        <p class="sous-titre"><?= $e($client['prenom'].' '.$client['nom']) ?> · <?= count($contratsActifs) ?> actif<?= count($contratsActifs) > 1 ? 's' : '' ?> · <?= count($contratsEnResiliation) ?> en résiliation</p>
    </header>

    <?php if ($erreur !== '') { ?>
        <p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= $e($erreur) ?></span></p>
    <?php } ?>

    <div class="pile">
        <section class="carte">
            <h2 class="titre-carte">Contrats actifs</h2>
            <?php if ($contratsActifs) { ?>
                <form method="post" action="<?= link ?>contrat/resilier/<?= (int)$client['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_resiliation']) ?>">
                    <input type="hidden" name="action" value="resilier">
                    <ul class="lignes">
                        <?php foreach ($contratsActifs as $ligne) $ligneContrat($ligne, 'Actif', 'ok'); ?>
                    </ul>
                    <div class="actions fin" style="margin-top: 1.25rem">
                        <a class="bouton contour" href="<?= link ?>consommateur/modifier/<?= (int)$client['id'] ?>">Retour</a>
                        <button class="bouton danger" type="submit"><i class="fa-solid fa-xmark"></i> Lancer la résiliation</button>
                    </div>
                </form>
            <?php } else { ?>
                <p class="vide">Aucun contrat actif.</p>
            <?php } ?>
        </section>

        <?php if ($contratsEnResiliation) { ?>
        <section class="carte">
            <h2 class="titre-carte">En attente du relevé final</h2>
            <form method="post" action="<?= link ?>contrat/resilier/<?= (int)$client['id'] ?>">
                <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_resiliation']) ?>">
                <input type="hidden" name="action" value="cloturer">
                <ul class="lignes">
                    <?php foreach ($contratsEnResiliation as $ligne) $ligneContrat($ligne, 'En résiliation', 'attente'); ?>
                </ul>
                <div class="actions fin" style="margin-top: 1.25rem">
                    <button class="bouton contour" type="submit"><i class="fa-solid fa-lock"></i> Clôturer sans relevé final</button>
                </div>
            </form>
        </section>
        <?php } ?>
    </div>
</main>
</body>
</html>
