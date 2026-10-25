<?php

function facturesDuConsommateur(mysqli $db, int $idConsommateur): array
{
    $requete = mysqli_prepare($db, 'SELECT f.id, f.dateGeneration FROM facture f JOIN declaration d ON d.id = f.idDeclaration WHERE d.idConsommateur = ? ORDER BY f.dateGeneration DESC, f.id DESC');
    mysqli_stmt_bind_param($requete, 'i', $idConsommateur);
    mysqli_stmt_execute($requete);
    $factures = mysqli_fetch_all(mysqli_stmt_get_result($requete), MYSQLI_ASSOC);
    mysqli_stmt_close($requete);
    return $factures;
}

function historiqueReclamationsConsommateur(mysqli $db, int $idConsommateur): array
{
    $sql = "SELECT r.idReclamation, r.description, r.dateCreation, r.statut, r.reponse,
        CASE WHEN fi.idReclamation IS NOT NULL THEN 'fuite_interne'
             WHEN fe.idReclamation IS NOT NULL THEN 'fuite_externe'
             WHEN rf.idReclamation IS NOT NULL THEN 'facture'
             WHEN a.idReclamation IS NOT NULL THEN 'autre'
             ELSE 'autre' END AS typeReclamation,
        rf.id AS idFacture
        FROM reclamation r
        LEFT JOIN reclamationFuite fuite ON fuite.idReclamation = r.idReclamation
        LEFT JOIN fuiteInterne fi ON fi.idReclamation = fuite.idReclamation
        LEFT JOIN fuiteExterne fe ON fe.idReclamation = fuite.idReclamation
        LEFT JOIN reclamationFacture rf ON rf.idReclamation = r.idReclamation
        LEFT JOIN autre a ON a.idReclamation = r.idReclamation
        WHERE r.id = ?
        ORDER BY r.dateCreation DESC, r.idReclamation DESC";
    $requete = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($requete, 'i', $idConsommateur);
    mysqli_stmt_execute($requete);
    $reclamations = mysqli_fetch_all(mysqli_stmt_get_result($requete), MYSQLI_ASSOC);
    mysqli_stmt_close($requete);
    return $reclamations;
}

function enregistrerReclamationConsommateur(mysqli $db, int $idConsommateur, string $type, string $description, ?int $idFacture): void
{
    mysqli_begin_transaction($db);
    try {
        if ($type === 'facture') {
            $requete = mysqli_prepare($db, 'SELECT f.id FROM facture f JOIN declaration d ON d.id = f.idDeclaration WHERE f.id = ? AND d.idConsommateur = ? LIMIT 1');
            mysqli_stmt_bind_param($requete, 'ii', $idFacture, $idConsommateur);
            mysqli_stmt_execute($requete);
            $facture = mysqli_fetch_assoc(mysqli_stmt_get_result($requete));
            mysqli_stmt_close($requete);
            if (!$facture) {
                throw new InvalidArgumentException('Choisissez une de vos factures.');
            }
        }

        $statut = 'En attente';
        $requete = mysqli_prepare($db, 'INSERT INTO reclamation (id, description, statut) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($requete, 'iss', $idConsommateur, $description, $statut);
        mysqli_stmt_execute($requete);
        $idReclamation = mysqli_insert_id($db);
        mysqli_stmt_close($requete);

        if ($type === 'fuite_interne' || $type === 'fuite_externe') {
            $requete = mysqli_prepare($db, 'INSERT INTO reclamationFuite (idReclamation) VALUES (?)');
            mysqli_stmt_bind_param($requete, 'i', $idReclamation);
            mysqli_stmt_execute($requete);
            mysqli_stmt_close($requete);
            $sql = $type === 'fuite_interne'
                ? 'INSERT INTO fuiteInterne (idReclamation) VALUES (?)'
                : 'INSERT INTO fuiteExterne (idReclamation) VALUES (?)';
            $requete = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($requete, 'i', $idReclamation);
        } elseif ($type === 'facture') {
            $requete = mysqli_prepare($db, 'INSERT INTO reclamationFacture (idReclamation, id) VALUES (?, ?)');
            mysqli_stmt_bind_param($requete, 'ii', $idReclamation, $idFacture);
        } else {
            $requete = mysqli_prepare($db, 'INSERT INTO autre (idReclamation) VALUES (?)');
            mysqli_stmt_bind_param($requete, 'i', $idReclamation);
        }
        mysqli_stmt_execute($requete);
        mysqli_stmt_close($requete);
        mysqli_commit($db);
    } catch (Throwable $exception) {
        mysqli_rollback($db);
        throw $exception;
    }
}
