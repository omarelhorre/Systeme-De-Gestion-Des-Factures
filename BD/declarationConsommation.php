<?php
// Requêtes de la déclaration : toutes les données variables passent par mysqli_prepare.
function clientConnecte($db, $id)
{
    $sql = 'SELECT c.id, c.idFournisseur, c.adresse, u.nom, u.prenom, u.email FROM consommateur c JOIN utilisateur u ON u.id=c.id WHERE c.id=?';
    $q = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    return $row;
}

function adressesDuClient($db, $id)
{
    $q = mysqli_prepare($db, 'SELECT id, adresse, categorie FROM adresseCompteur WHERE idConsommateur=? ORDER BY id');
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
}

function verrouillerClient($db, $id)
{
    $q = mysqli_prepare($db, 'SELECT id FROM consommateur WHERE id=? FOR UPDATE');
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    return $row !== null;
}

function declarationDuMois($db, $idAdresseCompteur, $premierJour, $jourSuivant)
{
    $q = mysqli_prepare($db, 'SELECT id FROM declaration WHERE idAdresseCompteur=? AND dateSaisie>=? AND dateSaisie<? LIMIT 1');
    mysqli_stmt_bind_param($q, 'iss', $idAdresseCompteur, $premierJour, $jourSuivant);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    return $row !== null;
}

function derniereDeclarationValide($db, $idAdresseCompteur)
{
    // Les chemins des photos servent à les afficher au client pendant sa nouvelle saisie.
    $q = mysqli_prepare($db, "SELECT id, indexEau, indexElectricite, dateSaisie, cheminCompteurEauImg, cheminCompteurElectriciteImg FROM declaration WHERE idAdresseCompteur=? AND statut IN ('Référence initiale','Validée') ORDER BY dateSaisie DESC, id DESC LIMIT 1");
    mysqli_stmt_bind_param($q, 'i', $idAdresseCompteur);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    return $row ?: null;
}

// Seuils d'anomalie choisis par un fournisseur : ['eau' => m³, 'electricite' => kWh]
function seuilsFournisseur($db, $idFournisseur)
{
    $q = mysqli_prepare($db, "SELECT seuilEau, seuilElectricite FROM fournisseur WHERE id=?");
    mysqli_stmt_bind_param($q, 'i', $idFournisseur);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    return $row ? array('eau' => (float)$row['seuilEau'], 'electricite' => (float)$row['seuilElectricite']) : null;
}

function enregistrerDeclaration($db, $fournisseur, $client, $idAdresseCompteur, $photoEau, $photoElectricite, $eau, $electricite, $date, $statut, $debut, $fin, $ht, $ttc)
{
    $sql = 'INSERT INTO declaration (idFournisseur,idConsommateur,idAdresseCompteur,cheminCompteurEauImg,cheminCompteurElectriciteImg,indexEau,indexElectricite,dateSaisie,statut,dateDebut,dateFin,prixHT,prixTTC) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
    $q = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($q, 'iiissddssssdd', $fournisseur, $client, $idAdresseCompteur, $photoEau, $photoElectricite, $eau, $electricite, $date, $statut, $debut, $fin, $ht, $ttc);
    mysqli_stmt_execute($q);
    $id = mysqli_insert_id($db);
    mysqli_stmt_close($q);
    return $id;
}


function enregistrerFacture($db, $declaration, $chemin, $date)
{
    $etat = 'Impayée';
    $q = mysqli_prepare($db, 'INSERT INTO facture (idDeclaration,etat,datePaiement,cheminGenere,dateGeneration) VALUES (?,?,NULL,?,?)');
    mysqli_stmt_bind_param($q, 'isss', $declaration, $etat, $chemin, $date);
    mysqli_stmt_execute($q);
    $id = mysqli_insert_id($db);
    mysqli_stmt_close($q);
    return $id;
}

function declarationsDuClient($db, $id)
{
    $q = mysqli_prepare($db, 'SELECT d.*, a.adresse, a.categorie, f.id AS idFacture, f.etat, f.datePaiement FROM declaration d JOIN adresseCompteur a ON a.id=d.idAdresseCompteur LEFT JOIN facture f ON f.idDeclaration=d.id WHERE d.idConsommateur=? ORDER BY d.dateSaisie DESC,d.id DESC');
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    mysqli_stmt_close($q);
    return $rows;
}

function fichierDuClient($db, $id, $declaration, $type)
{
    $colonne = $type === 'facture' ? 'f.cheminGenere' : ($type === 'eau' ? 'd.cheminCompteurEauImg' : 'd.cheminCompteurElectriciteImg');
    $sql = 'SELECT '.$colonne.' AS chemin FROM declaration d LEFT JOIN facture f ON f.idDeclaration=d.id WHERE d.idConsommateur=? AND d.id=?';
    $q = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($q, 'ii', $id, $declaration);
    mysqli_stmt_execute($q);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    mysqli_stmt_close($q);
    return $row ? $row['chemin'] : null;
}
