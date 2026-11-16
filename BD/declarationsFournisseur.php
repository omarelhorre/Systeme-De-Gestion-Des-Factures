<?php

function fournisseurAutoriseDeclarations(mysqli $db, int $id): bool
{
    $q = $db->prepare('SELECT id FROM fournisseur WHERE id=?');
    $q->bind_param('i', $id);
    $q->execute();
    return $q->get_result()->fetch_assoc() !== null;
}

function anomaliesDuFournisseur(mysqli $db, int $fournisseur): array
{
    $sql = "SELECT d.id,d.dateSaisie,d.statut,d.indexEau,d.indexElectricite,
                   u.nom,u.prenom,c.id AS idConsommateur,a.adresse
            FROM declaration d
            JOIN consommateur c ON c.id=d.idConsommateur
            JOIN adresseCompteur a ON a.id=d.idAdresseCompteur
            JOIN utilisateur u ON u.id=c.id
            WHERE d.idFournisseur=? AND c.idFournisseur=? AND d.statut LIKE 'Anomalie%'
            ORDER BY d.dateSaisie DESC,d.id DESC";
    $q = $db->prepare($sql);
    $q->bind_param('ii', $fournisseur, $fournisseur);
    $q->execute();
    return $q->get_result()->fetch_all(MYSQLI_ASSOC);
}

function anomalieDuFournisseur(mysqli $db, int $fournisseur, int $id, bool $verrouiller = false): ?array
{
    $sql = "SELECT d.*,u.nom,u.prenom,u.email,a.adresse
            FROM declaration d
            JOIN consommateur c ON c.id=d.idConsommateur
            JOIN adresseCompteur a ON a.id=d.idAdresseCompteur
            JOIN utilisateur u ON u.id=c.id
            WHERE d.id=? AND d.idFournisseur=? AND c.idFournisseur=? AND d.statut LIKE 'Anomalie%'";
    if ($verrouiller) $sql .= ' FOR UPDATE';
    $q = $db->prepare($sql);
    $q->bind_param('iii', $id, $fournisseur, $fournisseur);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
}

function lecturePrecedenteFournisseur(mysqli $db, int $idAdresseCompteur, string $date): ?array
{
    $sql = "SELECT id,indexEau,indexElectricite,dateSaisie FROM declaration
            WHERE idAdresseCompteur=? AND dateSaisie<? AND statut IN ('Référence initiale','Validée')
            ORDER BY dateSaisie DESC,id DESC LIMIT 1";
    $q = $db->prepare($sql);
    $q->bind_param('is', $idAdresseCompteur, $date);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
}

function existeDeclarationUlterieure(mysqli $db, int $idAdresseCompteur, string $date): bool
{
    $q = $db->prepare('SELECT id FROM declaration WHERE idAdresseCompteur=? AND dateSaisie>? LIMIT 1');
    $q->bind_param('is', $idAdresseCompteur, $date);
    $q->execute();
    return $q->get_result()->fetch_assoc() !== null;
}

function factureDeDeclarationExiste(mysqli $db, int $declaration): bool
{
    $q = $db->prepare('SELECT id FROM facture WHERE idDeclaration=? LIMIT 1');
    $q->bind_param('i', $declaration);
    $q->execute();
    return $q->get_result()->fetch_assoc() !== null;
}

function mettreAJourAnomalie(mysqli $db, int $fournisseur, int $id, float $eau, float $electricite,
    string $debut, string $fin, float $ht, float $ttc): bool
{
    $statut = 'Validée';
    $sql = "UPDATE declaration SET indexEau=?,indexElectricite=?,statut=?,
            dateDebut=?,dateFin=?,prixHT=?,prixTTC=?
            WHERE id=? AND idFournisseur=? AND statut LIKE 'Anomalie%'";
    $q = $db->prepare($sql);
    $q->bind_param('ddsssddii', $eau, $electricite, $statut, $debut, $fin, $ht, $ttc, $id, $fournisseur);
    $q->execute();
    return $q->affected_rows === 1;
}
