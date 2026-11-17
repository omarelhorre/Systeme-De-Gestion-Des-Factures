<?php
// Petites fonctions d'affichage partagées par les pages (aucun accès à la base ici).

if (!function_exists('miniCourbe')) {
    // Mini-courbe SVG (zone 72 x 24) pour une liste de valeurs.
    // Électricité : en escalier (angles droits). Eau : douce (formes rondes).
    function miniCourbe(array $valeurs, $escalier)
    {
        $valeurs = array_values($valeurs);
        $n = count($valeurs);
        if ($n < 2) return '';
        $max = max($valeurs);
        $min = min($valeurs);
        $ecart = ($max - $min) ?: 1;
        $points = array();
        foreach ($valeurs as $i => $v) {
            $points[] = array(round($i * 72 / ($n - 1), 1), round(22 - (($v - $min) / $ecart) * 20, 1));
        }
        $d = 'M'.$points[0][0].' '.$points[0][1];
        for ($i = 1; $i < $n; $i++) {
            list($x, $y) = $points[$i];
            list($xAvant, $yAvant) = $points[$i - 1];
            $milieu = round(($xAvant + $x) / 2, 1);
            $d .= $escalier
                ? ' H'.$milieu.' V'.$y.' H'.$x
                : ' C'.$milieu.' '.$yAvant.' '.$milieu.' '.$y.' '.$x.' '.$y;
        }
        return '<svg viewBox="0 0 72 24" preserveAspectRatio="none" aria-hidden="true"><path d="'.$d.'"/></svg>';
    }

    // Variation en % entre les deux dernières valeurs : "+12 %" (hausse = rouge), "−6 %" (baisse = vert)
    function variationConsommation(array $valeurs)
    {
        $valeurs = array_values($valeurs);
        $n = count($valeurs);
        if ($n < 2 || $valeurs[$n - 2] <= 0) return '';
        $pourcent = round(($valeurs[$n - 1] - $valeurs[$n - 2]) / $valeurs[$n - 2] * 100);
        if ($pourcent == 0) return '<span class="variation">0 %</span>';
        return '<span class="variation '.($pourcent > 0 ? 'hausse' : 'baisse').'">'.($pourcent > 0 ? '+' : '−').abs($pourcent).' %</span>';
    }

    function nombreFr($valeur, $decimales = 0)
    {
        return number_format((float)$valeur, $decimales, ',', "\u{00A0}");
    }

    // Graphique de consommation : électricité et eau sur UN seul tracé.
    // Les deux unités (kWh, m³) ne se comparent pas : chaque série est ramenée à un indice
    // (100 = sa propre moyenne sur la période), ce qui permet un seul axe. L'infobulle donne les vraies valeurs.
    // $points : [['mois' => 'AAAA-MM', 'electricite' => kWh, 'eau' => m³], ...] dans l'ordre chronologique.
    // $hauteur : hauteur de la zone de tracé (ex. '12rem').
    function graphiqueConsommation(array $points, $hauteur = '12rem')
    {
        $points = array_values($points);
        $n = count($points);
        if ($n < 2) return '';
        $moisLongs = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
        $moisCourts = array('', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.');
        $e = function ($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); };

        // Indice de chaque mois par rapport à la moyenne de la série
        $indices = array();
        foreach (array('electricite', 'eau') as $serie) {
            $valeurs = array_map('floatval', array_column($points, $serie));
            $moyenne = array_sum($valeurs) / $n;
            $indices[$serie] = array_map(function ($v) use ($moyenne) { return $moyenne > 0 ? $v / $moyenne * 100 : 100; }, $valeurs);
        }

        // Axe : de part et d'autre de 100, par pas réguliers
        $tous = array_merge($indices['electricite'], $indices['eau']);
        $etendue = max(max($tous) - min($tous), 1);
        $pas = $etendue <= 40 ? 10 : ($etendue <= 120 ? 25 : 50);
        $bas = floor(min(min($tous), 100) / $pas) * $pas;
        $haut = ceil(max(max($tous), 100) / $pas) * $pas;
        if ($haut == $bas) $haut = $bas + $pas;
        $y = function ($indice) use ($bas, $haut) { return round(100 - ($indice - $bas) / ($haut - $bas) * 100, 2); };
        $x = function ($i) use ($n) { return round($i * 100 / ($n - 1), 2); };

        // Courbe douce à travers les points (coordonnées en % de la zone)
        $trace = function (array $serie) use ($x, $y, $n) {
            $d = 'M'.$x(0).' '.$y($serie[0]);
            for ($i = 1; $i < $n; $i++) {
                $milieu = round(($x($i - 1) + $x($i)) / 2, 2);
                $d .= ' C'.$milieu.' '.$y($serie[$i - 1]).' '.$milieu.' '.$y($serie[$i]).' '.$x($i).' '.$y($serie[$i]);
            }
            return $d;
        };

        // Étiquettes au bout des courbes : écartées si les deux courbes finissent trop près l'une de l'autre
        $finElec = $y(end($indices['electricite']));
        $finEau = $y(end($indices['eau']));
        $ecartMin = 16;   // en % de la hauteur
        if (abs($finElec - $finEau) < $ecartMin) {
            $milieu = ($finElec + $finEau) / 2;
            $elecAuDessus = $finElec <= $finEau;
            $finElec = round($milieu + ($elecAuDessus ? -1 : 1) * $ecartMin / 2, 2);
            $finEau = round($milieu + ($elecAuDessus ? 1 : -1) * $ecartMin / 2, 2);
        }

        // Données lues par app.js pour l'infobulle
        $donnees = array();
        foreach ($points as $i => $p) {
            list($annee, $m) = explode('-', $p['mois']);
            $donnees[] = array(
                'mois' => $moisLongs[(int)$m].' '.$annee,
                'x' => $x($i),
                'elec' => $y($indices['electricite'][$i]), 'eau' => $y($indices['eau'][$i]),
                'kwh' => nombreFr($p['electricite']).' kWh', 'm3' => nombreFr($p['eau'], 1).' m³',
            );
        }

        ob_start(); ?>
        <figure class="graphe" data-graphe="<?= $e(json_encode($donnees)) ?>" style="--hauteur-graphe: <?= $e($hauteur) ?>">
            <figcaption class="graphe-legende">
                <span><span class="cle elec"></span>Électricité</span>
                <span><span class="cle eau"></span>Eau</span>
                <span class="note">Indice 100 = moyenne de chaque service sur la période</span>
            </figcaption>
            <div class="graphe-corps">
                <div class="graphe-axe" aria-hidden="true">
                    <?php for ($t = $bas; $t <= $haut; $t += $pas) { ?>
                        <span style="top: <?= $y($t) ?>%"><?= (int)$t ?></span>
                    <?php } ?>
                </div>
                <div class="graphe-zone" tabindex="0" aria-label="Consommation mois par mois : survolez ou utilisez les flèches pour lire les valeurs">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                        <?php for ($t = $bas; $t <= $haut; $t += $pas) { ?>
                            <line class="<?= $t == 100 ? 'reference' : '' ?>" x1="0" x2="100" y1="<?= $y($t) ?>" y2="<?= $y($t) ?>"/>
                        <?php } ?>
                        <path class="trait-elec" d="<?= $trace($indices['electricite']) ?>"/>
                        <path class="trait-eau" d="<?= $trace($indices['eau']) ?>"/>
                    </svg>
                    <!-- étiquettes directes au bout de chaque courbe -->
                    <span class="graphe-etiquette elec" style="top: <?= $finElec ?>%">Électricité</span>
                    <span class="graphe-etiquette eau" style="top: <?= $finEau ?>%">Eau</span>
                    <span class="graphe-guide" hidden></span>
                    <span class="graphe-point elec" hidden></span>
                    <span class="graphe-point eau" hidden></span>
                    <div class="graphe-bulle" role="status" hidden></div>
                </div>
            </div>
            <div class="graphe-mois" aria-hidden="true">
                <?php foreach ($points as $i => $p) { ?>
                    <span style="left: <?= $x($i) ?>%"><?= $moisCourts[(int)substr($p['mois'], 5, 2)] ?></span>
                <?php } ?>
            </div>
            <!-- les mêmes valeurs en tableau, pour les lecteurs d'écran -->
            <table class="sr-only">
                <caption>Consommation par mois</caption>
                <tr><th>Mois</th><th>Électricité</th><th>Eau</th></tr>
                <?php foreach ($donnees as $d) { ?>
                    <tr><td><?= $e($d['mois']) ?></td><td><?= $e($d['kwh']) ?></td><td><?= $e($d['m3']) ?></td></tr>
                <?php } ?>
            </table>
        </figure>
        <?php return ob_get_clean();
    }

    // Nombre de jours entiers entre une date (AAAA-MM-JJ) et aujourd'hui
    function joursDepuis($date)
    {
        $debut = new DateTimeImmutable(substr((string)$date, 0, 10));
        $aujourdhui = new DateTimeImmutable(date('Y-m-d'));
        return (int)$debut->diff($aujourdhui)->format('%r%a');
    }

    // Âge lisible : "aujourd'hui", "hier", "il y a 3 j", "il y a 2 mois"
    function ilYa($date)
    {
        $jours = joursDepuis($date);
        if ($jours <= 0) return "aujourd'hui";
        if ($jours === 1) return 'hier';
        if ($jours < 60) return 'il y a '.$jours.' j';
        return 'il y a '.intdiv($jours, 30).' mois';
    }

    // Statut d'une réclamation : libellé uniforme et couleur du point
    // (en attente = ambre, en cours = bleu, traitée = vert). La base contient parfois "En Attente".
    function statutReclamation($statut)
    {
        $cle = strtolower(strtr(trim((string)$statut), array('É' => 'e', 'é' => 'e')));
        if (in_array($cle, array('traitee', 'traite', 'traitees'), true)) return array('Traitée', 'ok');
        if ($cle === 'en cours') return array('En cours', 'encours');
        if ($cle === 'en attente') return array('En attente', 'attente');
        return array((string)$statut, '');
    }

    // Délai de paiement d'une facture (jours après sa date) : sert seulement à l'affichage du retard.
    if (!defined('DELAI_PAIEMENT_JOURS')) define('DELAI_PAIEMENT_JOURS', 30);

    // État d'une facture : "Payée", "à régler avant le 19/11" (ambre) ou "en retard de 28 j" (rouge)
    function etatFacture($etat, $dateFacture)
    {
        if ($etat === 'Payée') return array('Payée', 'ok');
        $echeance = (new DateTimeImmutable(substr((string)$dateFacture, 0, 10)))->modify('+'.DELAI_PAIEMENT_JOURS.' days');
        $retard = joursDepuis($echeance->format('Y-m-d'));
        if ($retard > 0) return array('en retard de '.$retard.' j', 'alerte');
        return array('à régler avant le '.$echeance->format('d/m'), 'attente');
    }
}
