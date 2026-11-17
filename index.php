<?php 
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



// Vérifier la connexion avant d'afficher le tableau de bord fournisseur.
$URL = explode('/', trim($_GET['url'] ?? '', '/'));

if (($URL[0] ?? '') === 'fournisseur'
    && ($URL[1] ?? '') === 'tableauDeBord'
    && count($URL) === 2
    && !isset($_SESSION['user'])) {
    header('Location: '.link.'utilisateur/authentification');
    exit;
}



if($URL[0] == "utilisateur" )
    {
        if($URL[1] == "authentification")
            {
                authentification();
            }
        else if($URL[1] == "deconnexion")
            {
                deconnexion();
            }
        else if(in_array($URL[1], ["profil", "modifierEmail", "modifierMotDePasse", "modifierSeuils"], true))
            {
                profil($URL[1]);
            }
    }

else if($URL[0] == "consommateur")
    {
        if($URL[1] == "tableauDeBord")
            {
                redirectToIndexC();
            }
        else if($URL[1] == "all")
            {
               afficherClients();
            }
        else if($URL[1] == "ajouter")
            {
               add();
            }
        else if($URL[1] == "ajouterAdresse" && isset($URL[2]))
            {
                ajouterAdresse($URL[2]);
            }
         else if($URL[1] == "modifier")
            {
                modify($URL[2]);
            }
         else if($URL[1] == "supprimer" && isset($URL[2]))
            {
                supprimer($URL[2]);
            }
    }
else if($URL[0] == "fournisseur")
    {
        if($URL[1] == "tableauDeBord")
            {
                redirectToIndexF();
            }
         if($URL[1] == "clients")
            {
                
            }
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
    }
else if($URL[0] == "annuelle")
    {
        if(($URL[1] ?? '') == 'all') afficherBilanAnnuel($URL[2] ?? null);
        else if(($URL[1] ?? '') == 'regler' && isset($URL[2])) reglerBilanAnnuel((int)$URL[2]);
    }
else if($URL[0] == "declaration")
    {
        if(($URL[1] ?? '') == 'all') traiterConsommation('all', $URL[2] ?? null);
        else if(($URL[1] ?? '') == 'ajouter') traiterConsommation('ajouter');
        else if(($URL[1] ?? '') == 'anomalies') traiterAnomaliesFournisseur('anomalies');
        else if(in_array($URL[1] ?? '', ['corriger', 'photoEau', 'photoElectricite'], true) && isset($URL[2]) && ctype_digit($URL[2]) && (int)$URL[2] > 0)
            traiterAnomaliesFournisseur($URL[1], (int)$URL[2]);
        else if(in_array($URL[1] ?? '', ['facture', 'eau', 'electricite'], true) && isset($URL[2]) && ctype_digit($URL[2]) && (int)$URL[2] > 0)
            traiterConsommation($URL[1], (int)$URL[2]);
    }
else if($URL[0] == "facture")
    {
        if(($URL[1] ?? '') == 'all') afficherFacturesFournisseur('liste');
        else if(in_array($URL[1] ?? '', ['detail', 'payer', 'pdf'], true) && isset($URL[2]) && ctype_digit($URL[2]) && (int)$URL[2] > 0)
            afficherFacturesFournisseur($URL[1], (int)$URL[2]);
    }
else if($URL[0] == "reclamation")
    {
        require_once ROOT.'Traitement/fournisseur/gestionReclamations.php';
        gestionReclamationsAfficher($URL);
    }

else if($URL[0] == "notification")
    {
        // Routes AJAX de la cloche : notification/all, notification/lire/{id}, notification/toutLire
        notifications($URL[1] ?? '', $URL[2] ?? null);
    }

else if($URL[0] == "contrat")
    {
        if($URL[1] == "resilier")
            {
                resilier($URL[2]);
            }
    }
// Activer le lien des réclamations dans les menus fournisseur existants.
$pagesNavigationReclamations = [
    'fournisseur/tableauDeBord', 'consommateur/all',
    'consommateur/ajouter', 'consommateur/modifier',
    'facture/all', 'facture/detail',
    'declaration/anomalies', 'declaration/corriger'
];
$pageNavigationReclamations = ($URL[0] ?? '').'/'.($URL[1] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_SESSION['user'])
    && in_array($pageNavigationReclamations, $pagesNavigationReclamations, true)) {
    include ROOT.'IHM/fournisseur/navigationReclamations.php';
}
?>
