<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion · Fatourti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=Inter:wght@400;500;600&family=Outfit:wght@600;800&family=JetBrains+Mono:wght@500&family=IBM+Plex+Sans+Arabic:wght@500&family=Noto+Sans+Tifinagh&display=swap" rel="stylesheet">
    <link href="<?= dir ?>/public/style/tokens.css" rel="stylesheet">
    <link href="<?= dir ?>/public/style/login.css" rel="stylesheet">
    <script src="<?= dir ?>/public/script/login.js" defer></script>
</head>
<body>
<main class="page-auth">
    <div class="auth-cadre entree">
        <header class="auth-entete">
            <span class="marque">
                <!-- Sceau propre à Fatourti (la rosace est dessinée par login.js) -->
                <svg class="sceau" viewBox="0 0 100 100" aria-hidden="true">
                    <defs><path id="anneau" d="M50 50 m-38 0 a38 38 0 1 1 76 0 a38 38 0 1 1 -76 0"/></defs>
                    <circle cx="50" cy="50" r="47"/>
                    <text class="sceau-texte"><textPath href="#anneau">FATOURTI · EAU · ÉLECTRICITÉ · SERVICE DES COMPTEURS ·</textPath></text>
                    <g class="rosace"></g>
                    <text x="50" y="59" class="sceau-lettre">F</text>
                </svg>
                Fatourti
            </span>
            <p class="noms">
                <span lang="ar" dir="rtl">فاتورتي</span>
                <span lang="zgh">ⴼⴰⵜⵓⵔⵜⵉ</span>
            </p>
            <span></span>
        </header>

        <div class="auth-corps">
            <!-- Visuel : les deux compteurs. Électricité = angles droits, eau = formes rondes -->
            <section class="auth-visuel" aria-hidden="true">
                <svg class="guilloche" viewBox="0 0 400 400" preserveAspectRatio="xMidYMid slice"></svg>

                <div class="compteur">
                    <p class="compteur-nom elec">Électricité</p>
                    <div class="lcd">
                        <span class="lcd-eteint">88888</span>
                        <span class="lcd-valeur" data-depart="4827">04827</span>
                    </div>
                    <p class="compteur-unite">kWh</p>
                </div>

                <div class="compteur">
                    <p class="compteur-nom eau">Eau</p>
                    <div class="tambours" data-depart="3124"></div>
                    <p class="compteur-unite">m³</p>
                </div>
            </section>

            <section class="auth-cote">
                <form class="auth-formulaire" action="./authentification" method="post">
                    <h1 class="auth-titre"><span>Eau et électricité,</span><span class="gris">une seule facture.</span></h1>
                    <p class="auth-accroche">Connectez-vous pour déclarer vos index, télécharger vos factures et suivre vos réclamations.</p>
                    <p class="auth-sous-titre">Pas encore d'espace ? Votre fournisseur le crée à la signature du contrat.</p>

                    <label class="auth-champ">
                        <svg class="auth-icone" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                        <input type="email" name="email" placeholder="Email" aria-label="Email" autocomplete="email" required>
                    </label>

                    <label class="auth-champ">
                        <svg class="auth-icone" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        <input type="password" name="password" placeholder="Mot de passe" aria-label="Mot de passe" autocomplete="current-password" required>
                        <button class="auth-oeil" type="button" aria-label="Afficher le mot de passe">
                            <svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </label>

                    <button class="auth-valider" type="submit">
                        Se connecter
                        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>

                    <p class="aide">Projet académique · service non officiel</p>
                </form>
            </section>
        </div>
    </div>
</main>
</body>
</html>
