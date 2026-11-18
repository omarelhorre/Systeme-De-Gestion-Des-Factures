<?php

function factureAPayer($db, $idFacture, $idConsommateur = null)
{
    $sql = "SELECT f.id, f.etat, d.prixTTC, d.idConsommateur, d.idFournisseur, u.nom, u.prenom, u.email
            FROM facture f
            JOIN declaration d ON d.id = f.idDeclaration
            JOIN utilisateur u ON u.id = d.idConsommateur
            WHERE f.id = ? AND f.etat = 'Impayée' AND d.statut = 'Validée'";
    if ($idConsommateur !== null) $sql .= ' AND d.idConsommateur = ?';
    $q = mysqli_prepare($db, $sql);
    if ($idConsommateur !== null) mysqli_stmt_bind_param($q, 'ii', $idFacture, $idConsommateur);
    else mysqli_stmt_bind_param($q, 'i', $idFacture);
    mysqli_stmt_execute($q);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($q)) ?: null;
}

function enregistrerPaiementEnLigne($db, $idFacture, $reference)
{
    $q = mysqli_prepare($db, "UPDATE facture SET etat = 'Payée', datePaiement = CURRENT_DATE(),
                                     modePaiement = 'en ligne', referencePaiement = ?
                              WHERE id = ? AND etat = 'Impayée'");
    mysqli_stmt_bind_param($q, 'si', $reference, $idFacture);
    mysqli_stmt_execute($q);
    return mysqli_stmt_affected_rows($q) === 1;
}
