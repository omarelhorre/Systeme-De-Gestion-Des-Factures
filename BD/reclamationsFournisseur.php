<?php
function estFournisseurReclamation($db, $id)
{
    $requete = mysqli_prepare($db, 'SELECT id FROM fournisseur WHERE id = ?');
    mysqli_stmt_bind_param($requete, 'i', $id);
    mysqli_stmt_execute($requete);
    $existe = mysqli_fetch_assoc(mysqli_stmt_get_result($requete)) !== null;
    mysqli_stmt_close($requete);
    return $existe;
}

function listeReclamationsFournisseur($db, $idFournisseur)
{
    $sql = 'SELECT r.idReclamation, r.description, r.dateCreation,
                   r.statut, r.reponse, u.nom, u.prenom
            FROM reclamation r
            JOIN consommateur c ON c.id = r.id
            JOIN utilisateur u ON u.id = c.id
            WHERE c.idFournisseur = ?
            ORDER BY r.dateCreation DESC, r.idReclamation DESC';
    $requete = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($requete, 'i', $idFournisseur);
    mysqli_stmt_execute($requete);
    $reclamations = mysqli_fetch_all(mysqli_stmt_get_result($requete), MYSQLI_ASSOC);
    mysqli_stmt_close($requete);
    return $reclamations;
}
