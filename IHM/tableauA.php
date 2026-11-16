<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importation annuelle - Fatourti</title>
    <link rel="stylesheet" href="<?=dir?>/public/style/dashboardF.css">
    <link rel="stylesheet" href="<?=dir?>/public/style/consommation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body class="consumer-page">
<nav class="menu" aria-label="Navigation agent">
    <ul class="menu-list">
        <li><span class="title">Fatourti</span></li>
        <li><div class="grid"><div class="border selected"><a class="section-txt" href="<?=link?>agent/tableauDeBord"><i class="fa-solid fa-file-arrow-up"></i> Importation annuelle</a></div></div></li>
        <li><div class="connexion-section"><div class="login-creds"><p class="identity"><?=htmlspecialchars($_SESSION['user']['nom'].' '.$_SESSION['user']['prenom'], ENT_QUOTES, 'UTF-8')?></p><p class="role-style">Agent</p></div><a class="element" href="<?=link?>utilisateur/deconnexion">Déconnexion</a></div></li>
    </ul>
</nav>
<main class="main"><div class="container">
    <h1>Consommation annuelle <?=$annee?></h1>
    <p>Déposez le fichier texte relevé lors de la tournée annuelle.</p>
    <?php if ($message): ?><p class="message" role="status"><?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?></p><?php endif; ?>
    <?php if ($erreur): ?><p class="erreur" role="alert"><?=htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8')?></p><?php endif; ?>
    <section class="carte">
        <?php if ($fichierImporte): ?>
            <h2>Fichier importé pour <?=$annee?></h2>
            <p>Votre fichier annuel a déjà été enregistré. Un second dépôt pour cette année n'est pas nécessaire.</p>
        <?php else: ?>
            <h2>Importer Consommation_Annuelle.txt</h2>
            <p>Une ligne par client, six champs séparés par des espaces :</p>
            <pre>ID_Client ID_Agent ConsommationEau ConsommationElect Annee Date_Saisie
17 <?=$agent?> 120.0000 850.0000 <?=$annee?> <?=$annee?>-12-31</pre>
            <p>Remplacez 17 par l'identifiant réel du client. Les consommations sont les totaux annuels, en m³ et en kWh.</p>
            <form method="post" action="<?=link?>agent/importer" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_annuel'], ENT_QUOTES, 'UTF-8')?>">
                <label>Fichier .txt<input type="file" name="fichier" accept=".txt,text/plain" required></label>
                <p><button type="submit">Importer le fichier</button></p>
            </form>
        <?php endif; ?>
    </section>
</div></main>
</body>
</html>
