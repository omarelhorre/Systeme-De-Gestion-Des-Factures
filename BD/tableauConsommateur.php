<?php
// Données du tableau de bord consommateur. Lecture seule, uniquement les données du client connecté.

function tableauConsommateurLire($db, $sql, $id)
{
    $q = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    $lignes = mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    mysqli_stmt_close($q);
    return $lignes;
}

// Ses 4 dernières factures, le total impayé, et ses 3 dernières réclamations
function tableauConsommateur($db, $id)
{
    $factures = tableauConsommateurLire($db,
        "SELECT f.id, f.etat, f.dateGeneration, f.datePaiement, f.modePaiement, d.id AS idDeclaration, d.prixTTC, a.adresse
         FROM facture f JOIN declaration d ON d.id=f.idDeclaration JOIN adresseCompteur a ON a.id=d.idAdresseCompteur
         WHERE d.idConsommateur=? ORDER BY f.dateGeneration DESC, f.id DESC LIMIT 4", $id);
    $impayes = tableauConsommateurLire($db,
        "SELECT COUNT(*) AS nombre, COALESCE(SUM(d.prixTTC),0) AS montant
         FROM facture f JOIN declaration d ON d.id=f.idDeclaration
         WHERE d.idConsommateur=? AND f.etat='Impayée'", $id)[0];
    $reclamations = tableauConsommateurLire($db,
        "SELECT idReclamation, description, dateCreation, statut, reponse
         FROM reclamation WHERE id=? ORDER BY dateCreation DESC, idReclamation DESC LIMIT 3", $id);
    return array('factures' => $factures, 'impayes' => $impayes, 'reclamations' => $reclamations);
}
