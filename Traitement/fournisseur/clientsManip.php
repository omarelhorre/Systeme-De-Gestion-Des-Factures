<?php
    use PHPMailer\PHPMailer\PHPMailer;
    require_once(ROOT.'/vendor/autoload.php');
    include_once(ROOT."/BD/consommateur.php");
    include_once(ROOT."/BD/agent.php");
    include_once(ROOT."/BD/contrat.php");
    $link = connexion();

    // Numéro d'identité : 1 ou 2 lettres puis 4 à 8 chiffres (ex. BL175578)
    function cinValide($cin)
    {
        return preg_match('/^[A-Z]{1,2}\d{4,8}$/', $cin) === 1;
    }
    // Téléphone marocain : 0 ou +212, puis 9 chiffres commençant par 5, 6 ou 7
    function telephoneValide($telephone)
    {
        return preg_match('/^(\+212|0)[5-7]\d{8}$/', $telephone) === 1;
    }

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
        if($_SERVER["REQUEST_METHOD"] == "GET")
            {
                include_once(ROOT."/IHM/fournisseur/form-add.php");
            }
        else if($_SERVER["REQUEST_METHOD"] == "POST")
        {
            $role = $_POST['role'] ?? 'consommateur';
            if ($role !== 'consommateur' && $role !== 'agent') {
                http_response_code(400); echo 'Type de compte invalide.'; return;
            }
            if ($role === 'consommateur') {
                $adresses = $_POST['adresse'] ?? [];
                $categories = $_POST['categorie'] ?? [];
                $eaux = $_POST['indexPrecendentEau'] ?? [];
                $elects = $_POST['indexPrecendentElec'] ?? [];
                $categoriesPermises = ['Résidentiel économique', 'Résidentiel standard', 'Résidentiel haut standing', 'Commercial', 'Industriel'];
                if (!is_array($adresses) || !$adresses || count($adresses) !== count($categories)
                    || count($adresses) !== count($eaux) || count($adresses) !== count($elects)) {
                    http_response_code(400); echo 'Adresses et compteurs invalides.'; return;
                }
                $points = [];
                foreach ($adresses as $i => $adresse) {
                    $idxEau = str_replace(',', '.', trim($eaux[$i]));
                    $idxElec = str_replace(',', '.', trim($elects[$i]));
                    if (trim($adresse) === '' || !in_array($categories[$i], $categoriesPermises, true)
                        || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $idxEau)
                        || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $idxElec)) {
                        http_response_code(400); echo 'Adresse, catégorie ou index invalide.'; return;
                    }
                    $points[] = [$adresse, $categories[$i], (float)$idxEau, (float)$idxElec];
                }
                // Numéro d'identité (ex. BL175578) et téléphone (ex. 0612345678 ou +212612345678)
                $cin = strtoupper(trim($_POST['cin'] ?? ''));
                $telephone = str_replace(' ', '', trim($_POST['telephone'] ?? ''));
                if (!cinValide($cin) || !telephoneValide($telephone)) {
                    http_response_code(400); echo 'Numéro d\'identité ou téléphone invalide.'; return;
                }
            }

            $password = bin2hex(random_bytes(8));
            $passwordHashed = password_hash($password,PASSWORD_DEFAULT);
            mysqli_begin_transaction($link);
            try {
                $lastUser = insertUser($_POST["email"],$_POST["nom"],$_POST["prenom"],$passwordHashed);
                if ($role === 'agent') insertAgent($lastUser);
                else {
                    insertConsommateur($lastUser,$_SESSION["user"]["id"],$points[0][0],$cin,$telephone);
                    foreach ($points as $point) {
                        insertAdresseCompteur($lastUser,$_SESSION["user"]["id"],$point[0],$point[1],$point[2],$point[3]);
                    }
                }
                mysqli_commit($link);
            } catch (Throwable $e) {
                mysqli_rollback($link);
                throw $e;
            }
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
            $email->Subject = $role === 'agent'
                ? 'Votre mot de passe de connexion à votre espace agent'
                : 'Votre mot de passe de connexion à votre espace client';
            $email->Body = "Votre mot de passe est: ". $password . ". Ne le partager avec personne";
            if ($role === 'consommateur') {
            $Client = ["nom" => $_POST["nom"],
                        "prenom" => $_POST["prenom"],
                        "adresse" => $points[0][0],
                        ];
            include_once(ROOT."/Traitement/domPDFTraitement.php");
            $pdf = genererPdfContrat($Client);
            $email->addStringAttachment($pdf,'contrat-'.$lastUser.'.pdf');
            $cheminGeneration = ROOT.'stockageContrats/contrat-'.$lastUser.'.pdf';
            file_put_contents($cheminGeneration,$pdf);
            ajouterContrat($lastUser,$cheminGeneration);
            }
            if(!$email->send())
                {
                    
                }
            else
                {
                    header("Location:".link.($role === 'agent' ? "fournisseur/tableauDeBord" : "consommateur/all"));
                    exit;
                }
            
        }
    }

    function modify($id)
    {
        if($_SERVER["REQUEST_METHOD"] == "GET")
            {
                $contrat = selectContractByConsommateur($id);
                $result = selectConsommateur($id);
                $adressesCompteur = mysqli_fetch_all(adressesCompteurDuClient($id), MYSQLI_ASSOC);
                include_once(ROOT."/IHM/fournisseur/modify-form.php");
            }
        else if ($_SERVER["REQUEST_METHOD"] == "POST")
            {
                // Les anciens clients n'ont pas encore ces deux champs : vides acceptés, sinon format vérifié.
                $cin = strtoupper(trim($_POST['cin'] ?? ''));
                $telephone = str_replace(' ', '', trim($_POST['telephone'] ?? ''));
                if (($cin !== '' && !cinValide($cin)) || ($telephone !== '' && !telephoneValide($telephone))) {
                    http_response_code(400); echo 'Numéro d\'identité ou téléphone invalide.'; return;
                }
                modifyUtilisateur($id,$_POST["nom"],$_POST["prenom"],$_POST["email"]);
                modifyConsommateur($id,$_POST["adresse"],$cin,$telephone);
                header("Location:".link."consommateur/all");
                exit;
            }
    }

    function ajouterAdresse($id)
    {
        global $link;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:'.link.'consommateur/modifier/'.$id); exit;
        }
        $client = selectConsommateur($id);
        if (!$client || (int)$client['idFournisseur'] !== (int)$_SESSION['user']['id']) {
            http_response_code(403); echo 'Client non autorisé.'; return;
        }
        $adresse = trim($_POST['adresse'] ?? '');
        $categorie = $_POST['categorie'] ?? '';
        $categories = ['Résidentiel économique', 'Résidentiel standard', 'Résidentiel haut standing', 'Commercial', 'Industriel'];
        $eau = str_replace(',', '.', trim($_POST['indexPrecendentEau'] ?? ''));
        $electricite = str_replace(',', '.', trim($_POST['indexPrecendentElec'] ?? ''));
        if ($adresse === '' || !in_array($categorie, $categories, true)
            || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $eau)
            || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $electricite)) {
            http_response_code(400); echo 'Adresse, catégorie ou index invalide.'; return;
        }
        insertAdresseCompteur($id, $_SESSION['user']['id'], $adresse, $categorie, (float)$eau, (float)$electricite);
        header('Location:'.link.'consommateur/modifier/'.$id); exit;
    }

    function resilier($id)
    {
        if($_SERVER["REQUEST_METHOD"] =="POST")
            {
                resilier_BDD($id);
                header("Location:".link."consommateur/all");
            }
        include_once(ROOT."IHM/validerModal.php");
    }

    // Supprimer un client : GET affiche la confirmation, POST supprime.
    function supprimer($id)
    {
        if (!ctype_digit((string)$id)) {
            http_response_code(404); echo 'Client introuvable.'; return;
        }
        $client = selectConsommateur($id);
        if (!$client || (int)$client['idFournisseur'] !== (int)($_SESSION['user']['id'] ?? 0)) {
            http_response_code(403); echo 'Client non autorisé.'; return;
        }
        if (empty($_SESSION['csrf_suppression_client'])) {
            $_SESSION['csrf_suppression_client'] = bin2hex(random_bytes(32));
        }
        if ($_SERVER["REQUEST_METHOD"] == "POST")
            {
                if (!hash_equals($_SESSION['csrf_suppression_client'], (string)($_POST['csrf'] ?? ''))) {
                    http_response_code(400); echo 'Session expirée : rechargez la page.'; return;
                }
                supprimerConsommateur($id);
                unset($_SESSION['csrf_suppression_client']);
                header("Location:".link."consommateur/all");
                exit;
            }
        $historique = historiqueConsommateur($id);
        $contrat = selectContractByConsommateur($id);
        include_once(ROOT."IHM/fournisseur/supprimerClient.php");
    }
?>
