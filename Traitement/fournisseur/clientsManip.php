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
        global $link;
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        if (!$userId || !checkFournisseur($userId)) {
            http_response_code(403);
            echo 'Accès réservé au fournisseur.';
            return;
        }
        $values = ['nom'=>'', 'prenom'=>'', 'adresse'=>'', 'email'=>''];
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($values as $key => $unused) {
                $values[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
            }
            if (in_array('', $values, true) || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)
                || strlen($values['nom']) > 50 || strlen($values['prenom']) > 50
                || strlen($values['adresse']) > 255 || strlen($values['email']) > 100) {
                $error = 'Veuillez remplir tous les champs avec des valeurs valides.';
            } else {
                mysqli_begin_transaction($link);
                try {
                    $password = bin2hex(random_bytes(8));
                    $lastUser = insertUser($values['email'], $values['nom'], $values['prenom'], password_hash($password, PASSWORD_DEFAULT));
                    if (!$lastUser || !insertConsommateur($lastUser, $userId, $values['adresse'])) {
                        throw new RuntimeException('Insertion impossible.');
                    }
                    mysqli_commit($link);
                } catch (Throwable $exception) {
                    mysqli_rollback($link);
                    $error = $exception->getCode() === 1062
                        ? 'Cette adresse email est déjà utilisée. Choisissez une autre adresse.'
                        : 'Impossible d’enregistrer le client. Veuillez réessayer.';
                }
                if ($error === '') {
                    $sent = false;
                    if (getenv('SMTP_PASSWORD')) {
                        try {
                            $email = new PHPMailer();
                            $email->isSMTP();
                            $email->SMTPAuth = true;
                            $email->Host = 'smtp.gmail.com';
                            $email->Port = 587;
                            $email->Username = 'farhan.othmane@etu.uae.ac.ma';
                            $email->Password = getenv('SMTP_PASSWORD');
                            $email->SMTPSecure = 'tls';
                            $email->CharSet = 'UTF-8';
                            $email->Timeout = 10;
                            $email->setFrom('farhan.othmane@etu.uae.ac.ma', 'Fatourti');
                            $email->addAddress($values['email'], $values['nom'].' '.$values['prenom']);
                            $email->Subject = 'Votre mot de passe de connexion à votre espace client';
                            $email->Body = 'Votre mot de passe est : '.$password.'. Ne le partagez avec personne.';
                            $sent = $email->send();
                        } catch (Throwable $exception) {
                            $sent = false;
                        }
                    }
                    $_SESSION['clients_message'] = $sent
                        ? 'Client ajouté avec succès. Ses identifiants ont été envoyés par email.'
                        : 'Client ajouté avec succès. Le mail des identifiants n’a pas pu être envoyé. Ne recréez pas ce client.';
                    header('Location:'.link.'consommateur/all');
                    exit;
                }
            }
        }
        include(ROOT.'IHM/fournisseur/form-add.php');
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