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
}
