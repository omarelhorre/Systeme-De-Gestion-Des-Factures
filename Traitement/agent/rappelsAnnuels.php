<?php
require_once ROOT.'BD/annuelle.php';
require_once ROOT.'vendor/autoload.php';

function envoyerEmailRegularisation($ligne)
{
    if (getenv('ANNUAL_MAIL_DRY_RUN') === '1') return true;
    if (!getenv('SMTP_PASS') || !filter_var($ligne['email'], FILTER_VALIDATE_EMAIL)) return false;
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
        $mail->addAddress($ligne['email'], $ligne['prenom'].' '.$ligne['nom']);
        $mail->Subject = 'Régularisation de votre consommation annuelle '.$ligne['annee'];
        $mail->Body = 'Bonjour '.$ligne['prenom'].' '.$ligne['nom']."\n\n".
            'Le relevé annuel de notre agent présente un écart par rapport à vos déclarations mensuelles.' . "\n" .
            'Différence eau : '.number_format((float)$ligne['differenceEau'], 4, ',', ' ')." m³\n".
            'Différence électricité : '.number_format((float)$ligne['differenceElectricite'], 4, ',', ' ')." kWh\n".
            'Reste à payer estimé TTC : '.number_format((float)$ligne['montantEstime'], 2, ',', ' ')." DH.\n".
            'Cette estimation utilise les tranches tarifaires du sujet et la TVA de 18 % ; le fournisseur confirmera le montant.' . "\n\n" .
            'Veuillez vous présenter chez votre fournisseur au plus tard le '.$ligne['dateLimite'].' pour régulariser ce montant.' . "\n" .
            'Pour éviter tout problème juridique, veuillez effectuer le règlement avant cette date.' . "\n" .
            'Pour toute réclamation, veuillez consulter la zone de réclamation dans votre espace Fatourti.' . "\n\n" .
            'Fatourti';
        return $mail->send();
    } catch (Throwable $e) {
        error_log('Rappel annuel : échec SMTP pour regularisation #'.(int)$ligne['id']);
        return false;
    }
}

function envoyerRappelsAnnuels($db)
{
    $envoyes = 0;
    foreach (rappelsAnnuelsEnAttente($db) as $ligne) {
        $id = (int)$ligne['id'];
        if (!reserverRappelAnnuel($db, $id)) continue;
        if (envoyerEmailRegularisation($ligne)) $envoyes++;
        else libererRappelAnnuel($db, $id);
    }
    return $envoyes;
}
