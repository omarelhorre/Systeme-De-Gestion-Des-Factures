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

require_once ROOT.'Traitement/consommateur/reglesConsommation.php';

function estimerRegularisationAnnuelle($agentEau, $agentElectricite, $clientEau, $clientElectricite)
{
    $agent = 12 * (coutEauMensuel($agentEau / 12) + coutElectriciteMensuel($agentElectricite / 12));
    $client = 12 * (coutEauMensuel($clientEau / 12) + coutElectriciteMensuel($clientElectricite / 12));
    return round(max(0, $agent - $client) * 1.18, 2);
}
