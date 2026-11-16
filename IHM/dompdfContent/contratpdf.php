<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body
        {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
        }
        h1
        {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Contrat de service Fatourti</h1>
    <p>
        Client : <?= $client["prenom"].' '.$client["nom"]?>
    </p>
    <p>
        Adresse:
        <?= $client["adresse"]?>
    </p>
     <p>Le client accepte les conditions de fourniture et de facturation du service.</p>
     <p>Date: <?= date('d/m/Y') ?></p>
</body>
</html>