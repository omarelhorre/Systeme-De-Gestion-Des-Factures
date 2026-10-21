<?php 
include_once(ROOT."/BD/utilisateur.php");
include_once(ROOT."/BD/fournisseur.php");
include_once(ROOT."/BD/consommateur.php");
session_start();
function authentification()
    {
        if($_SERVER["REQUEST_METHOD"] == "GET")
            {
                include_once(ROOT."IHM/login.php");
            }
        else if($_SERVER["REQUEST_METHOD"] == "POST")
            {
                $email = $_POST["email"];
                $password = $_POST["password"];
                $user = checkcredentials($email);
                if(isset($user))
                {
                    $passwordHashed = $user["pass"] ?? "";
                    if(password_verify($password,$passwordHashed))
                    {
                        $_SESSION["user"] = $user;
                        $fournisseur = checkFournisseur($user[0]);
                        $consommateur = checkConsommateur($user[0]);
                        if(isset($consommateur))
                            {
                            header("Location:".link."consommateur/tableauDeBord");
                            }
                        if(isset($fournisseur))
                            {
                            header("Location:".link."fournisseur/tableauDeBord");
                            }
                    }
                    else
                    {
                        include_once(ROOT."IHM/login.php");
                    }
                }
                else
                {
                    include_once(ROOT."IHM/login.php");
                }
            }

        }
    function redirectToIndexF()
    {
    include_once(ROOT."/IHM/tableauF.php");
    }

    function redirectToIndexC()
    {
        include_once(ROOT."/IHM/tableauC.php");
    }


    function deconnexion()
    {
        $_SESSION=[];
        session_destroy();
        header("Location:".link."utilisateur/authentification");
        exit;
    }
?>