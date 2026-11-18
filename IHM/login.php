<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion · Fatourti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=Inter:wght@400;500;600&family=Outfit:wght@600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="<?= dir ?>/public/style/tokens.css" rel="stylesheet">
    <link href="<?= dir ?>/public/style/login.css" rel="stylesheet">
    <script src="<?= dir ?>/public/script/login.js" defer></script>
</head>
<body>
<main class="page-auth">
    <div class="auth-cadre entree">
        <header class="auth-entete">
            <span class="marque">Fatourti</span>
        </header>

        <div class="auth-corps">
            <section class="auth-visuel">
                <img src="<?= dir ?>/public/img/loginImg.jpg" alt="">
            </section>

            <section class="auth-cote">
                <form class="auth-formulaire" action="<?= link ?>utilisateur/authentification" method="post">
                    <h1 class="auth-titre"><span>Ea<span class="lettre-eau">u</span> et <span class="lettre-elec">é</span>lectricité,</span><span class="gris">une seule facture.</span></h1>

                    <label class="auth-champ">
                        <i class="auth-icone fa-regular fa-envelope" aria-hidden="true"></i>
                        <input type="email" name="email" placeholder="Email" aria-label="Email" autocomplete="email" required>
                    </label>

                    <label class="auth-champ">
                        <i class="auth-icone fa-solid fa-lock" aria-hidden="true"></i>
                        <input type="password" name="password" placeholder="Mot de passe" aria-label="Mot de passe" autocomplete="current-password" required>
                        <button class="auth-oeil" type="button" aria-label="Afficher le mot de passe">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </label>

                    <button class="auth-valider" type="submit">
                        Se connecter
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>
            </section>
        </div>
    </div>
</main>
</body>
</html>
