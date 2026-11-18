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
    </header>

    <?php if ($erreur): ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?=h($erreur)?></span></p><?php endif; ?>

    <!-- Choisir le compteur recharge la page sur ce compteur -->
    <div class="barre-outils">
        <label class="outil outil-large"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <select aria-label="Compteur" onchange="window.location.href='<?= $url ?>ajouter/' + this.value">
                <?php foreach ($adresses as $adresse): ?>
                <option value="<?=h($adresse['id'])?>" <?=((int)$adresse['id'] === $idAdresseCompteur ? 'selected' : '')?>><?=h($adresse['adresse'].' — '.($adresse['quartier'] ?? $adresse['categorie']))?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <?php
    // Une ligne par service : [champ index, champ photo, libellé, unité, icône, couleur, dernier index, route de la photo]
    $services = array(
        array('indexEau', 'photoEau', 'Eau', 'm³', 'fa-droplet', 'var(--eau)',
              $precedente ? $precedente['indexEau'] : null, $precedente && $precedente['cheminCompteurEauImg'] ? $url.'eau/'.(int)$precedente['id'] : null),
        array('indexElectricite', 'photoElectricite', 'Électricité', 'kWh', 'fa-bolt', 'var(--elec)',
              $precedente ? $precedente['indexElectricite'] : null, $precedente && $precedente['cheminCompteurElectriciteImg'] ? $url.'electricite/'.(int)$precedente['id'] : null),
    );
    $saisie = $bloque === '';
    ?>
    <!-- Le relevé comme une facture : dernier index, nouvel index, consommation calculée pendant la saisie -->
    <form class="liste" method="post" action="<?=$url?>ajouter" enctype="multipart/form-data">
        <?php if ($saisie): ?>
        <input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_consommation'])?>">
        <input type="hidden" name="idAdresseCompteur" value="<?=h($idAdresseCompteur)?>">
        <?php endif; ?>
        <table class="tableau tableau-releve">
            <thead>
                <tr><th>Compteur</th><th>Dernier index</th><th>Dernière photo</th><th>Nouvel index</th><th class="fin">Consommation</th><th>Nouvelle photo</th></tr>
            </thead>
            <tbody>
                <?php foreach ($services as list($champ, $champPhoto, $libelle, $unite, $icone, $couleur, $dernier, $photo)): ?>
                <tr>
                    <td><span class="fort"><i class="fa-solid <?= $icone ?>" style="color: <?= $couleur ?>" aria-hidden="true"></i> <?= $libelle ?></span></td>
                    <td class="nombre">
                        <?php if ($dernier !== null): ?>
                            <?= indexFr($dernier) ?> <?= $unite ?>
                            <small>le <?= $jourReleve($precedente['dateSaisie']) ?></small>
                        <?php else: ?><span class="note">Premier relevé</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($photo): ?>
                            <button type="button" class="vignette lien-photo" data-photo="<?= h($photo) ?>" data-titre="<?= h($libelle.' — relevé du '.$jourReleve($precedente['dateSaisie'])) ?>" aria-label="Agrandir la photo <?= h($libelle) ?> du dernier relevé">
                                <img src="<?= h($photo) ?>" alt=""></button>
                        <?php else: ?><span class="note">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($saisie): ?>
                            <label class="saisie-index"><input type="text" inputmode="decimal" name="<?= $champ ?>" required pattern="[0-9]+([.,][0-9]{1,4})?"
                                   data-precedent="<?= $dernier !== null ? h($dernier) : '' ?>" data-unite="<?= $unite ?>" aria-label="Nouvel index <?= $libelle ?> (<?= $unite ?>)"><span><?= $unite ?></span></label>
                        <?php else: ?><span class="note">—</span><?php endif; ?>
                    </td>
                    <td class="fin nombre"><span class="consommation-calculee note">—</span></td>
                    <td>
                        <?php if ($saisie): ?>
                            <div class="photo-nouvelle">
                                <button type="button" class="vignette lien-photo" hidden aria-label="Agrandir la nouvelle photo <?= h($libelle) ?>"><img alt=""></button>
                                <label class="fichier"><input type="file" name="<?= $champPhoto ?>" accept="image/jpeg" required>
                                    <i class="fa-solid fa-camera" aria-hidden="true"></i><span>Joindre la photo</span></label>
                            </div>
                        <?php else: ?><span class="note">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$saisie): ?>
            <p class="note" style="margin-top: 1rem"><?= h($bloque) ?></p>
        <?php else: ?>
            <div class="pied-releve">
                <span></span>
                <div class="actions">
                    <a class="bouton contour" href="<?= $url ?>all">Annuler</a>
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                </div>
            </div>
        <?php endif; ?>
    </form>

    <!-- Photo d'un relevé précédent, ouverte par "voir la photo" -->
    <dialog class="visionneuse" id="visionneuse">
        <div class="visionneuse-entete"><span id="visionneuse-titre"></span>
            <button type="button" class="bouton-rond" onclick="this.closest('dialog').close()" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button></div>
        <img id="visionneuse-image" alt="">
    </dialog>
</main>
</body>
</html>
