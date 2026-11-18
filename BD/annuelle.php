<?php
function fichierAnnuelDeAgent($db, $agent, $annee)
{
    $q = mysqli_prepare($db, 'SELECT fichierChemin FROM ConsommationAnuelle WHERE agentDepo=? AND annee=? LIMIT 1');
    mysqli_stmt_bind_param($q, 'ii', $agent, $annee);
    mysqli_stmt_execute($q);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
}

function clientsAnnuels($db)
{
    $result = mysqli_query($db, 'SELECT id FROM consommateur');
    return array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'id');
}

function clientsPourAgent($db, $annee)
{
    $q = mysqli_prepare($db, "SELECT c.id, u.nom, u.prenom, c.cin, c.telephone,
               CONCAT(uf.prenom, ' ', uf.nom) AS fournisseur,
               (SELECT COUNT(*) FROM adresseCompteur a WHERE a.idConsommateur = c.id) AS compteurs,
               (SELECT GROUP_CONCAT(DISTINCT qu.nom ORDER BY qu.nom SEPARATOR ', ')
                  FROM adresseCompteur a JOIN quartier qu ON qu.id = a.idQuartier WHERE a.idConsommateur = c.id) AS quartiers,
               ca.ConsommationEau AS releveEau, ca.ConsommationElectricite AS releveElectricite
        FROM consommateur c
        JOIN utilisateur u ON u.id = c.id
        JOIN utilisateur uf ON uf.id = c.idFournisseur
        LEFT JOIN ConsommationAnuelle ca ON ca.idConsommateur = c.id AND ca.annee = ?
        ORDER BY u.nom, u.prenom");
    mysqli_stmt_bind_param($q, 'i', $annee);
    mysqli_stmt_execute($q);
    return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
}

function declarationsPourCalculAnnuel($db, $annee)
{
    $fin = ($annee + 1).'-01-01';
    $q = mysqli_prepare($db, "SELECT idConsommateur,idAdresseCompteur,dateSaisie,indexEau,indexElectricite,statut FROM declaration WHERE dateSaisie<? AND statut IN ('Référence initiale','Validée') ORDER BY idAdresseCompteur,dateSaisie,id");
    mysqli_stmt_bind_param($q, 's', $fin);
    mysqli_stmt_execute($q);
    return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
}

function enregistrerFichierAnnuel($db, $chemin)
{
    $q = mysqli_prepare($db, 'INSERT INTO fichierAnuelle(fichierChemin) VALUES (?)');
    mysqli_stmt_bind_param($q, 's', $chemin);
    mysqli_stmt_execute($q);
}

function enregistrerConsommationAnnuelle($db, $client, $chemin, $agent, $annee, $eau, $electricite, $date)
{
    $q = mysqli_prepare($db, 'INSERT INTO ConsommationAnuelle(idConsommateur,fichierChemin,agentDepo,annee,ConsommationEau,ConsommationElectricite,dateSaisie) VALUES (?,?,?,?,?,?,?)');
    mysqli_stmt_bind_param($q, 'isiidds', $client, $chemin, $agent, $annee, $eau, $electricite, $date);
    mysqli_stmt_execute($q);
    return mysqli_insert_id($db);
}

function enregistrerRegularisationAnnuelle($db, $idAnnuel, $eau, $electricite, $montant, $dateLimite)
{
    $q = mysqli_prepare($db, 'INSERT INTO regularisationAnnuelle(idConsommationAnuelle,differenceEau,differenceElectricite,montantEstime,dateLimite) VALUES (?,?,?,?,?)');
    mysqli_stmt_bind_param($q, 'iddds', $idAnnuel, $eau, $electricite, $montant, $dateLimite);
    mysqli_stmt_execute($q);
}

function bilanAnnuelFournisseur($db, $fournisseur, $annee)
{
    $q = mysqli_prepare($db, 'SELECT c.id AS idConsommateur,u.nom,u.prenom,u.email,a.id AS idAnnuel,a.annee,a.ConsommationEau,a.ConsommationElectricite,a.dateSaisie,r.id AS idRegularisation,r.differenceEau,r.differenceElectricite,r.montantEstime,r.dateLimite,r.dateDernierEnvoi,r.nombreEnvois,r.dateReglement FROM consommateur c JOIN utilisateur u ON u.id=c.id LEFT JOIN ConsommationAnuelle a ON a.idConsommateur=c.id AND a.annee=? LEFT JOIN regularisationAnnuelle r ON r.idConsommationAnuelle=a.id WHERE c.idFournisseur=? ORDER BY u.nom,u.prenom');
    mysqli_stmt_bind_param($q, 'ii', $annee, $fournisseur);
    mysqli_stmt_execute($q);
    return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
}

function rappelsAnnuelsEnAttente($db)
{
    $sql = 'SELECT r.*,a.annee,a.idConsommateur,u.nom,u.prenom,u.email FROM regularisationAnnuelle r JOIN ConsommationAnuelle a ON a.id=r.idConsommationAnuelle JOIN utilisateur u ON u.id=a.idConsommateur WHERE r.dateReglement IS NULL AND r.dateLimite>=CURRENT_DATE() AND (r.dateDernierEnvoi IS NULL OR r.dateDernierEnvoi<CURRENT_DATE()) ORDER BY r.id';
    return mysqli_fetch_all(mysqli_query($db, $sql), MYSQLI_ASSOC);
}

function reserverRappelAnnuel($db, $id)
{
    $q = mysqli_prepare($db, 'UPDATE regularisationAnnuelle SET dateDernierEnvoi=CURRENT_DATE(),nombreEnvois=nombreEnvois+1 WHERE id=? AND dateReglement IS NULL AND dateLimite>=CURRENT_DATE() AND (dateDernierEnvoi IS NULL OR dateDernierEnvoi<CURRENT_DATE())');
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
    return mysqli_stmt_affected_rows($q) === 1;
}

function libererRappelAnnuel($db, $id)
{
    $q = mysqli_prepare($db, 'UPDATE regularisationAnnuelle SET dateDernierEnvoi=NULL,nombreEnvois=nombreEnvois-1 WHERE id=? AND dateDernierEnvoi=CURRENT_DATE()');
    mysqli_stmt_bind_param($q, 'i', $id);
    mysqli_stmt_execute($q);
}

function reglerRegularisationAnnuelle($db, $fournisseur, $id)
{
    $q = mysqli_prepare($db, 'UPDATE regularisationAnnuelle r JOIN ConsommationAnuelle a ON a.id=r.idConsommationAnuelle JOIN consommateur c ON c.id=a.idConsommateur SET r.dateReglement=CURRENT_DATE() WHERE r.id=? AND c.idFournisseur=? AND r.dateReglement IS NULL');
    mysqli_stmt_bind_param($q, 'ii', $id, $fournisseur);
    mysqli_stmt_execute($q);
    return mysqli_stmt_affected_rows($q) === 1;
}
