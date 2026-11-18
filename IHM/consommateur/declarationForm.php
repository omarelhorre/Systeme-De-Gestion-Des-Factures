<?php
// Déclarer ma consommation : le formulaire, sur sa propre page (comme "Ajouter un client").
// Variables fournies par traiterConsommation('ajouter') : $client, $adresses, $idAdresseCompteur,
// $precedente, $declarations, $statutContrat, $date, $erreur.
require_once ROOT.'IHM/commun/outils.php';
function h($valeur) { return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8'); }
$url = link . 'declaration/';
$coqueTitre = 'Déclarer ma consommation';
$coqueActif = 'releves';
$jourReleve = function ($jour) { return $jour ? date('d/m/Y', strtotime($jour)) : '—'; };

// Mêmes règles que le serveur :
//  - relevé régulier : du 18 à la fin du mois, un par compteur et par mois (le relevé de départ ne compte pas) ;
//  - relevé final (contrat en résiliation) : tout de suite, même si le relevé du mois est déjà envoyé,
//    sauf si un relevé de ce compteur attend la correction d'une anomalie ;
//  - contrat résilié : plus de relevé.
$enResiliation = $statutContrat === CONTRAT_EN_RESILIATION;
$releveDuMois = false;
$anomalieEnCours = false;
foreach ($declarations as $ligne) {
    if ((int)$ligne['idAdresseCompteur'] !== $idAdresseCompteur) continue;
    if ($ligne['statut'] !== 'Référence initiale' && substr($ligne['dateSaisie'], 0, 7) === $date->format('Y-m')) $releveDuMois = true;
    if (strpos($ligne['statut'], 'Anomalie') === 0) $anomalieEnCours = true;
}
if ($statutContrat === 'resilié') $bloque = 'Le contrat de ce compteur est résilié : il ne reçoit plus de relevé.';
else if ($enResiliation && $anomalieEnCours) $bloque = 'Votre relevé final est en cours de vérification par votre fournisseur. Le contrat se fermera dès qu\'il sera validé.';
else if (!$enResiliation && !declarationOuverte($date)) $bloque = 'La déclaration est ouverte du 18 au dernier jour du mois.';
else if (!$enResiliation && $releveDuMois) $bloque = 'Vous avez déjà envoyé le relevé de ce compteur ce mois-ci.';
else $bloque = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/consommateur/menu.php'; ?>

<main class="contenu entree">
    <a class="lien-retour" href="<?= $url ?>all"><i class="fa-solid fa-arrow-left"></i> Retour à mes relevés</a>
    <header class="entete">
        <h1 class="titre"><?= $enResiliation ? 'Relevé final' : 'Déclarer ma consommation' ?></h1>
        <p class="sous-titre"><?= $enResiliation
            ? 'Ce relevé crée votre facture finale et clôt le contrat de ce compteur.'
            : 'Saisissez les index de vos compteurs et joignez une photo de chacun.' ?></p>
    </header>

    <?php if ($erreur): ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?=h($erreur)?></span></p><?php endif; ?>

    <section class="carte">
        <div class="formulaire">
            <!-- Choisir le compteur recharge la page sur ce compteur -->
            <label class="champ entier"><span class="libelle">Compteur</span>
                <select onchange="window.location.href='<?= $url ?>ajouter/' + this.value">
                    <?php foreach ($adresses as $adresse): ?>
                    <option value="<?=h($adresse['id'])?>" <?=((int)$adresse['id'] === $idAdresseCompteur ? 'selected' : '')?>><?=h($adresse['adresse'].' — '.($adresse['quartier'] ?? $adresse['categorie']))?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- Le dernier relevé : la valeur et la photo de chaque compteur, pour comparer avant de saisir -->
            <div class="entier">
                <?php if ($precedente): ?>
                    <p class="libelle">Dernier relevé, du <?=$jourReleve($precedente['dateSaisie'])?> : <strong style="color: var(--texte)"><?=indexFr($precedente['indexEau'])?> m³</strong> d'eau · <strong style="color: var(--texte)"><?=indexFr($precedente['indexElectricite'])?> kWh</strong> d'électricité</p>
                    <?php if ($precedente['cheminCompteurEauImg'] || $precedente['cheminCompteurElectriciteImg']): ?>
                        <div class="photos photos-releve" style="margin-top: 0.75rem">
                            <figure><img src="<?=$url?>eau/<?=h($precedente['id'])?>" alt="Photo du compteur d'eau au dernier relevé"><figcaption><i class="fa-solid fa-droplet" style="color: var(--eau)"></i> Eau</figcaption></figure>
                            <figure><img src="<?=$url?>electricite/<?=h($precedente['id'])?>" alt="Photo du compteur d'électricité au dernier relevé"><figcaption><i class="fa-solid fa-bolt" style="color: var(--elec)"></i> Électricité</figcaption></figure>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="libelle">Premier relevé : il servira de référence et ne donnera pas lieu à une facture.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($bloque !== ''): ?>
            <p class="vide" style="margin-top: 1.5rem"><?= h($bloque) ?></p>
        <?php else: ?>
            <form class="formulaire" style="margin-top: 1.25rem" method="post" action="<?=$url?>ajouter" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_consommation'])?>">
                <input type="hidden" name="idAdresseCompteur" value="<?=h($idAdresseCompteur)?>">
                <label class="champ"><span class="libelle"><i class="fa-solid fa-droplet" style="color: var(--eau)"></i> Index d'eau (m³)</span>
                    <input type="text" inputmode="decimal" name="indexEau" required pattern="[0-9]+([.,][0-9]{1,4})?">
                </label>
                <label class="champ"><span class="libelle"><i class="fa-solid fa-bolt" style="color: var(--elec)"></i> Index d'électricité (kWh)</span>
                    <input type="text" inputmode="decimal" name="indexElectricite" required pattern="[0-9]+([.,][0-9]{1,4})?">
                </label>
                <label class="champ"><span class="libelle">Photo du compteur d'eau (JPEG, 3 Mo max)</span>
                    <input type="file" name="photoEau" accept="image/jpeg" required>
                </label>
                <label class="champ"><span class="libelle">Photo du compteur d'électricité (JPEG, 3 Mo max)</span>
                    <input type="file" name="photoElectricite" accept="image/jpeg" required>
                </label>
                <?php if ($enResiliation && $releveDuMois): ?>
                    <p class="note entier">Le relevé de ce mois est déjà envoyé : ce relevé final s'y ajoute pour clore le contrat.</p>
                <?php endif; ?>
                <div class="entier actions fin">
                    <a class="bouton contour" href="<?= $url ?>all">Annuler</a>
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
