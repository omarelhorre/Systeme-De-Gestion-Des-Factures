<?php
// Résilier des contrats d'un client : on coche ceux à résilier, puis on confirme.
// Variables fournies par resilier() : $client, $contratsActifs, $erreur.
$coqueTitre = 'Résilier des contrats';
$coqueActif = 'clients';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
// Les cases déjà cochées sont conservées si le formulaire revient avec une erreur
$coches = is_array($_POST['contrats'] ?? null) ? array_map('intval', $_POST['contrats']) : array();
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
        <p class="sous-titre"><?= $e($client['prenom'].' '.$client['nom']) ?> · <?= count($contratsActifs) ?> contrat<?= count($contratsActifs) > 1 ? 's' : '' ?> actif<?= count($contratsActifs) > 1 ? 's' : '' ?></p>
    </header>

    <?php if ($erreur !== '') { ?>
        <p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= $e($erreur) ?></span></p>
    <?php } ?>

    <section class="carte">
        <?php if ($contratsActifs) { ?>
            <form method="post" action="<?= link ?>contrat/resilier/<?= (int)$client['id'] ?>">
                <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_resiliation']) ?>">
                <h2 class="titre-carte">Contrats à résilier</h2>
                <p class="legende" style="margin-bottom: 1rem">Cochez un ou plusieurs contrats. Une résiliation ne peut pas être annulée.</p>

                <ul class="lignes">
                    <?php foreach ($contratsActifs as $ligne) { ?>
                        <li>
                            <!-- toute la ligne est cliquable : c'est le libellé de la case -->
                            <label class="ligne choix">
                                <input type="checkbox" name="contrats[]" value="<?= (int)$ligne['id'] ?>" <?= in_array((int)$ligne['id'], $coches, true) ? 'checked' : '' ?>>
                                <span class="ligne-texte">
                                    <span class="ligne-nom" style="display: block"><?= $ligne['adresse'] !== null ? $e($ligne['adresse']) : 'Adresse non renseignée' ?></span>
                                    <span class="ligne-detail" style="display: block">Contrat N° <?= (int)$ligne['id'] ?><?= $ligne['idAdresseCompteur'] ? ' · compteur N° '.(int)$ligne['idAdresseCompteur'] : '' ?><?= $ligne['categorie'] ? ' · '.$e($ligne['categorie']) : '' ?> · depuis le <?= $e(date('d/m/Y', strtotime($ligne['dateDebut']))) ?></span>
                                </span>
                                <span class="pill ok">Actif</span>
                            </label>
                        </li>
                    <?php } ?>
                </ul>

                <div class="actions fin" style="margin-top: 1.25rem">
                    <a class="bouton contour" href="<?= link ?>consommateur/modifier/<?= (int)$client['id'] ?>">Retour</a>
                    <button class="bouton danger" type="submit"><i class="fa-solid fa-xmark"></i> Résilier la sélection</button>
                </div>
            </form>
        <?php } else { ?>
            <p class="vide">Ce client n'a aucun contrat actif.</p>
        <?php } ?>
    </section>
</main>
</body>
</html>
