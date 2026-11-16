<?php
// Modification d'un client. Variables fournies par modify() : $result, $contrat, $adressesCompteur.
$coqueTitre = 'Modifier un client';
$coqueActif = 'clients';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
$statutContrat = $contrat["statut"] ?? "resilié";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <a class="lien-retour" href="<?= link ?>consommateur/all"><i class="fa-solid fa-arrow-left"></i> Retour aux clients</a>
    <header class="entete ligne">
        <div>
            <h1 class="titre"><?= $e($result["prenom"].' '.$result["nom"]) ?></h1>
            <p class="sous-titre">Client N° <?= (int)$result["id"] ?></p>
        </div>
        <span class="pill <?= $statutContrat === 'actif' ? 'ok' : '' ?>">Contrat <?= $e($statutContrat) ?></span>
    </header>

    <div class="grille">
        <section class="carte large">
            <h2 class="titre-carte">Informations</h2>
            <form class="formulaire" style="margin-top: 1rem" action="<?= link ?>consommateur/modifier/<?= (int)$result["id"] ?>" method="post">
                <label class="champ"><span class="libelle">Nom</span>
                    <input type="text" name="nom" value="<?= $e($result["nom"]) ?>">
                </label>
                <label class="champ"><span class="libelle">Prénom</span>
                    <input type="text" name="prenom" value="<?= $e($result["prenom"]) ?>">
                </label>
                <label class="champ entier"><span class="libelle">Adresse</span>
                    <input type="text" name="adresse" value="<?= $e($result["adresse"]) ?>">
                </label>
                <label class="champ entier"><span class="libelle">Email</span>
                    <input type="email" name="email" value="<?= $e($result["email"]) ?>">
                </label>
                <div class="entier actions fin">
                    <?php if ($contrat): ?>
                        <a class="bouton danger" href="<?= link ?>contrat/resilier/<?= (int)$contrat[0] ?>"><i class="fa-solid fa-xmark"></i> Résilier le contrat</a>
                    <?php endif; ?>
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                </div>
            </form>
        </section>

        <section class="carte large">
            <h2 class="titre-carte">Adresses et compteurs</h2>
            <ul class="lignes" style="margin: 1rem 0">
                <?php foreach ($adressesCompteur as $point): ?>
                    <li class="ligne">
                        <div class="ligne-texte">
                            <p class="ligne-nom"><?= $e($point['adresse']) ?></p>
                            <p class="ligne-detail"><?= $e($point['categorie']) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form class="formulaire bloc" action="<?= link ?>consommateur/ajouterAdresse/<?= (int)$result['id'] ?>" method="post">
                <p class="bloc-titre entier" style="margin-bottom: 0">Nouvelle adresse</p>
                <label class="champ entier"><span class="libelle">Adresse</span>
                    <input type="text" name="adresse" required>
                </label>
                <label class="champ entier"><span class="libelle">Catégorie</span>
                    <select name="categorie">
                        <option value="Résidentiel économique">Résidentiel économique</option>
                        <option value="Résidentiel standard">Résidentiel standard</option>
                        <option value="Résidentiel haut standing">Résidentiel haut standing</option>
                        <option value="Commercial">Commercial</option>
                        <option value="Industriel">Industriel</option>
                    </select>
                </label>
                <label class="champ"><span class="libelle">Index initial eau (m³)</span>
                    <input type="text" name="indexPrecendentEau" inputmode="decimal" required>
                </label>
                <label class="champ"><span class="libelle">Index initial électricité (kWh)</span>
                    <input type="text" name="indexPrecendentElec" inputmode="decimal" required>
                </label>
                <div class="entier actions fin">
                    <button class="bouton plein petit" type="submit"><i class="fa-solid fa-plus"></i> Ajouter cette adresse</button>
                </div>
            </form>
        </section>
    </div>
</main>
</body>
</html>
