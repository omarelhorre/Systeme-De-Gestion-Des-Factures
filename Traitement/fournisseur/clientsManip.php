<?php
    use PHPMailer\PHPMailer\PHPMailer;
    require_once(ROOT.'/vendor/autoload.php');
    include_once(ROOT."/BD/consommateur.php");

    $link = connexion();
    function afficherClients()
    {
       $result = selectAll();
        $data = [];
        while($ligne = mysqli_fetch_row($result))
        {
            $data[] = $ligne;
        }
        include_once(ROOT."/IHM/fournisseur/clientsList.php"); 
           
    }
    function add()
    {
        if($_SERVER["REQUEST_METHOD"] == "GET")
            {
                include_once(ROOT."/IHM/fournisseur/form-add.php");
            }
        else if($_SERVER["REQUEST_METHOD"] == "POST")
        {
            $password = bin2hex(random_bytes(8));
            $passwordHashed = password_hash($password,PASSWORD_DEFAULT);
            $lastUser = insertUser($_POST["email"],$_POST["nom"],$_POST["prenom"],$passwordHashed);
            insertConsommateur($lastUser,$_SESSION["user"]["id"],$_POST["adresse"]);
            $email = new PHPMailer();
            $email->isSMTP();
            $email->SMTPAuth = TRUE;
            $email->Host = 'smtp.gmail.com';
            $email->Port = '587';
            $email->Username = 'farhan.othmane@etu.uae.ac.ma';
            $email->Password = getenv('SMTP_PASS');
            $email-> SMTPSecure = 'tls';
            $email->setFrom('farhan.othmane@etu.uae.ac.ma','Fatourti');
            $email->AddAddress($_POST["email"],$_POST["nom"].' '.$_POST["prenom"]);
            $email->Subject = 'Votre mot de passe de connexion à votre espace client';
            $email->Body = "Votre mot de passe est: ". $password . ". Ne le partager avec personne";
            if(!$email->send())
                {
                    
                }
            else
                {
                    header("Location:".link."consommateur/all");
                    exit;
                }
            
        }
    }

    function modify($id)
    {
        if($_SERVER["REQUEST_METHOD"] == "GET")
            {
                $result = selectConsommateur($id);
                include_once(ROOT."/IHM/fournisseur/modify-form.php");
            }
        else if ($_SERVER["REQUEST_METHOD"] == "POST")
            {
                echo($id);
                modifyUtilisateur($id,$_POST["nom"],$_POST["prenom"],$_POST["email"]);
                modifyConsommateur($id,$_POST["adresse"]);
                header("Location:".link."/consommateur/all");
                exit;
            }
    }
?>