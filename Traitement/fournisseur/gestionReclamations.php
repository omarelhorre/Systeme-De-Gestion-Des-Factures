<?php
require_once __DIR__.'/../../BD/connexion.php';
require_once __DIR__.'/../../BD/gestionReclamations.php';
require_once __DIR__.'/notificationReclamations.php';

function gestionReclamationsTexte($valeur)
{
    return htmlspecialchars((string)($valeur ?? ''), ENT_QUOTES, 'UTF-8');
}

function gestionReclamationsErreur($code, $message)
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}

function gestionReclamationsStatut($statut)
{
    // Accepter aussi les anciennes écritures présentes en base.
    $valeur = strtolower(strtr(trim($statut), ['É' => 'e', 'é' => 'e', 'È' => 'e', 'è' => 'e']));
    if (in_array($valeur, ['traitee', 'traite', 'traitees'], true)) return 'traitee';
    if ($valeur === 'en cours') return 'encours';
    if ($valeur === 'en attente') return 'attente';
    return 'autre';
}

function gestionReclamationsContient($texte, $recherche)
{
    $accents = ['É'=>'e', 'È'=>'e', 'Ê'=>'e', 'é'=>'e', 'è'=>'e', 'ê'=>'e',
        'À'=>'a', 'Â'=>'a', 'à'=>'a', 'â'=>'a', 'Î'=>'i', 'î'=>'i', 'Ô'=>'o', 'ô'=>'o',
        'Ù'=>'u', 'Û'=>'u', 'ù'=>'u', 'û'=>'u', 'Ç'=>'c', 'ç'=>'c'];
    return strpos(strtolower(strtr($texte, $accents)), strtolower(strtr($recherche, $accents))) !== false;
}

function gestionReclamationsAfficher($route)
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    $fournisseur = filter_var($_SESSION['user']['id'] ?? null,
        FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$fournisseur) {
        header('Location: '.link.'utilisateur/authentification');
        exit;
    }
    $action = $route[1] ?? '';
    if (!in_array($action, ['all', 'detail', 'traiter'], true)) {
        gestionReclamationsErreur(404, 'Page introuvable.');
    }
    $methode = $action === 'traiter' ? 'POST' : 'GET';
    if ($_SERVER['REQUEST_METHOD'] !== $methode) {
        header('Allow: '.$methode);
        gestionReclamationsErreur(405, 'Méthode non autorisée.');
    }
    if (count($route) !== ($action === 'all' ? 2 : 3)) {
        gestionReclamationsErreur(404, 'Page introuvable.');
    }
    $id = null;
    if ($action !== 'all') {
        $id = filter_var($route[2] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) gestionReclamationsErreur(400, 'Numéro de réclamation invalide.');
    }
    if (empty($_SESSION['csrf_gestion_reclamations'])) {
        $_SESSION['csrf_gestion_reclamations'] = bin2hex(random_bytes(32));
    }
    $erreur = '';
    $reclamation = null;
    $transaction = false;
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db = connexion();
        mysqli_set_charset($db, 'utf8mb4');
        if (!gestionReclamationsEstFournisseur($db, $fournisseur)) {
            gestionReclamationsErreur(403, 'Accès réservé aux fournisseurs.');
        }
        if ($action !== 'all') {
            if ($action === 'traiter') {
                if (!is_string($_POST['csrf'] ?? null) ||
                    !hash_equals($_SESSION['csrf_gestion_reclamations'], $_POST['csrf'])) {
                    gestionReclamationsErreur(403, 'Formulaire expiré. Rechargez la page.');
                }
                mysqli_begin_transaction($db);
                $transaction = true;
            }
            $lignes = gestionReclamationsLire($db, $fournisseur, $id, $transaction);
            if (!$lignes) {
                if ($transaction) mysqli_rollback($db);
                gestionReclamationsErreur(404, 'Réclamation introuvable.');
            }
            $reclamation = $lignes[0];
        }
        if ($action === 'traiter') {
            $reponse = is_string($_POST['reponse'] ?? null) ? trim($_POST['reponse']) : '';
            $statut = is_string($_POST['statut'] ?? null) ? $_POST['statut'] : '';
            if (!in_array($statut, ['En cours', 'Traitée'], true)) {
                $erreur = 'Choisissez un statut valide.';
            } elseif ($reponse === '' || !preg_match('//u', $reponse) || preg_match_all('/./us', $reponse) > 1000) {
                $erreur = 'Décrivez le traitement effectué avec 1 à 1000 caractères.';
            } elseif (gestionReclamationsStatut($reclamation['statut']) === 'traitee' && $statut !== 'Traitée') {
                $erreur = 'Une réclamation traitée ne peut pas être remise en cours.';
            }
            if ($erreur !== '') {
                mysqli_rollback($db);
                $transaction = false;
                http_response_code(422);
                $reclamation['reponse'] = $reponse;
                $action = 'detail';
            } else {
                $modifie = $reclamation['statut'] !== $statut || (string)$reclamation['reponse'] !== $reponse;
                if ($modifie) gestionReclamationsSauver($db, $fournisseur, $id, $statut, $reponse);
                mysqli_commit($db);
                $transaction = false;
                mysqli_close($db);
                // Envoyer après validation en base : un échec mail n'efface pas le traitement.
                $message = 'Aucun changement : ce traitement est déjà enregistré. Aucun nouvel e-mail envoyé.';
                $avertissement = false;
                if ($modifie) {
                    // Notifications : le client voit la réponse dans sa cloche (l'email part juste après, comme avant).
                    notifier((int)$reclamation['id'], 'reclamation_reponse', 'Réclamation n° '.$id.' : '.$statut, mb_substr($reponse, 0, 200), 'reclamation/all', array('idObjet' => $id));
                    if ($statut === 'Traitée') resoudreNotification($fournisseur, 'reclamation_nouvelle', $id);
                    $envoye = gestionReclamationsNotifier($reclamation, $statut, $reponse);
                    $message = $envoye
                        ? 'Traitement enregistré. Notification transmise au serveur de messagerie.'
                        : 'Traitement enregistré, mais la notification e-mail a échoué. Vérifiez la configuration SMTP.';
                    $avertissement = !$envoye;
                }
                $_SESSION['message_gestion_reclamations'] = ['texte' => $message, 'avertissement' => $avertissement];
                header('Location: '.link.'reclamation/detail/'.$id, true, 303);
                exit;
            }
        }
        $types = ['Fuite externe', 'Fuite interne', 'Facture', 'Autre', 'Fuite (non précisée)'];
        $statuts = ['' => 'Toutes', 'attente' => 'En attente', 'encours' => 'En cours', 'traitee' => 'Traitées'];
        // Le .htaccess existant ne conserve pas les paramètres de recherche.
        // Les lire depuis l'URL d'origine, sans modifier ce fichier existant.
        $filtres = [];
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $filtres);
        $recherche = $filtres['recherche'] ?? '';
        $type = $filtres['type'] ?? '';
        $filtreStatut = $filtres['statut'] ?? '';
        if (!is_string($recherche) || strlen($recherche) > 200 || !preg_match('//u', $recherche)
            || !is_string($type) || ($type !== '' && !in_array($type, $types, true))
            || !is_string($filtreStatut) || !array_key_exists($filtreStatut, $statuts)) {
            gestionReclamationsErreur(400, 'Filtres invalides.');
        }
        $recherche = trim($recherche);
        $statistiques = ['total' => 0, 'attente' => 0, 'encours' => 0, 'traitee' => 0];
        $reclamations = [];
        if ($action === 'all') {
            $toutes = gestionReclamationsLire($db, $fournisseur);
            $statistiques['total'] = count($toutes);
            foreach ($toutes as $ligne) {
                $cle = gestionReclamationsStatut($ligne['statut']);
                if (isset($statistiques[$cle])) $statistiques[$cle]++;
                if ($filtreStatut !== '' && $cle !== $filtreStatut) continue;
                if ($type !== '' && $ligne['typeReclamation'] !== $type) continue;
                $nom = $ligne['nom'].' '.$ligne['prenom'].' '.$ligne['prenom'].' '.$ligne['nom'];
                $numero = ltrim($recherche, '#0');
                $numeroTrouve = ctype_digit($numero) && (string)$ligne['idReclamation'] === $numero;
                if ($recherche !== '' && !$numeroTrouve && !gestionReclamationsContient($nom, $recherche)) continue;
                $reclamations[] = $ligne;
            }
        }
        mysqli_close($db);
    } catch (mysqli_sql_exception $exception) {
        if ($transaction && isset($db)) mysqli_rollback($db);
        error_log('Gestion réclamations : '.$exception->getMessage());
        gestionReclamationsErreur(503, 'Impossible de charger les réclamations. Vérifiez la connexion et les tables de la base.');
    }
    $message = $_SESSION['message_gestion_reclamations'] ?? null;
    unset($_SESSION['message_gestion_reclamations']);
    include __DIR__.'/../../IHM/fournisseur/gestionReclamations.php';
}
