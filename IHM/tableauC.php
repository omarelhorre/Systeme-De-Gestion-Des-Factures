<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord consommateur — Fatourti</title>
    <link rel="stylesheet" href="<?= dir ?>/public/style/dashboardF.css">
    <link rel="stylesheet" href="<?= dir ?>/public/style/consommation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body class="consumer-page">
<nav class="menu" aria-label="Navigation consommateur">
    <ul class="menu-list">
        <li><span class="title">Fatourti</span></li>
        <li>
            <div class="grid">
                <div class="border selected">
                    <a class="section-txt" href="<?= link ?>consommateur/tableauDeBord" aria-current="page"><i class="fa-solid fa-house"></i> Consulter le tableau de bord</a>
                </div>
                <div class="border">
                    <a class="section-txt" href="<?= link ?>declaration/all"><i class="fa-solid fa-pen"></i> Déclarer consommation</a>
                </div>
                <div class="border">
                    <a class="section-txt" href="<?= link ?>reclamations.php"><i class="fa-solid fa-circle-exclamation"></i> Mes réclamations</a>
                </div>
            </div>
        </li>
        <li>
            <div class="connexion-section">
                <div class="login-creds">
                    <p class="identity"><?= htmlspecialchars(($_SESSION['user']['nom'] ?? '') . ' ' . ($_SESSION['user']['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="role-style">Consommateur</p>
                </div>
                <a class="element" href="<?= link ?>utilisateur/deconnexion"><button class="connexion-btn" type="button"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</button></a>
            </div>
        </li>
    </ul>
</nav>
<main class="main">
    <div class="container">
        <h1>Tableau de bord</h1>
        <p>Consultez vos relevés et transmettez votre consommation mensuelle.</p>
        <a class="add-btn" href="<?= link ?>declaration/all">Déclarer consommation</a>
    </div>
</main>
</body>
</html>
