<?php
// Données du tableau de bord fournisseur.
// Lecture seule : aucune écriture en base, uniquement les clients du fournisseur connecté.

require_once __DIR__.'/facturesFournisseur.php';
require_once __DIR__.'/reclamationsFournisseur.php';

// Exécute une requête préparée qui ne prend que l'identifiant du fournisseur (répété $fois)
function tableauFournisseurLire($db, $sql, $fournisseur, $fois = 1)
{
    $q = mysqli_prepare($db, $sql);
    $params = array_fill(0, $fois, $fournisseur);
    mysqli_stmt_bind_param($q, str_repeat('i', $fois), ...$params);
    mysqli_stmt_execute($q);
    $lignes = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    mysqli_stmt_close($q);
    return $lignes;
}

// Les chiffres clés : clients, factures, anomalies, réclamations
function tableauFournisseurChiffres($db, $fournisseur)
{
    $sql = "SELECT
        (SELECT COUNT(*) FROM consommateur WHERE idFournisseur = ?) AS clients,
        (SELECT COUNT(*) FROM facture f JOIN declaration d ON d.id = f.idDeclaration
            WHERE d.idFournisseur = ? AND f.etat = 'Impayée') AS facturesImpayees,
        (SELECT COALESCE(SUM(d.prixTTC), 0) FROM facture f JOIN declaration d ON d.id = f.idDeclaration
            WHERE d.idFournisseur = ? AND f.etat = 'Impayée') AS montantImpaye,
        (SELECT COALESCE(SUM(d.prixTTC), 0) FROM facture f JOIN declaration d ON d.id = f.idDeclaration
            WHERE d.idFournisseur = ? AND f.etat = 'Payée'
              AND f.datePaiement >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')) AS encaisseCeMois,
        (SELECT COUNT(*) FROM declaration
            WHERE idFournisseur = ? AND statut LIKE 'Anomalie%') AS anomalies,
        (SELECT COUNT(*) FROM reclamation r JOIN consommateur c ON c.id = r.id
            WHERE c.idFournisseur = ? AND r.statut <> 'Traitée') AS reclamationsOuvertes";
    return tableauFournisseurLire($db, $sql, $fournisseur, 6)[0];
}

// Consommation validée par mois (6 derniers mois).
// La base garde des index cumulés : la consommation d'un relevé = index - index précédent du même compteur.
function tableauFournisseurConsommation($db, $fournisseur)
{
    $sql = "SELECT DATE_FORMAT(x.dateSaisie, '%Y-%m') AS mois,
                   SUM(x.eau) AS eau, SUM(x.electricite) AS electricite
            FROM (
                SELECT d.dateSaisie, d.statut,
                       d.indexEau - LAG(d.indexEau) OVER (PARTITION BY d.idAdresseCompteur ORDER BY d.dateSaisie, d.id) AS eau,
                       d.indexElectricite - LAG(d.indexElectricite) OVER (PARTITION BY d.idAdresseCompteur ORDER BY d.dateSaisie, d.id) AS electricite
                FROM declaration d
                WHERE d.idFournisseur = ? AND d.statut NOT LIKE 'Anomalie%'
            ) x
            WHERE x.eau IS NOT NULL AND x.statut = 'Validée'
              AND x.dateSaisie >= DATE_SUB(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
            GROUP BY mois
            ORDER BY mois";
    return tableauFournisseurLire($db, $sql, $fournisseur);
}

// Tout ce que la page affiche, en un seul appel
function tableauFournisseur($db, $fournisseur)
{
    $reclamations = array_filter(
        listeReclamationsFournisseur($db, $fournisseur),
        function ($r) { return $r['statut'] !== 'Traitée'; }
    );
    return array(
        'chiffres'     => tableauFournisseurChiffres($db, $fournisseur),
        'consommation' => tableauFournisseurConsommation($db, $fournisseur),
        'factures'     => array_slice(selectFacturesFournisseur($db, $fournisseur), 0, 4),
        'reclamations' => array_slice($reclamations, 0, 3),
    );
}
