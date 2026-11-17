<?php
// DONNÉES DE DÉMONSTRATION — à lancer une seule fois, dans le conteneur :
//   docker exec php_backend_v2 php /var/www/html/mock/seed-omarelhorre.php
//
// Pour le client omarelhorre@gmail.com, sur sa première adresse de compteur :
//   - relevé de référence en mai, puis un relevé validé par mois de juin à octobre 2026
//   - une facture PDF par relevé (juin à août payées, septembre et octobre impayées)
//   - 7 réclamations réparties sur les 6 derniers mois (traitées, en cours, en attente)
//   - les notifications correspondantes (client et fournisseur)
// Le script refuse de tourner deux fois (il s'arrête si le client a déjà des relevés validés).

define('ROOT', __DIR__.'/../');
define('link', 'http://localhost:8080/');
require_once ROOT.'BD/connexion.php';
require_once ROOT.'BD/declarationConsommation.php';
require_once ROOT.'Traitement/consommateur/reglesConsommation.php';
require_once ROOT.'Traitement/consommateur/pdfFacture.php';

const EMAIL_CLIENT = 'omarelhorre@gmail.com';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = connexion();
mysqli_set_charset($db, 'utf8mb4');

function ligne($db, $sql, $types = '', ...$valeurs)
{
    $q = mysqli_prepare($db, $sql);
    if ($types !== '') mysqli_stmt_bind_param($q, $types, ...$valeurs);
    mysqli_stmt_execute($q);
    $resultat = mysqli_stmt_get_result($q);
    return $resultat ? mysqli_fetch_assoc($resultat) : null;
}

// ----- Le client, son fournisseur et sa première adresse -----
$client = ligne($db, 'SELECT u.id, u.nom, u.prenom, c.idFournisseur FROM utilisateur u JOIN consommateur c ON c.id=u.id WHERE u.email=?', 's', EMAIL_CLIENT);
if (!$client) exit("Client ".EMAIL_CLIENT." introuvable.\n");
$idClient = (int)$client['id'];
$fournisseur = (int)$client['idFournisseur'];
$adresse = ligne($db, 'SELECT id, adresse FROM adresseCompteur WHERE idConsommateur=? ORDER BY id LIMIT 1', 'i', $idClient);
if (!$adresse) exit("Le client n'a aucune adresse de compteur.\n");
$idAdresse = (int)$adresse['id'];

$deja = ligne($db, "SELECT COUNT(*) AS n FROM declaration WHERE idConsommateur=? AND statut='Validée'", 'i', $idClient);
if ((int)$deja['n'] > 0) exit("Le client a déjà des relevés validés : rien n'est ajouté.\n");

mysqli_begin_transaction($db);
$fichiersCrees = array();
try {
    // ----- Relevé de référence : placé le 20 mai 2026 -----
    $reference = ligne($db, "SELECT id, indexEau, indexElectricite FROM declaration WHERE idAdresseCompteur=? AND statut='Référence initiale' ORDER BY id LIMIT 1", 'i', $idAdresse);
    if ($reference) {
        $q = mysqli_prepare($db, "UPDATE declaration SET dateSaisie='2026-05-20', dateDebut='2026-05-20' WHERE id=?");
        mysqli_stmt_bind_param($q, 'i', $reference['id']);
        mysqli_stmt_execute($q);
        $precedente = array('indexEau' => (float)$reference['indexEau'], 'indexElectricite' => (float)$reference['indexElectricite'], 'dateSaisie' => '2026-05-20');
    } else {
        enregistrerDeclaration($db, $fournisseur, $idClient, $idAdresse, null, null, 0.0, 0.0, '2026-05-20', 'Référence initiale', '2026-05-20', null, null, null);
        $precedente = array('indexEau' => 0.0, 'indexElectricite' => 0.0, 'dateSaisie' => '2026-05-20');
    }

    // ----- Un relevé validé par mois, avec sa facture -----
    // [mois, m³ d'eau consommés, kWh consommés, payée le (null = impayée)]
    $mois = array(
        array('2026-06', 11.5, 184.0, '2026-06-28'),
        array('2026-07', 14.2, 247.0, '2026-07-27'),
        array('2026-08', 16.8, 263.0, '2026-08-29'),
        array('2026-09', 12.1, 205.0, null),
        array('2026-10', 10.4, 192.0, null),
    );
    $photos = array(
        '2026-06' => array('demo-11-2026-06-water-a8c1497164b0.jpg', 'demo-11-2026-06-electricity-a8946bff9165.jpg'),
        '2026-07' => array('demo-11-2026-07-water-f8ebc389e43e.jpg', 'demo-11-2026-07-electricity-d555d6ce5e88.jpg'),
        '2026-08' => array('demo-11-2026-08-water-69400cd33ea4.jpg', 'demo-11-2026-08-electricity-1aa3625f3acc.jpg'),
        '2026-09' => array('demo-11-2026-09-water-029b897ff287.jpg', 'demo-11-2026-09-electricity-b011c6657c2b.jpg'),
        '2026-10' => array('demo-11-2026-10-water-1ff39351c414.jpg', 'demo-11-2026-10-electricity-6334bc3665a9.jpg'),
    );
    $factures = array();
    foreach ($mois as $m) {
        list($cle, $eauMois, $elecMois, $payeLe) = $m;
        $jour = $cle.'-20';
        $eau = round($precedente['indexEau'] + $eauMois, 4);
        $elec = round($precedente['indexElectricite'] + $elecMois, 4);
        $calcul = calculerDeclaration($eau, $elec, $precedente);
        $photoEau = 'stockage_consommation/'.$photos[$cle][0];
        $photoElec = 'stockage_consommation/'.$photos[$cle][1];
        $idDeclaration = enregistrerDeclaration($db, $fournisseur, $idClient, $idAdresse, $photoEau, $photoElec, $eau, $elec, $jour, $calcul['statut'], $precedente['dateSaisie'], $jour, $calcul['ht'], $calcul['ttc']);

        $pdf = 'stockage_consommation/mock-'.$idClient.'-'.$cle.'-'.bin2hex(random_bytes(4)).'.pdf';
        $idFacture = enregistrerFacture($db, $idDeclaration, $pdf, $jour);
        $courante = array('indexEau' => $eau, 'indexElectricite' => $elec, 'dateSaisie' => $jour);
        $infosClient = array('id' => $idClient, 'nom' => $client['nom'], 'prenom' => $client['prenom'], 'adresse' => $adresse['adresse']);
        creerPdfFacture(ROOT.$pdf, $idFacture, $infosClient, $precedente, $courante, $calcul, ROOT.$photoEau, ROOT.$photoElec, $jour);
        $fichiersCrees[] = ROOT.$pdf;

        if ($payeLe) {
            $q = mysqli_prepare($db, "UPDATE facture SET etat='Payée', datePaiement=? WHERE id=?");
            mysqli_stmt_bind_param($q, 'si', $payeLe, $idFacture);
            mysqli_stmt_execute($q);
        }
        $factures[$cle] = array('id' => $idFacture, 'ttc' => $calcul['ttc'], 'payeLe' => $payeLe, 'jour' => $jour);
        $precedente = $courante;
    }

    // ----- Réclamations des 6 derniers mois -----
    // [date, type, description, statut, réponse]
    $reclamations = array(
        array('2026-05-27', 'fuite_externe', "Fuite d'eau visible sur le trottoir devant le compteur depuis ce matin.", 'Traitée', "Une équipe est intervenue le 28 mai : le joint du raccord extérieur a été remplacé."),
        array('2026-06-24', 'autre', "Le capot du compteur d'électricité est cassé, l'écran est difficile à lire.", 'Traitée', "Capot remplacé lors du passage du technicien le 2 juillet."),
        array('2026-07-30', 'facture', "La facture de juillet me paraît élevée par rapport à juin, pouvez-vous vérifier les index ?", 'Traitée', "Index vérifiés sur les photos : la hausse correspond à la consommation d'été (climatisation). La facture est correcte."),
        array('2026-08-18', 'fuite_interne', "Le compteur d'eau tourne alors que tous les robinets sont fermés.", 'Traitée', "Fuite détectée sur la chasse d'eau, à réparer par vos soins. Aucune anomalie côté réseau."),
        array('2026-09-26', 'facture', "Je n'ai pas reçu la facture de septembre par email.", 'En cours', "La facture est disponible dans votre espace. Nous vérifions l'envoi des emails."),
        array('2026-10-25', 'autre', "Je souhaite passer au paiement mensuel par virement au lieu des espèces.", 'En Attente', null),
        array('2026-11-12', 'facture', "Je conteste le montant de la facture d'octobre, ma consommation a baissé ce mois-ci.", 'En Attente', null),
    );
    $sousTable = array('fuite_interne' => array('reclamationFuite', 'fuiteInterne'), 'fuite_externe' => array('reclamationFuite', 'fuiteExterne'), 'autre' => array('autre'));
    $idsReclamations = array();
    foreach ($reclamations as $r) {
        list($date, $type, $description, $statut, $reponse) = $r;
        $q = mysqli_prepare($db, 'INSERT INTO reclamation (id, reponse, description, dateCreation, statut) VALUES (?,?,?,?,?)');
        mysqli_stmt_bind_param($q, 'issss', $idClient, $reponse, $description, $date, $statut);
        mysqli_stmt_execute($q);
        $idReclamation = mysqli_insert_id($db);
        if ($type === 'facture') {
            // la facture du mois précédant la réclamation
            $cle = $date >= '2026-11-01' ? '2026-10' : ($date >= '2026-09-01' ? '2026-09' : '2026-07');
            $q = mysqli_prepare($db, 'INSERT INTO reclamationFacture (idReclamation, id) VALUES (?,?)');
            mysqli_stmt_bind_param($q, 'ii', $idReclamation, $factures[$cle]['id']);
            mysqli_stmt_execute($q);
        } else {
            foreach ($sousTable[$type] as $table) {
                mysqli_query($db, 'INSERT INTO '.$table.' (idReclamation) VALUES ('.(int)$idReclamation.')');
            }
        }
        $idsReclamations[] = array($idReclamation, $date, $statut, $reponse, $description);
    }

    // ----- Notifications correspondantes (les anciennes sont déjà lues) -----
    $notif = mysqli_prepare($db, 'INSERT INTO notification (idUtilisateur, type, titre, detail, lien, aTraiter, idObjet, dateCreation, dateLecture, dateResolution) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $ajouter = function ($utilisateur, $type, $titre, $detail, $lienNotif, $aTraiter, $objet, $creee, $lue, $resolue) use ($notif) {
        mysqli_stmt_bind_param($notif, 'issssiisss', $utilisateur, $type, $titre, $detail, $lienNotif, $aTraiter, $objet, $creee, $lue, $resolue);
        mysqli_stmt_execute($notif);
    };
    foreach ($factures as $f) {
        $recent = $f['jour'] >= '2026-10-01';
        $ajouter($idClient, 'facture', 'Votre facture n° '.$f['id'].' est disponible', 'Montant : '.number_format($f['ttc'], 2, ',', ' ').' DH TTC.', 'declaration/all/'.$idAdresse, 0, $f['id'], $f['jour'].' 10:15:00', $recent ? null : $f['jour'].' 18:00:00', null);
        if ($f['payeLe']) {
            $ajouter($idClient, 'facture_payee', 'Paiement reçu pour la facture n° '.$f['id'], 'Votre paiement en espèces a été enregistré.', 'declaration/all', 0, $f['id'], $f['payeLe'].' 11:30:00', $f['payeLe'].' 19:00:00', null);
        }
    }
    foreach ($idsReclamations as $r) {
        list($idReclamation, $date, $statut, $reponse, $description) = $r;
        $traitee = $statut === 'Traitée';
        $ajouter($fournisseur, 'reclamation_nouvelle', 'Nouvelle réclamation n° '.$idReclamation, mb_substr($description, 0, 200), 'reclamation/detail/'.$idReclamation, 1, $idReclamation, $date.' 09:00:00', $date >= '2026-11-01' ? null : $date.' 12:00:00', $traitee ? $date.' 17:00:00' : null);
        if ($reponse !== null) {
            $ajouter($idClient, 'reclamation_reponse', 'Réclamation n° '.$idReclamation.' : '.$statut, mb_substr($reponse, 0, 200), 'reclamations.php', 0, $idReclamation, $date.' 17:00:00', $date >= '2026-09-01' ? null : $date.' 20:00:00', null);
        }
    }

    mysqli_commit($db);
} catch (Throwable $e) {
    mysqli_rollback($db);
    foreach ($fichiersCrees as $fichier) if (is_file($fichier)) unlink($fichier);
    exit('Erreur, rien n\'a été enregistré : '.$e->getMessage()."\n");
}

// ----- Résumé -----
$impayees = array_filter($factures, function ($f) { return !$f['payeLe']; });
echo "Client ".EMAIL_CLIENT." (n° $idClient), adresse n° $idAdresse : ".$adresse['adresse']."\n";
echo count($factures)." relevés validés et factures, dont ".count($impayees)." impayées : ";
echo implode(', ', array_map(function ($f) { return 'n° '.$f['id'].' ('.number_format($f['ttc'], 2, ',', ' ').' DH)'; }, $impayees))."\n";
echo count($idsReclamations)." réclamations ajoutées.\n";
