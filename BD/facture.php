<?php
include_once(ROOT.'BD/connexion.php');
function facturesListe($userId, $fournisseur, $clientId = 0) {
    global $link;
    $sql = 'SELECT f.id, f.etat, f.datePaiement, f.dateGeneration, d.dateDebut, d.dateFin, d.prixHT, d.prixTTC, c.id AS clientId, u.nom, u.prenom FROM facture f JOIN declaration d ON d.id=f.idDeclaration JOIN consommateur c ON c.id=d.idConsommateur JOIN utilisateur u ON u.id=c.id WHERE ';
    $sql .= $fournisseur ? 'c.idFournisseur=? AND d.idFournisseur=?' : 'c.id=?';
    if ($fournisseur && $clientId) $sql .= ' AND c.id=?';
    $sql .= ' ORDER BY f.dateGeneration DESC, f.id DESC';
    $stmt = mysqli_prepare($link, $sql);
    if ($fournisseur && $clientId) mysqli_stmt_bind_param($stmt, 'iii', $userId, $userId, $clientId);
    elseif ($fournisseur) mysqli_stmt_bind_param($stmt, 'ii', $userId, $userId);
    else mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}
function facturePayerEspeces($id, $fournisseurId) {
    global $link;
    $stmt = mysqli_prepare($link, "UPDATE facture f JOIN declaration d ON d.id=f.idDeclaration JOIN consommateur c ON c.id=d.idConsommateur SET f.etat='Payée', f.datePaiement=CURRENT_DATE() WHERE f.id=? AND c.idFournisseur=? AND d.idFournisseur=? AND f.datePaiement IS NULL AND f.etat='Impayée'");
    mysqli_stmt_bind_param($stmt, 'iii', $id, $fournisseurId, $fournisseurId);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_affected_rows($stmt) === 1;
}
