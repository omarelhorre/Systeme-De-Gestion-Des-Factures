<?php
function verifierFournisseurFactures($db, $id)
{
    $stmt = mysqli_prepare($db, 'SELECT id FROM fournisseur WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) !== null;
}

function selectFacturesFournisseur($db, $fournisseur, $etat = '', $id = null)
{
    $sql = 'SELECT f.*, d.dateDebut, d.dateFin, d.prixTTC, u.nom, u.prenom,
                   u.email, c.adresse FROM facture f
            JOIN declaration d ON d.id = f.idDeclaration
            JOIN consommateur c ON c.id = d.idConsommateur
            JOIN utilisateur u ON u.id = c.id
            WHERE d.idFournisseur = ? AND c.idFournisseur = ?';
    if ($id !== null) {
        $sql .= ' AND f.id = ?';
    } elseif ($etat !== '') {
        $sql .= ' AND f.etat = ?';
    }
    $sql .= ' ORDER BY f.dateGeneration DESC, f.id DESC';
    $stmt = mysqli_prepare($db, $sql);
    if ($id !== null) {
        mysqli_stmt_bind_param($stmt, 'iii', $fournisseur, $fournisseur, $id);
    } elseif ($etat !== '') {
        mysqli_stmt_bind_param($stmt, 'iis', $fournisseur, $fournisseur, $etat);
    } else {
        mysqli_stmt_bind_param($stmt, 'ii', $fournisseur, $fournisseur);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function payerFactureFournisseur($db, $fournisseur, $id)
{
    $sql = "UPDATE facture f
            JOIN declaration d ON d.id = f.idDeclaration
            JOIN consommateur c ON c.id = d.idConsommateur
            SET f.etat = 'Payée', f.datePaiement = CURRENT_DATE()
            WHERE f.id = ? AND d.idFournisseur = ? AND c.idFournisseur = ?
              AND f.etat = 'Impayée' AND f.datePaiement IS NULL";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'iii', $id, $fournisseur, $fournisseur);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_affected_rows($stmt) === 1;
}
