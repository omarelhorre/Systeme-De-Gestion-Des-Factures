<?php
    use PHPMailer\PHPMailer\PHPMailer;
    require_once(ROOT.'/vendor/autoload.php');
    include_once(ROOT."/BD/consommateur.php");
    include_once(ROOT."/BD/agent.php");
    include_once(ROOT."/BD/contrat.php");
    $link = connexion();

    function cinValide($cin)
    {
        return preg_match('/^[A-Z]{1,2}\d{4,8}$/', $cin) === 1;
    }
    function telephoneValide($telephone)
    {
        return preg_match('/^(\+212|0)[5-7]\d{8}$/', $telephone) === 1;
    }

    function afficherClients()
    {
        $result = selectAll((int)($_SESSION['user']['id'] ?? 0));
        $data = [];
        while($ligne = mysqli_fetch_row($result))
        {
            $data[] = $ligne;
        }
        $resume = resumeClients();
        $series = consommationRecenteClients();
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
                $quartiersChoisis = $_POST['quartier'] ?? [];
                $eaux = $_POST['indexPrecendentEau'] ?? [];
                $elects = $_POST['indexPrecendentElec'] ?? [];
                if (!is_array($adresses) || !$adresses || !is_array($quartiersChoisis) || count($adresses) !== count($quartiersChoisis)
                    || count($adresses) !== count($eaux) || count($adresses) !== count($elects)) {
                    http_response_code(400); echo 'Adresses et compteurs invalides.'; return;
                }
                $points = [];
                foreach ($adresses as $i => $adresse) {
                    $idxEau = str_replace(',', '.', trim($eaux[$i]));
                    $idxElec = str_replace(',', '.', trim($elects[$i]));
                    $quartier = quartierParId($quartiersChoisis[$i]);
                    if (trim($adresse) === '' || !$quartier
                        || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $idxEau)
                        || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $idxElec)) {
                        http_response_code(400); echo 'Adresse, quartier ou index invalide.'; return;
                    }
                    $points[] = [$adresse, $quartier['typeActivite'], (float)$idxEau, (float)$idxElec, (int)$quartier['id']];
                }
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
                    $idsAdresses = [];
                    foreach ($points as $point) {
                        $idsAdresses[] = insertAdresseCompteur($lastUser,$_SESSION["user"]["id"],$point[0],$point[1],$point[2],$point[3],$point[4]);
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
            include_once(ROOT."/Traitement/domPDFTraitement.php");
            foreach ($points as $i => $point) {
                $Client = ["nom" => $_POST["nom"],
                            "prenom" => $_POST["prenom"],
                            "adresse" => $point[0],
                            ];
                $pdf = genererPdfContrat($Client);
                $nomFichier = 'contrat-'.$lastUser.'-'.$idsAdresses[$i].'.pdf';
                $email->addStringAttachment($pdf,$nomFichier);
                $cheminGeneration = ROOT.'stockageContrats/'.$nomFichier;
                file_put_contents($cheminGeneration,$pdf);
                ajouterContrat($lastUser,$cheminGeneration,$idsAdresses[$i]);
            }
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
                $contrats = contratsDuConsommateur($id);
                $result = selectConsommateur($id);
                $adressesCompteur = mysqli_fetch_all(adressesCompteurDuClient($id), MYSQLI_ASSOC);
                include_once(ROOT."/IHM/fournisseur/modify-form.php");
            }
        else if ($_SERVER["REQUEST_METHOD"] == "POST")
            {
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
        $quartier = quartierParId($_POST['quartier'] ?? 0);
        $eau = str_replace(',', '.', trim($_POST['indexPrecendentEau'] ?? ''));
        $electricite = str_replace(',', '.', trim($_POST['indexPrecendentElec'] ?? ''));
        if ($adresse === '' || !$quartier
            || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $eau)
            || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $electricite)) {
            http_response_code(400); echo 'Adresse, quartier ou index invalide.'; return;
        }
        $idAdresse = insertAdresseCompteur($id, $_SESSION['user']['id'], $adresse, $quartier['typeActivite'], (float)$eau, (float)$electricite, (int)$quartier['id']);
        include_once(ROOT."/Traitement/domPDFTraitement.php");
        $pdf = genererPdfContrat(["nom" => $client["nom"], "prenom" => $client["prenom"], "adresse" => $adresse]);
        $nomFichier = 'contrat-'.(int)$id.'-'.$idAdresse.'.pdf';
        $cheminGeneration = ROOT.'stockageContrats/'.$nomFichier;
        file_put_contents($cheminGeneration,$pdf);
        ajouterContrat($id,$cheminGeneration,$idAdresse);
        envoyerContratParEmail($client, $pdf, $nomFichier, $adresse);
        header('Location:'.link.'consommateur/modifier/'.$id); exit;
    }

    function envoyerContratParEmail($client, $pdf, $nomFichier, $adresse)
    {
        if (!getenv('SMTP_PASS') || !filter_var($client['email'] ?? '', FILTER_VALIDATE_EMAIL)) return false;
        try {
            $email = new PHPMailer(true);
            $email->isSMTP();
            $email->SMTPAuth = TRUE;
            $email->Host = 'smtp.gmail.com';
            $email->Port = '587';
            $email->Username = 'farhan.othmane@etu.uae.ac.ma';
            $email->Password = getenv('SMTP_PASS');
            $email-> SMTPSecure = 'tls';
            $email->CharSet = 'UTF-8';
            $email->setFrom('farhan.othmane@etu.uae.ac.ma','Fatourti');
            $email->AddAddress($client["email"],$client["nom"].' '.$client["prenom"]);
            $email->Subject = 'Votre nouveau contrat Fatourti';
            $email->Body = "Bonjour ".$client["prenom"].",\n\nVous trouverez en pièce jointe le contrat de votre nouvelle adresse : ".$adresse.".\n\nFatourti";
            $email->addStringAttachment($pdf,$nomFichier);
            return $email->send();
        } catch (\PHPMailer\PHPMailer\Exception $erreur) {
            error_log('Fatourti contrat : échec de l\'envoi par email.');
            return false;
        }
    }

    function resilier($id)
    {
        if (!ctype_digit((string)$id)) {
            http_response_code(404); echo 'Client introuvable.'; return;
        }
        $client = selectConsommateur($id);
        if (!$client || (int)$client['idFournisseur'] !== (int)($_SESSION['user']['id'] ?? 0)) {
            http_response_code(403); echo 'Client non autorisé.'; return;
        }
        if (empty($_SESSION['csrf_resiliation'])) {
            $_SESSION['csrf_resiliation'] = bin2hex(random_bytes(32));
        }
        $contrats = contratsDuConsommateur($id);
        $contratsActifs = array_values(array_filter($contrats, function ($c) { return $c['statut'] === 'actif'; }));
        $contratsEnResiliation = array_values(array_filter($contrats, function ($c) { return $c['statut'] === CONTRAT_EN_RESILIATION; }));
        $erreur = '';
        if($_SERVER["REQUEST_METHOD"] =="POST")
            {
                $action = (string)($_POST['action'] ?? 'resilier');
                $choisis = is_array($_POST['contrats'] ?? null) ? array_map('intval', $_POST['contrats']) : [];
                $permis = array_map('intval', array_column($action === 'cloturer' ? $contratsEnResiliation : $contratsActifs, 'id'));
                if (!hash_equals($_SESSION['csrf_resiliation'], (string)($_POST['csrf'] ?? ''))) {
                    $erreur = 'Session expirée : rechargez la page.';
                } else if (!$choisis) {
                    $erreur = 'Cochez au moins un contrat.';
                } else if (array_diff($choisis, $permis)) {
                    $erreur = 'Sélection invalide : rechargez la page.';
                } else {
                    $parId = array_column($contrats, null, 'id');
                    foreach ($choisis as $idContrat) {
                        $contrat = $parId[$idContrat];
                        $idAdresse = (int)$contrat['idAdresseCompteur'];
                        if ($action === 'cloturer') {
                            resilier_BDD($idContrat);
                            resoudreNotification((int)$id, 'contrat_fin', $idAdresse);
                            notifier((int)$id, 'contrat_resilie', 'Contrat résilié', 'Votre contrat pour '.$contrat['adresse'].' est terminé.', 'consommateur/tableauDeBord', array('idObjet' => $idAdresse, 'email' => true));
                        } else if (demarrerResiliation($idContrat)) {
                            notifier((int)$id, 'contrat_fin', 'Déclarez votre relevé final', 'Votre contrat pour '.$contrat['adresse'].' est en cours de résiliation. Votre dernier relevé permet d\'établir la facture finale.', 'declaration/all/'.$idAdresse, array('aTraiter' => true, 'idObjet' => $idAdresse, 'email' => true));
                        }
                    }
                    unset($_SESSION['csrf_resiliation']);
                    header("Location:".link."consommateur/modifier/".$id);
                    exit;
                }
            }
        include_once(ROOT."IHM/validerModal.php");
    }

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
