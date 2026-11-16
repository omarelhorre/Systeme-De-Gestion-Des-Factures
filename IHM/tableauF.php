<?php
// Tableau de bord fournisseur.
// $tableau est préparé par redirectToIndexF() (voir BD/tableauFournisseur.php).
$chiffres = $tableau['chiffres'];
$consommation = $tableau['consommation'];
$coqueActif = 'tableau';

// Petites fonctions d'affichage, propres à cette page
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
$nombre = function ($valeur, $decimales = 0) { return number_format((float)$valeur, $decimales, ',', "\u{00A0}"); };

$moisCourts = array('', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.');
$moisLongs = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');

// Dessine une courbe SVG (zone de 300 x 80) à partir d'une liste de valeurs.
// Électricité : en escalier (angles droits). Eau : lissée (formes rondes).
$courbe = function (array $valeurs, $escalier) {
    $n = count($valeurs);
    $max = max($valeurs) ?: 1;
    $points = array();
    foreach ($valeurs as $i => $v) {
        $points[] = array(round($i * 300 / ($n - 1), 1), round(76 - ($v / $max) * 68, 1));
    }
    $d = 'M'.$points[0][0].' '.$points[0][1];
    for ($i = 1; $i < $n; $i++) {
        list($x, $y) = $points[$i];
        list($xAvant, $yAvant) = $points[$i - 1];
        $milieu = round(($xAvant + $x) / 2, 1);
        $d .= $escalier
            ? ' H'.$milieu.' V'.$y.' H'.$x                                   // palier, puis marche
            : ' C'.$milieu.' '.$yAvant.' '.$milieu.' '.$y.' '.$x.' '.$y;     // courbe douce
    }
    return array('trait' => $d, 'aplat' => $d.' V80 H0 Z');
};

$afficherCourbes = count($consommation) >= 2;   // pas de courbe vide : il faut au moins 2 mois
if ($afficherCourbes) {
    $dernier = end($consommation);
    $traceElec = $courbe(array_map('floatval', array_column($consommation, 'electricite')), true);
    $traceEau = $courbe(array_map('floatval', array_column($consommation, 'eau')), false);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord · Fatourti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=Inter:wght@400;500;600&family=Outfit:wght@600;800&display=swap" rel="stylesheet">
    <link href="<?= dir ?>/public/style/tokens.css" rel="stylesheet">
    <link href="<?= dir ?>/public/style/app.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
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
                <a class="bouton-rond" href="<?= link ?>consommateur/all" aria-label="Voir les clients"><i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <p class="chiffre"><?= (int)$chiffres['clients'] ?></p>
            <p class="legende">sous contrat</p>
        </section>

        <section class="carte">
            <div class="carte-entete">
                <h2 class="titre-carte">Anomalies</h2>
                <a class="bouton-rond" href="<?= link ?>declaration/anomalies" aria-label="Corriger les anomalies"><i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <p class="chiffre <?= $chiffres['anomalies'] > 0 ? 'alerte' : '' ?>"><?= (int)$chiffres['anomalies'] ?></p>
            <p class="legende">relevé<?= $chiffres['anomalies'] > 1 ? 's' : '' ?> à corriger</p>
        </section>

        <!-- Consommation : électricité en escalier, eau en courbe -->
        <section class="carte large haute">
            <div class="carte-entete">
                <h2 class="titre-carte">Consommation</h2>
                <a class="bouton-rond" href="<?= link ?>annuelle/all" aria-label="Voir le bilan annuel"><i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if ($afficherCourbes) { ?>
                <div class="courbes">
                    <div class="courbe">
                        <div class="courbe-entete">
                            <span class="courbe-nom elec"><i class="fa-solid fa-bolt"></i> Électricité</span>
                            <span class="courbe-valeur"><?= $nombre($dernier['electricite']) ?><span class="unite">kWh</span></span>
                        </div>
                        <svg class="elec" viewBox="0 0 300 80" preserveAspectRatio="none" aria-hidden="true">
                            <path class="aplat" d="<?= $traceElec['aplat'] ?>"/>
                            <path class="trait" d="<?= $traceElec['trait'] ?>"/>
                        </svg>
                    </div>
                    <div class="courbe">
                        <div class="courbe-entete">
                            <span class="courbe-nom eau"><i class="fa-solid fa-droplet"></i> Eau</span>
                            <span class="courbe-valeur"><?= $nombre($dernier['eau'], 1) ?><span class="unite">m³</span></span>
                        </div>
                        <svg class="eau" viewBox="0 0 300 80" preserveAspectRatio="none" aria-hidden="true">
                            <path class="aplat" d="<?= $traceEau['aplat'] ?>"/>
                            <path class="trait" d="<?= $traceEau['trait'] ?>"/>
                        </svg>
                    </div>
                    <div class="mois">
                        <?php foreach ($consommation as $ligne) { ?>
                            <span><?= $moisCourts[(int)substr($ligne['mois'], 5, 2)] ?></span>
                        <?php } ?>
                    </div>
                </div>
            <?php } else { ?>
                <p class="vide">Les courbes apparaîtront après deux mois de relevés validés.</p>
            <?php } ?>
        </section>

        <section class="carte large">
            <div class="carte-entete">
                <h2 class="titre-carte">Réclamations ouvertes</h2>
                <a class="bouton-rond" href="<?= link ?>reclamation/all" aria-label="Voir les réclamations"><i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if ($tableau['reclamations']) { ?>
                <ul class="lignes">
                    <?php foreach ($tableau['reclamations'] as $reclamation) { ?>
                        <li class="ligne">
                            <div class="ligne-texte">
                                <p class="ligne-nom"><?= $e($reclamation['prenom'].' '.$reclamation['nom']) ?></p>
                                <p class="ligne-detail"><?= $e($reclamation['description']) ?></p>
                            </div>
                            <span class="pill"><?= $e($reclamation['statut']) ?></span>
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
                            <span class="pill <?= $facture['etat'] === 'Payée' ? 'ok' : '' ?>"><?= $e($facture['etat']) ?></span>
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
