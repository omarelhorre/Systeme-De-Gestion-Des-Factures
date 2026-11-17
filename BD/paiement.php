<?php
// Paiement en ligne d'une facture (CMI) : lecture de la facture à payer et enregistrement du paiement.

// La facture d'un client, avec son montant, seulement si elle est encore impayée.
// (Une facture n'existe que pour un relevé validé : après correction d'une anomalie, le relevé est validé
//  et sa facture créée ; c'est donc toujours une facture vérifiée.)
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

// Paiement accepté par CMI : la facture passe à "Payée", sans action du fournisseur.
// Ne fait rien si elle est déjà payée (CMI peut prévenir deux fois : retour du navigateur et callback).
function enregistrerPaiementEnLigne($db, $idFacture, $reference)
{
    $q = mysqli_prepare($db, "UPDATE facture SET etat = 'Payée', datePaiement = CURRENT_DATE(),
                                     modePaiement = 'en ligne', referencePaiement = ?
                              WHERE id = ? AND etat = 'Impayée'");
    mysqli_stmt_bind_param($q, 'si', $reference, $idFacture);
    mysqli_stmt_execute($q);
    return mysqli_stmt_affected_rows($q) === 1;
}
