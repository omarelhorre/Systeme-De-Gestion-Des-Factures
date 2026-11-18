<?php
    include_once(ROOT."BD/notification.php");

    function notifier($idUtilisateur, $type, $titre, $detail, $lien, $options = array())
    {
        try {
            if (!$idUtilisateur) return;
            notificationCreer((int)$idUtilisateur, $type, $titre, $detail, $lien,
                !empty($options['aTraiter']), $options['idObjet'] ?? null);
            if (!empty($options['email'])) {
                envoyerEmailNotification((int)$idUtilisateur, $titre, $detail, $lien);
            }
        } catch (Throwable $e) {
            error_log('Fatourti notification : '.$e->getMessage());
        }
    }

    function resoudreNotification($idUtilisateur, $type, $idObjet = null)
    {
        try {
            if ($idUtilisateur) notificationResoudre((int)$idUtilisateur, $type, $idObjet);
        } catch (Throwable $e) {
            error_log('Fatourti notification : '.$e->getMessage());
        }
    }

    function envoyerEmailNotification($idUtilisateur, $titre, $detail, $lien)
    {
        $destinataire = notificationDestinataire($idUtilisateur);
        if (!getenv('SMTP_PASS') || !$destinataire || !filter_var($destinataire['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        require_once ROOT.'vendor/autoload.php';
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->Username = getenv('SMTP_USER') ?: 'farhan.othmane@etu.uae.ac.ma';
            $mail->Password = getenv('SMTP_PASS');
            $mail->SMTPSecure = 'tls';
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($mail->Username, 'Fatourti');
            $mail->addAddress($destinataire['email'], $destinataire['prenom'].' '.$destinataire['nom']);
            $mail->Subject = $titre;
            $mail->Body = 'Bonjour '.$destinataire['prenom'].",\n\n".$titre."\n".$detail."\n\n".
                'Ouvrir dans Fatourti : '.link.$lien."\n\nFatourti";
            return $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('Fatourti notification : échec SMTP.');
            return false;
        }
    }

    function accueillirUtilisateur($idUtilisateur)
    {
        try {
            if (notificationExiste((int)$idUtilisateur, 'bienvenue')) return;
        } catch (Throwable $e) {
            return;
        }
        notifier($idUtilisateur, 'bienvenue', 'Bienvenue sur Fatourti',
            'Pour votre sécurité, changez le mot de passe reçu par email.',
            'utilisateur/profil', array('aTraiter' => true, 'email' => true));
    }

    function notifications($action, $id = null)
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, private');
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(array('erreur' => 'Connexion requise.'));
            return;
        }
        $idUtilisateur = (int)$_SESSION['user']['id'];
        if (empty($_SESSION['csrf_notifications'])) {
            $_SESSION['csrf_notifications'] = bin2hex(random_bytes(32));
        }

        if ($action == 'lire' || $action == 'toutLire')
            {
                if ($_SERVER["REQUEST_METHOD"] != "POST"
                    || !hash_equals($_SESSION['csrf_notifications'], (string)($_POST['csrf'] ?? ''))) {
                    http_response_code(403);
                    echo json_encode(array('erreur' => 'Session expirée.'));
                    return;
                }
                if ($action == 'lire' && ctype_digit((string)$id)) notificationLire((int)$id, $idUtilisateur);
                if ($action == 'toutLire') notificationsToutLire($idUtilisateur);
            }
        else if ($action != 'all')
            {
                http_response_code(404);
                echo json_encode(array('erreur' => 'Action inconnue.'));
                return;
            }

        $jetonReclamations = null;
        if (checkFournisseur($idUtilisateur)) {
            if (empty($_SESSION['csrf_gestion_reclamations'])) {
                $_SESSION['csrf_gestion_reclamations'] = bin2hex(random_bytes(32));
            }
            $jetonReclamations = $_SESSION['csrf_gestion_reclamations'];
        }

        echo json_encode(array(
            'notifications' => notificationsDe($idUtilisateur),
            'nonLues' => notificationsNonLues($idUtilisateur),
            'csrf' => $_SESSION['csrf_notifications'],
            'csrfReclamations' => $jetonReclamations,
            'maintenant' => notificationMaintenant(),
        ));
    }
?>
