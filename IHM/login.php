<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion · Fatourti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Arabic:wght@500;600&family=IBM+Plex+Mono:wght@500&family=Noto+Sans+Tifinagh&display=swap" rel="stylesheet">
    <link href="<?= dir ?>/public/style/tokens.css" rel="stylesheet">
    <link href="<?= dir ?>/public/style/login.css" rel="stylesheet">
    <script src="<?= dir ?>/public/script/login.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#connexion">Aller au formulaire de connexion</a>

    <!-- Bandeau institutionnel : couleurs du drapeau, sans emblème officiel -->
    <div class="band" aria-hidden="true"></div>
    <p class="notice">Service de démonstration réalisé dans le cadre d'un projet académique. Ce site n'est pas un service officiel.</p>

    <div class="page">
        <header class="masthead">
            <!-- Sceau propre à Fatourti (la rosace est dessinée par login.js) -->
            <svg class="seal" viewBox="0 0 100 100" aria-hidden="true">
                <defs>
                    <path id="seal-ring" d="M50 50 m-38 0 a38 38 0 1 1 76 0 a38 38 0 1 1 -76 0"/>
                </defs>
                <circle cx="50" cy="50" r="48" class="seal-line"/>
                <circle cx="50" cy="50" r="44.5" class="seal-line thin"/>
                <circle cx="50" cy="50" r="30" class="seal-line thin"/>
                <text class="seal-text"><textPath href="#seal-ring" startOffset="0">FATOURTI · SERVICE DES COMPTEURS · EAU · ÉLECTRICITÉ ·</textPath></text>
                <g class="rosette" data-r="27"></g>
                <text x="50" y="58" class="seal-letter">F</text>
            </svg>

            <div class="identity">
                <p class="wordmark">Fatourti</p>
                <p class="names">
                    <span lang="ar" dir="rtl">فاتورتي</span>
                    <span lang="zgh">ⴼⴰⵜⵓⵔⵜⵉ</span>
                </p>
                <p class="service">Service de facturation de l'eau et de l'électricité</p>
            </div>
        </header>

        <main class="main" id="contenu">
            <section class="intro">
                <h1>Votre eau et votre électricité, déclarées et facturées en ligne.</h1>
                <p class="lead">Relevez vos compteurs, téléchargez vos factures et suivez vos réclamations depuis votre espace.</p>
            </section>

            <!-- Plaque des compteurs : électricité = angles droits, eau = formes rondes -->
                <div class="plate" aria-hidden="true">
                    <svg class="plate-guilloche" viewBox="0 0 400 200" preserveAspectRatio="xMidYMid slice"></svg>

                    <div class="meter meter-elec">
                        <p class="meter-name">Électricité</p>
                        <div class="lcd">
                            <span class="lcd-ghost">88888</span>
                            <span class="lcd-value" data-start="4827">04827</span>
                        </div>
                        <p class="meter-unit">kWh</p>
                    </div>

                    <div class="meter meter-water">
                        <p class="meter-name">Eau</p>
                        <div class="drums" data-start="3124">
                            <!-- 4 tambours entiers + 1 tambour décimal (rempli par login.js) -->
                        </div>
                        <p class="meter-unit">m³</p>
                    </div>
                </div>

            <section class="card" id="connexion" aria-labelledby="titre-connexion">
                <h2 id="titre-connexion">Connexion</h2>
                <p class="card-hint">Accès consommateur, fournisseur et agent.</p>

                <form action="./authentification" method="post" class="form">
                    <div class="field">
                        <label for="email">Adresse e-mail</label>
                        <input type="email" id="email" name="email" autocomplete="email" required>
                    </div>
                    <div class="field">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn-main" type="submit">
                        Se connecter
                        <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 8h9M8.5 4.5 12 8l-3.5 3.5"/></svg>
                    </button>
                </form>

                <p class="card-foot">Pas encore d'espace&nbsp;? Il est créé par votre fournisseur à la signature du contrat. Votre mot de passe vous est envoyé par e-mail.</p>
            </section>
        </main>

        <footer class="footer">
            <span>Fatourti · 2026</span>
            <span>Projet académique · Système de gestion des factures</span>
        </footer>
    </div>
</body>
</html>
