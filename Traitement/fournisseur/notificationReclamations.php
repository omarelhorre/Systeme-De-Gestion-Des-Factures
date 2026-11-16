<?php
function gestionReclamationsNotifier($client, $statut, $reponse)
{
    // Réutiliser PHPMailer et le compte SMTP déjà employés par le projet.
    if (!getenv('SMTP_PASS') || !filter_var($client['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    require_once __DIR__.'/../../vendor/autoload.php';
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
        $mail->addAddress($client['email'], $client['nom'].' '.$client['prenom']);
        $mail->Subject = 'Suivi de votre réclamation #'.(int)$client['idReclamation'];
        $mail->Body = 'Bonjour '.$client['prenom'].",\n\n".
            'Votre fournisseur a mis à jour votre réclamation #'.(int)$client['idReclamation'].".\n".
            'Statut : '.$statut."\n\n".$reponse."\n\nVotre fournisseur — Fatourti";
        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $erreur) {
        error_log('Notification réclamation : échec SMTP.');
        return false;
    }
}
