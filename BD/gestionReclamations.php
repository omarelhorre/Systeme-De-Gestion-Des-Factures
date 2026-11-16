<?php
// Lire la base existante : aucune nouvelle table n'est nécessaire.
function gestionReclamationsLire($db, $fournisseur, $id = null, $verrouiller = false)
{
    $sql = "SELECT r.*, u.nom, u.prenom, u.email, c.adresse,
            CASE
                WHEN EXISTS (SELECT 1 FROM fuiteInterne t WHERE t.idReclamation = r.idReclamation) THEN 'Fuite interne'
                WHEN EXISTS (SELECT 1 FROM fuiteExterne t WHERE t.idReclamation = r.idReclamation) THEN 'Fuite externe'
                WHEN EXISTS (SELECT 1 FROM reclamationFacture t WHERE t.idReclamation = r.idReclamation) THEN 'Facture'
                WHEN EXISTS (SELECT 1 FROM reclamationFuite t WHERE t.idReclamation = r.idReclamation) THEN 'Fuite (non précisée)'
                ELSE 'Autre'
            END AS typeReclamation
            FROM reclamation r
            JOIN consommateur c ON c.id = r.id
            JOIN utilisateur u ON u.id = c.id
            WHERE c.idFournisseur = ?";
    if ($id !== null) {
        $sql .= ' AND r.idReclamation = ?';
    }
    $sql .= ' ORDER BY r.dateCreation DESC, r.idReclamation DESC';
    if ($verrouiller) {
        $sql .= ' FOR UPDATE';
    }
    $requete = mysqli_prepare($db, $sql);
    if ($id === null) {
        mysqli_stmt_bind_param($requete, 'i', $fournisseur);
    } else {
        mysqli_stmt_bind_param($requete, 'ii', $fournisseur, $id);
    }
    mysqli_stmt_execute($requete);
    $lignes = mysqli_fetch_all(mysqli_stmt_get_result($requete), MYSQLI_ASSOC);
    mysqli_stmt_close($requete);
    return $lignes;
}

function gestionReclamationsSauver($db, $fournisseur, $id, $statut, $reponse)
{
    $sql = 'UPDATE reclamation r
            JOIN consommateur c ON c.id = r.id
            SET r.statut = ?, r.reponse = ?
            WHERE r.idReclamation = ? AND c.idFournisseur = ?';
    $requete = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($requete, 'ssii', $statut, $reponse, $id, $fournisseur);
    mysqli_stmt_execute($requete);
    mysqli_stmt_close($requete);
}

function gestionReclamationsEstFournisseur($db, $id)
{
    $requete = mysqli_prepare($db, 'SELECT id FROM fournisseur WHERE id = ?');
    mysqli_stmt_bind_param($requete, 'i', $id);
    mysqli_stmt_execute($requete);
    $existe = mysqli_fetch_assoc(mysqli_stmt_get_result($requete)) !== null;
    mysqli_stmt_close($requete);
    return $existe;
}
