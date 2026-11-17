<?php
// Règles de facturation (cahier des charges du TP).
// Les prix par tranche sont ceux du sujet ; ils servent aux factures mensuelles ET au bilan annuel.

const TVA_CONSOMMATION = 0.18;

// Seuils d'anomalie par défaut : chaque fournisseur peut définir les siens (Mon compte).
const SEUIL_ELECTRICITE_DEMO = 1000.0; // kWh par relevé
const SEUIL_EAU_DEMO = 100.0;          // m3 par relevé

// Prix unitaires selon la consommation mensuelle : [début, fin (null = sans limite), prix en DH].
// Chaque tranche est facturée à son propre prix (ex. 192 kWh = 100 × 0,82 + 50 × 0,92 + 42 × 1,10).
const TRANCHES_ELECTRICITE = array(
    array(0, 100, 0.82),
    array(100, 150, 0.92),
    array(150, null, 1.10),
);
const TRANCHES_EAU = array(
    array(0, 6, 2.37),
    array(6, 20, 7.39),
    array(20, 35, 10.98),
    array(35, null, 11.03),
);

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

// Détail du prix d'une consommation : une ligne par tranche utilisée.
// Renvoie [['de' => 0, 'a' => 100, 'quantite' => 100, 'prix' => 0.82, 'montant' => 82.0], ...]
function detailTranches($quantite, array $tranches)
{
    $lignes = array();
    foreach ($tranches as $tranche) {
        list($debut, $fin, $prix) = $tranche;
        $dansTranche = $fin === null ? max($quantite - $debut, 0) : min(max($quantite - $debut, 0), $fin - $debut);
        if ($dansTranche <= 0) continue;
        $lignes[] = array('de' => $debut, 'a' => $fin, 'quantite' => round($dansTranche, 4),
                          'prix' => $prix, 'montant' => round($dansTranche * $prix, 2));
    }
    return $lignes;
}

function coutEauMensuel($quantite)
{
    return array_sum(array_column(detailTranches($quantite, TRANCHES_EAU), 'montant'));
}

function coutElectriciteMensuel($quantite)
{
    return array_sum(array_column(detailTranches($quantite, TRANCHES_ELECTRICITE), 'montant'));
}

// Nombre de mois couverts par un relevé : du mois du dernier relevé valide au mois actuel (au moins 1).
// Ex. dernier relevé en septembre, nouveau en novembre (octobre oublié) = 2 mois.
function moisCouverts($precedente)
{
    $avant = new DateTimeImmutable(substr((string)$precedente['dateSaisie'], 0, 10));
    $maintenant = dateConsommation();
    $mois = ((int)$maintenant->format('Y') * 12 + (int)$maintenant->format('n'))
          - ((int)$avant->format('Y') * 12 + (int)$avant->format('n'));
    return max(1, $mois);
}

// $seuils : ['eau' => m³, 'electricite' => kWh] du fournisseur ; sans valeur, les seuils par défaut.
function calculerDeclaration($eau, $electricite, $precedente, $validationFournisseur = false, $seuils = null)
{
    if ($precedente === null) {
        return array('statut' => 'Référence initiale', 'eau' => null, 'electricite' => null,
            'motif' => '', 'ht' => null, 'ttc' => null);
    }
    // Les seuils sont mensuels : un relevé qui couvre plusieurs mois (mois oublié) a droit à autant de fois le seuil.
    $mois = moisCouverts($precedente);
    $seuilEau = (float)($seuils['eau'] ?? SEUIL_EAU_DEMO) * $mois;
    $seuilElectricite = (float)($seuils['electricite'] ?? SEUIL_ELECTRICITE_DEMO) * $mois;
    $quantiteEau = round($eau - (float)$precedente['indexEau'], 4);
    $quantiteElectricite = round($electricite - (float)$precedente['indexElectricite'], 4);
    $motifs = array();
    if ($quantiteEau < 0) $motifs[] = 'index eau en baisse';
    if ($quantiteElectricite < 0) $motifs[] = 'index élec. en baisse';
    if (!$validationFournisseur && $quantiteEau > $seuilEau) $motifs[] = 'eau > seuil';
    if (!$validationFournisseur && $quantiteElectricite > $seuilElectricite) $motifs[] = 'élec. > seuil';
    if ($motifs) {
        return array('statut' => 'Anomalie', 'eau' => $quantiteEau, 'electricite' => $quantiteElectricite,
        'motif' => implode('; ', $motifs), 'ht' => null, 'ttc' => null);
    }
    $detailEau = detailTranches($quantiteEau, TRANCHES_EAU);
    $detailElectricite = detailTranches($quantiteElectricite, TRANCHES_ELECTRICITE);
    $htEau = round(array_sum(array_column($detailEau, 'montant')), 2);
    $htElectricite = round(array_sum(array_column($detailElectricite, 'montant')), 2);
    $ht = round($htEau + $htElectricite, 2);
    return array('statut' => 'Validée', 'eau' => $quantiteEau, 'electricite' => $quantiteElectricite,
        'motif' => '', 'ht' => $ht, 'ttc' => round($ht * (1 + TVA_CONSOMMATION), 2),
        'htEau' => $htEau, 'htElectricite' => $htElectricite,
        'detailEau' => $detailEau, 'detailElectricite' => $detailElectricite);
}
