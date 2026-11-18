<?php
// Modification d'un client. Variables fournies par modify() : $result, $contrat, $contrats, $adressesCompteur.
$coqueTitre = 'Modifier un client';
$coqueActif = 'clients';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };

// Un contrat par adresse de compteur : on range les contrats par adresse pour les afficher à côté.
$contratParAdresse = array();
$nbActifs = 0;
$nbEnResiliation = 0;
foreach ($contrats as $ligneContrat) {
    if ($ligneContrat['statut'] === 'actif') $nbActifs++;
    if ($ligneContrat['statut'] === CONTRAT_EN_RESILIATION) $nbEnResiliation++;
    // si une adresse a plusieurs contrats, on garde l'actif, sinon le plus récent
    $cle = (int)$ligneContrat['idAdresseCompteur'];
    if (!isset($contratParAdresse[$cle]) || in_array($ligneContrat['statut'], array('actif', CONTRAT_EN_RESILIATION), true)) $contratParAdresse[$cle] = $ligneContrat;
}
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
        <span class="pill <?= $nbActifs > 0 ? 'ok' : '' ?>"><?= $nbActifs > 0 ? $nbActifs.' contrat'.($nbActifs > 1 ? 's' : '').' actif'.($nbActifs > 1 ? 's' : '') : 'Aucun contrat actif' ?></span>
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
                <label class="champ"><span class="libelle">Numéro d'identité</span>
                    <input type="text" name="cin" placeholder="BL175578" maxlength="10" pattern="[A-Za-z]{1,2}[0-9]{4,8}" value="<?= $e($result["cin"] ?? '') ?>">
                </label>
                <label class="champ"><span class="libelle">Téléphone</span>
                    <input type="tel" name="telephone" placeholder="0612345678" maxlength="16" pattern="(\+212|0)[5-7][0-9 ]{8,12}" value="<?= $e($result["telephone"] ?? '') ?>">
                </label>
                <div class="entier actions fin">
                    <?php if ($nbActifs + $nbEnResiliation > 0): ?>
                        <a class="bouton danger" href="<?= link ?>contrat/resilier/<?= (int)$result["id"] ?>"><i class="fa-solid fa-xmark"></i> Résilier <?= $nbActifs > 1 ? 'des contrats' : 'le contrat' ?></a>
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
                            <p class="ligne-detail">Compteur N° <?= (int)$point['id'] ?> · <?= $e(($point['quartier'] ?? 'Sans quartier').' · '.$point['categorie']) ?></p>
                        </div>
                        <?php $contratAdresse = $contratParAdresse[(int)$point['id']] ?? null; ?>
                        <?php if ($contratAdresse === null) { ?>
                            <span class="pill">Sans contrat</span>
                        <?php } else { ?>
                            <?php $classeContrat = $contratAdresse['statut'] === 'actif' ? 'ok' : ($contratAdresse['statut'] === CONTRAT_EN_RESILIATION ? 'attente' : ''); ?>
                            <span class="etat <?= $classeContrat ?>">Contrat N° <?= (int)$contratAdresse['id'] ?> <?= $e($contratAdresse['statut'] === CONTRAT_EN_RESILIATION ? 'en résiliation (relevé final attendu)' : $contratAdresse['statut']) ?></span>
                        <?php } ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <form class="formulaire bloc" action="<?= link ?>consommateur/ajouterAdresse/<?= (int)$result['id'] ?>" method="post">
                <p class="bloc-titre entier" style="margin-bottom: 0">Nouvelle adresse</p>
                <label class="champ entier"><span class="libelle">Adresse</span>
                    <input type="text" name="adresse" required>
                </label>
                <label class="champ entier"><span class="libelle">Catégorie et quartier</span>
                    <select name="quartier" required>
                        <option value="" disabled selected>Choisir un quartier</option>
                        <?php foreach (quartiersParCategorie() as $categorie => $liste): ?>
                        <optgroup label="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>">
                            <?php foreach ($liste as $q): ?><option value="<?= (int)$q['id'] ?>"><?= htmlspecialchars($q['nom'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
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
