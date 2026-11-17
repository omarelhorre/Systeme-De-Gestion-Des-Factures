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
