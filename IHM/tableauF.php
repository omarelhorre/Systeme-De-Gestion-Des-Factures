<?php
// Tableau de bord fournisseur.
require_once ROOT.'IHM/commun/outils.php';
// $tableau est préparé par redirectToIndexF() (voir BD/tableauFournisseur.php).
$chiffres = $tableau['chiffres'];
$consommation = $tableau['consommation'];
$coqueActif = 'tableau';
$coqueTitre = 'Tableau de bord';

// Petites fonctions d'affichage, propres à cette page
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
$nombre = function ($valeur, $decimales = 0) { return number_format((float)$valeur, $decimales, ',', "\u{00A0}"); };

$moisCourts = array('', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.');
$moisLongs = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');

$afficherCourbes = count($consommation) >= 2;   // pas de courbe vide : il faut au moins 2 mois
if ($afficherCourbes) {
    $dernier = end($consommation);
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
    <header class="entete">
        <h1 class="titre">Bonjour, <?= $e($_SESSION['user']['prenom'] ?? '') ?></h1>
        <p class="sous-titre"><?= (int)date('j').' '.$moisLongs[(int)date('n')].' '.date('Y') ?></p>
    </header>

    <div class="grille">

        <!-- Widget principal : l'argent à encaisser -->
        <section class="carte large">
            <h2 class="titre-carte">À encaisser</h2>
            <p class="chiffre geant"><?= $nombre($chiffres['montantImpaye'], 2) ?><span class="unite">DH</span></p>
            <div class="carte-pied">
                <div>
                    <p class="legende"><strong><?= (int)$chiffres['facturesImpayees'] ?></strong> facture<?= $chiffres['facturesImpayees'] > 1 ? 's' : '' ?> impayée<?= $chiffres['facturesImpayees'] > 1 ? 's' : '' ?></p>
                    <p class="legende"><strong><?= $nombre($chiffres['encaisseCeMois'], 2) ?> DH</strong> encaissés ce mois</p>
                </div>
                <a class="bouton accent" href="<?= link ?>facture/all">
                    Voir les factures <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </section>

        <section class="carte">
            <div class="carte-entete">
                <h2 class="titre-carte">Clients</h2>
                <a class="bouton contour petit" href="<?= link ?>consommateur/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <p class="chiffre"><?= (int)$chiffres['clients'] ?></p>
            <p class="legende">sous contrat</p>
        </section>

        <section class="carte">
            <div class="carte-entete">
                <h2 class="titre-carte">Anomalies</h2>
                <a class="bouton contour petit" href="<?= link ?>declaration/anomalies">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <p class="chiffre <?= $chiffres['anomalies'] > 0 ? 'alerte' : '' ?>"><?= (int)$chiffres['anomalies'] ?></p>
            <p class="legende">relevé<?= $chiffres['anomalies'] > 1 ? 's' : '' ?> à corriger</p>
        </section>

        <!-- Consommation : électricité et eau sur un seul graphique, valeurs au survol -->
        <section class="carte large haute">
            <div class="carte-entete">
                <div>
                    <h2 class="titre-carte">Consommation</h2>
                    <?php if ($afficherCourbes) { ?>
                        <p class="legende" style="margin-top: 0.125rem"><?= $moisLongs[(int)substr($dernier['mois'], 5, 2)] ?> : <strong><?= $nombre($dernier['electricite']) ?> kWh</strong> · <strong><?= $nombre($dernier['eau'], 1) ?> m³</strong></p>
                    <?php } ?>
                </div>
                <a class="bouton contour petit" href="<?= link ?>annuelle/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if ($afficherCourbes) { ?>
                <?= graphiqueConsommation($consommation, '15rem') ?>
            <?php } else { ?>
                <p class="vide">Les courbes apparaîtront après deux mois de relevés validés.</p>
            <?php } ?>
        </section>

        <section class="carte large">
            <div class="carte-entete">
                <h2 class="titre-carte">Réclamations ouvertes</h2>
                <a class="bouton contour petit" href="<?= link ?>reclamation/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if ($tableau['reclamations']) { ?>
                <ul class="lignes">
                    <?php foreach ($tableau['reclamations'] as $reclamation) { ?>
                        <li class="ligne">
                            <div class="ligne-texte">
                                <p class="ligne-nom"><?= $e($reclamation['prenom'].' '.$reclamation['nom']) ?></p>
                                <p class="ligne-detail"><?= $e($reclamation['description']) ?></p>
                            </div>
                            <?php list($libelle, $classe) = statutReclamation($reclamation['statut']); ?>
                            <span class="note" style="flex-shrink: 0; white-space: nowrap"><?= ilYa($reclamation['dateCreation']) ?></span>
                            <span class="etat <?= $classe ?>"><?= $libelle ?></span>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p class="vide">Aucune réclamation ouverte.</p>
            <?php } ?>
        </section>

        <section class="carte large">
            <div class="carte-entete">
                <h2 class="titre-carte">Dernières factures</h2>
                <a class="bouton contour petit" href="<?= link ?>facture/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if ($tableau['factures']) { ?>
                <ul class="lignes">
                    <?php foreach ($tableau['factures'] as $facture) { ?>
                        <li class="ligne">
                            <div class="ligne-texte">
                                <p class="ligne-nom"><?= $e($facture['prenom'].' '.$facture['nom']) ?></p>
                                <p class="ligne-detail">N° <?= (int)$facture['id'] ?> · <?= $e(date('d/m/Y', strtotime($facture['dateGeneration']))) ?></p>
                            </div>
                            <span class="ligne-montant"><?= $nombre($facture['prixTTC'], 2) ?> DH</span>
                            <?php list($libelle, $classe) = etatFacture($facture['etat'], $facture['dateGeneration']); ?><span class="etat <?= $classe ?>"><?= $libelle ?></span>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p class="vide">Aucune facture pour le moment.</p>
            <?php } ?>
        </section>

    </div>
</main>
</body>
</html>
