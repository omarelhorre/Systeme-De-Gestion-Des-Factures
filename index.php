<?php 
define('ROOT',str_replace('index.php','',$_SERVER["SCRIPT_FILENAME"]));
define('link',"http://localhost/Fatourti/");
define('dir',"/Fatourti/");
include_once(ROOT."/Traitement/loginForm.php");
include_once(ROOT."/Traitement/fournisseur/clientsManip.php");
$URL = explode('/',$_GET["url"]);
if($URL[0] == "utilisateur" )
    {
        if($URL[1] == "authentification")
            {
                authentification();
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
         else if($URL[1] == "modifier")
            {
                modify($URL[2]);
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
?>