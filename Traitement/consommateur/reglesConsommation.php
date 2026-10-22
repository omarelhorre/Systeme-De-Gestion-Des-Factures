<?php
// Tarifs et seuils de DEMONSTRATION : remplacer après validation par le professeur.
const TARIF_ELECTRICITE_DEMO = 1.20; // MAD / kWh
const TARIF_EAU_DEMO = 6.00;         // MAD / m3
const SEUIL_ELECTRICITE_DEMO = 1000.0; // kWh par relevé
const SEUIL_EAU_DEMO = 100.0;          // m3 par relevé
const TVA_CONSOMMATION = 0.18;

function dateConsommation()
{
    return new DateTimeImmutable('now', new DateTimeZone('Africa/Casablanca'));
}

function declarationOuverte($date)
{
    return (int)$date->format('j') >= 18;
}

function lireIndex($valeur)
{
    if (!is_string($valeur) || !preg_match('/^\d{1,11}(?:[.,]\d{1,4})?$/', trim($valeur))) {
        throw new InvalidArgumentException('Les index doivent être des nombres positifs avec au plus quatre décimales.');
    }
    return (float)str_replace(',', '.', trim($valeur));
}

function calculerDeclaration($eau, $electricite, $precedente)
{
    if ($precedente === null) {
        return array('statut' => 'Référence initiale', 'eau' => null, 'electricite' => null,
            'motif' => '', 'ht' => null, 'ttc' => null);
    }
    $quantiteEau = round($eau - (float)$precedente['indexEau'], 4);
    $quantiteElectricite = round($electricite - (float)$precedente['indexElectricite'], 4);
    $motifs = array();
    if ($quantiteEau < 0) $motifs[] = 'index eau en baisse';
    if ($quantiteElectricite < 0) $motifs[] = 'index élec. en baisse';
    if ($quantiteEau > SEUIL_EAU_DEMO) $motifs[] = 'eau > seuil';
    if ($quantiteElectricite > SEUIL_ELECTRICITE_DEMO) $motifs[] = 'élec. > seuil';
    if ($motifs) {
        return array('statut' => 'Anomalie', 'eau' => $quantiteEau, 'electricite' => $quantiteElectricite,
        'motif' => implode('; ', $motifs), 'ht' => null, 'ttc' => null);
    }
    $ht = round($quantiteEau * TARIF_EAU_DEMO + $quantiteElectricite * TARIF_ELECTRICITE_DEMO, 2);
    return array('statut' => 'Validée', 'eau' => $quantiteEau, 'electricite' => $quantiteElectricite,
        'motif' => '', 'ht' => $ht, 'ttc' => round($ht * (1 + TVA_CONSOMMATION), 2));
}
