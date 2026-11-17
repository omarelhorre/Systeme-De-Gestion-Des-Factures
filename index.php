<?php
// Routeur unique de l'application : TOUTES les adresses passent ici (voir .htaccess),
// sous la forme table/action/id. Seuls les fichiers de public/ (CSS, JS) sont servis directement.
define('ROOT',str_replace('index.php','',$_SERVER["SCRIPT_FILENAME"]));
define('link',"http://localhost:8080/");
define('dir',"");
include_once(ROOT."/Traitement/loginForm.php");
include_once(ROOT."/Traitement/profil.php");
include_once(ROOT."/Traitement/notification.php");
include_once(ROOT."/Traitement/fournisseur/clientsManip.php");
include_once(ROOT."/Traitement/fournisseur/facturesManip.php");
include_once(ROOT."/Traitement/fournisseur/anomaliesManip.php");
include_once(ROOT."/Traitement/consommateur/declarationConsommation.php");
include_once(ROOT."/Traitement/agent/importAnnuel.php");
include_once(ROOT."/Traitement/fournisseur/bilanAnnuel.php");

// Adresse inconnue : la page 404, et rien d'autre
function introuvable()
{
    http_response_code(404);
    include ROOT.'IHM/404.php';
    exit;
}

$URL = explode('/', trim($_GET['url'] ?? '', '/'));
$id = $URL[2] ?? null;

// Vérifier la connexion avant d'afficher le tableau de bord fournisseur.
if (($URL[0] ?? '') === 'fournisseur'
    && ($URL[1] ?? '') === 'tableauDeBord'
    && count($URL) === 2
    && !isset($_SESSION['user'])) {
    header('Location: '.link.'utilisateur/authentification');
    exit;
}

// Adresse vide (http://localhost:8080/) : la connexion
if($URL[0] == "")
    {
        header('Location: '.link.'utilisateur/authentification');
        exit;
    }

else if($URL[0] == "utilisateur" )
    {
        if(($URL[1] ?? '') == "authentification")
            {
                authentification();
            }
        else if(($URL[1] ?? '') == "deconnexion")
            {
                deconnexion();
            }
        else if(in_array($URL[1] ?? '', ["profil", "modifierEmail", "modifierMotDePasse", "modifierSeuils"], true))
            {
                profil($URL[1]);
            }
        else introuvable();
    }

else if($URL[0] == "consommateur")
    {
        if(($URL[1] ?? '') == "tableauDeBord")
            {
                redirectToIndexC();
            }
        else if(($URL[1] ?? '') == "all")
            {
               afficherClients();
            }
        else if(($URL[1] ?? '') == "ajouter")
            {
               add();
            }
        else if(($URL[1] ?? '') == "ajouterAdresse" && isset($id))
            {
                ajouterAdresse($id);
            }
        else if(($URL[1] ?? '') == "modifier" && isset($id))
            {
                modify($id);
            }
        else if(($URL[1] ?? '') == "supprimer" && isset($id))
            {
                supprimer($id);
            }
        else introuvable();
    }
else if($URL[0] == "fournisseur")
    {
        if(($URL[1] ?? '') == "tableauDeBord")
            {
                redirectToIndexF();
            }
        else introuvable();
    }
else if($URL[0] == "agent")
    {
        if(in_array($URL[1] ?? '', ['tableauDeBord', 'importer'], true))
            {
                if (!isset($_SESSION['user']) || !checkAgent($_SESSION['user']['id'])) {
                    header('Location: '.link.'utilisateur/authentification');
                    exit;
                }
                traiterImportAnnuel();
            }
        else introuvable();
    }
else if($URL[0] == "annuelle")
    {
        // annuelle/all/{année}, annuelle/regler/{id}
        if(($URL[1] ?? '') == 'all') afficherBilanAnnuel($id);
        else if(($URL[1] ?? '') == 'regler' && isset($id)) reglerBilanAnnuel((int)$id);
        else introuvable();
    }
else if($URL[0] == "declaration")
    {
        if(($URL[1] ?? '') == 'all') traiterConsommation('all', $id);
        else if(($URL[1] ?? '') == 'ajouter') traiterConsommation('ajouter', $id);   // formulaire, sur la page du compteur choisi
        else if(($URL[1] ?? '') == 'anomalies') traiterAnomaliesFournisseur('anomalies');
        else if(in_array($URL[1] ?? '', ['corriger', 'photoEau', 'photoElectricite'], true) && isset($id) && ctype_digit($id) && (int)$id > 0)
            traiterAnomaliesFournisseur($URL[1], (int)$id);
        else if(in_array($URL[1] ?? '', ['facture', 'eau', 'electricite'], true) && isset($id) && ctype_digit($id) && (int)$id > 0)
            traiterConsommation($URL[1], (int)$id);
        else introuvable();
    }
else if($URL[0] == "facture")
    {
        // facture/all, facture/all/payees, facture/all/impayees : la liste, filtrée par le 3e morceau
        // facture/detail|payer|pdf|telecharger/{id}
        $filtresFactures = array('payees' => 'Payée', 'impayees' => 'Impayée');
        if(($URL[1] ?? '') == 'all' && (!isset($id) || isset($filtresFactures[$id])))
            afficherFacturesFournisseur('liste', null, isset($id) ? $filtresFactures[$id] : '');
        else if(in_array($URL[1] ?? '', ['detail', 'payer', 'pdf', 'telecharger'], true) && isset($id) && ctype_digit($id) && (int)$id > 0)
            afficherFacturesFournisseur($URL[1], (int)$id);
        else introuvable();
    }
else if($URL[0] == "reclamation")
    {
        // Un consommateur : reclamation/all = ses réclamations, reclamation/ajouter = le formulaire.
        // Un fournisseur : la gestion des réclamations de ses clients
        //   (all, all/{attente|encours|traitee}, detail/{id}, traiter/{id}).
        if (isset($_SESSION['user']) && checkConsommateur($_SESSION['user']['id']))
            {
                if (in_array($URL[1] ?? '', ['all', 'ajouter'], true) && !isset($id))
                    {
                        require_once ROOT.'BD/reclamationsConsommateur.php';
                        require_once ROOT.'Traitement/consommateur/reclamations.php';
                        traiterReclamationsConsommateur($URL[1]);
                    }
                else introuvable();
            }
        else
            {
                require_once ROOT.'Traitement/fournisseur/gestionReclamations.php';
                gestionReclamationsAfficher($URL);
            }
    }

else if($URL[0] == "notification")
    {
        // Routes AJAX de la cloche : notification/all, notification/lire/{id}, notification/toutLire
        notifications($URL[1] ?? '', $id);
    }

else if($URL[0] == "contrat")
    {
        if(($URL[1] ?? '') == "resilier" && isset($id))
            {
                resilier($id);
            }
        else introuvable();
    }

else introuvable();
?>
