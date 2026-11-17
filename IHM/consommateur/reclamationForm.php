<?php
// Nouvelle réclamation : le formulaire, sur sa propre page (comme "Ajouter un client").
// Variables fournies par traiterReclamationsConsommateur('ajouter') : $factures, $valeurs, $erreur.
require_once ROOT.'IHM/commun/outils.php';
if (!function_exists('texteReclamation')) {
    function texteReclamation($valeur): string
    {
        return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
    }
}
$libellesTypes = [
    'fuite_interne' => 'Fuite interne',
    'fuite_externe' => 'Fuite externe',
    'facture' => 'Facture',
    'autre' => 'Autre',
];
$coqueTitre = 'Nouvelle réclamation';
$coqueActif = 'reclamations';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/consommateur/menu.php'; ?>

<main class="contenu entree">
    <a class="lien-retour" href="<?= link ?>reclamation/all"><i class="fa-solid fa-arrow-left"></i> Retour à mes réclamations</a>
    <header class="entete">
        <h1 class="titre">Nouvelle réclamation</h1>
        <p class="sous-titre">Votre fournisseur est prévenu et vous répond ici et par email.</p>
    </header>

    <?php if ($erreur): ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= texteReclamation($erreur) ?></span></p><?php endif; ?>

    <section class="carte">
        <form class="formulaire" method="post" action="<?= link ?>reclamation/ajouter">
            <input type="hidden" name="csrf" value="<?= texteReclamation($_SESSION['csrf_reclamations']) ?>">
            <label class="champ"><span class="libelle">Type</span>
                <select name="type" required>
                    <option value="">Choisir un type</option>
                    <?php foreach ($libellesTypes as $code => $libelle): ?>
                        <option value="<?= texteReclamation($code) ?>" <?= $valeurs['type'] === $code ? 'selected' : '' ?>><?= texteReclamation($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="champ"><span class="libelle">Facture concernée (type « Facture »)</span>
                <select name="idFacture">
                    <option value="">Aucune</option>
                    <?php foreach ($factures as $facture): ?>
                        <option value="<?= (int)$facture['id'] ?>" <?= $valeurs['idFacture'] === (string)$facture['id'] ? 'selected' : '' ?>>N° <?= (int)$facture['id'] ?> du <?= texteReclamation(date('d/m/Y', strtotime($facture['dateGeneration']))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="champ entier"><span class="libelle">Description</span>
                <textarea name="description" rows="5" maxlength="1000" required placeholder="Décrivez le problème : où, depuis quand, ce que vous constatez."><?= texteReclamation($valeurs['description']) ?></textarea>
            </label>
            <div class="entier actions fin">
                <a class="bouton contour" href="<?= link ?>reclamation/all">Annuler</a>
                <button class="bouton accent" type="submit"><i class="fa-solid fa-paper-plane"></i> Envoyer</button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
