<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se Connecter</title>
    <link href=<?=dir."/public/style/login.css"?> rel="stylesheet"/>
    <script src=<?= dir."/public/script/login.js"?>></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar">
        <ul class="navbar-list">
            <li><span class="title">Fatourti<span></li>
            <li>
                <a class="element" href=<?=dir."utilisateur/authentification"?>>
                <button class="connexion-btn">Deconnexion</button>
                </a>
            </li>
        </ul>
        </nav>
    </header>
    <main class="main">
        <section style="max-width:720px;margin:48px auto;padding:32px;border:1px solid #e3e3e3;border-radius:16px;background:#fff;box-shadow:0 8px 28px #173d2c08">
            <p style="margin:0 0 8px;color:#6f6f6f;font-size:12px;font-weight:700;letter-spacing:1px">ESPACE CONSOMMATEUR</p>
            <h1 style="margin:0 0 10px;color:#1f1f1f;font-size:28px">Mes consommations</h1>
            <p style="margin:0 0 20px;color:#6b6b6b;line-height:1.6">Déclarez vos index d’eau et d’électricité à partir du 18 de chaque mois, puis téléchargez votre facture PDF après validation.</p>
            <a href="<?= dir ?>consommateur/declaration" style="display:inline-block;padding:13px 18px;border-radius:9px;background:#c8102e;color:#fff;font-weight:700;text-decoration:none">Déclarer mes consommations</a>
        </section>
    </main>
</body>
</html>