<?php
require_once ROOT.'BD/annuelle.php';

function consommationsDeclareesAnnee($db, $annee)
{
    $totaux = [];
    $precedents = [];
    foreach (declarationsPourCalculAnnuel($db, $annee) as $ligne) {
        $adresse = (int)$ligne['idAdresseCompteur'];
        $client = (int)$ligne['idConsommateur'];
        if ($ligne['statut'] === 'Validée' && substr($ligne['dateSaisie'], 0, 4) === (string)$annee
            && isset($precedents[$adresse])) {
            if (!isset($totaux[$client])) $totaux[$client] = ['eau' => 0.0, 'electricite' => 0.0];
            $totaux[$client]['eau'] += max(0, (float)$ligne['indexEau'] - (float)$precedents[$adresse]['indexEau']);
            $totaux[$client]['electricite'] += max(0, (float)$ligne['indexElectricite'] - (float)$precedents[$adresse]['indexElectricite']);
        }
        $precedents[$adresse] = $ligne;
    }
    return $totaux;
}

function coutEauMensuel($quantite)
{
    return min($quantite, 6) * 2.37
        + min(max($quantite - 6, 0), 14) * 7.39
        + min(max($quantite - 20, 0), 15) * 10.98
        + max($quantite - 35, 0) * 11.03;
}

function coutElectriciteMensuel($quantite)
{
    return min($quantite, 100) * 0.82
        + min(max($quantite - 100, 0), 50) * 0.92
        + max($quantite - 150, 0) * 1.10;
}

function estimerRegularisationAnnuelle($agentEau, $agentElectricite, $clientEau, $clientElectricite)
{
    // Estimation : répartir les totaux annuels sur douze mois, appliquer les tranches du sujet, puis la TVA de 18 %.
    $agent = 12 * (coutEauMensuel($agentEau / 12) + coutElectriciteMensuel($agentElectricite / 12));
    $client = 12 * (coutEauMensuel($clientEau / 12) + coutElectriciteMensuel($clientElectricite / 12));
    return round(max(0, $agent - $client) * 1.18, 2);
}
