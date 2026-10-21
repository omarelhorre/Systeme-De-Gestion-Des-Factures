<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déclarer mes consommations | Fatourti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= fatourtiDeclarationEscape($basePath . '/public/style/declaration.css') ?>">
</head>
<body>
    <aside class="menu">
        <ul class="menu-list">
            <li><a class="title" href="<?= fatourtiDeclarationEscape($basePath . '/?url=consommateur/tableauDeBord') ?>">Fatourti</a></li>
            <li>
                <nav class="grid" aria-label="Navigation consommateur">
                    <div class="border"><a class="section-txt" href="<?= fatourtiDeclarationEscape($basePath . '/?url=consommateur/tableauDeBord') ?>">♟ Consulter le tableau de bord</a></div>
                    <div class="border selected"><a class="section-txt" href="<?= fatourtiDeclarationEscape($basePath . '/?url=consommateur/declaration') ?>">☷ Déclarer mes consommations</a></div>
                </nav>
            </li>
            <li>
                <div class="connexion-section">
                    <div class="login-creds">
                        <p class="identity"><?= fatourtiDeclarationEscape($displayName) ?></p>
                        <p class="role-style">Consommateur</p>
                    </div>
                    <a class="connexion-btn" href="<?= fatourtiDeclarationEscape($basePath . '/?url=utilisateur/authentification') ?>">Déconnexion</a>
                </div>
            </li>
        </ul>
    </aside>

    <main class="main">
        <section class="container">
            <div class="centering">
                <a class="back-link" href="<?= fatourtiDeclarationEscape($basePath . '/?url=consommateur/tableauDeBord') ?>">← Tableau de bord</a>
                <div class="page-heading">
                    <div>
                        <p class="eyebrow">ESPACE CONSOMMATEUR · <?= fatourtiDeclarationEscape($monthLabel) ?></p>
                        <h1>Déclarer mes consommations</h1>
                        <p class="intro-text">Relevez les index de vos compteurs d’eau et d’électricité. Les index sont comparés à votre dernière déclaration.</p>
                    </div>
                    <span class="period-status <?= $canSubmit ? 'is-open' : 'is-closed' ?>">
                        <span class="status-dot"></span>
                        <?= $canSubmit ? 'Saisie ouverte' : ($day < 18 ? 'Ouverture le 18' : 'Déclaration enregistrée') ?>
                    </span>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="notice <?= strpos($message, 'Anomalie') === 0 || strpos($message, 'Une déclaration existe') === 0 ? 'notice-warning' : 'notice-success' ?>" role="status">
                        <span><?= fatourtiDeclarationEscape($message) ?></span>
                        <?php if ($invoiceId): ?>
                            <a class="button button-dark" href="<?= fatourtiDeclarationEscape($basePath . '/?url=consommateur/facture/' . $invoiceId) ?>">Télécharger ma facture PDF</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="notice notice-error" role="alert"><?= fatourtiDeclarationEscape($error) ?></div>
                <?php endif; ?>

                <?php if ($day < 18): ?>
                    <section class="panel closed-card">
                        <div class="calendar-icon">18</div>
                        <div><h2>Le formulaire ouvrira le 18</h2><p>Vous pourrez déclarer les index de vos compteurs du 18 au dernier jour de ce mois.</p></div>
                    </section>
                <?php elseif ($canSubmit): ?>
                    <form class="declaration-form" method="post" enctype="multipart/form-data" action="<?= fatourtiDeclarationEscape($basePath . '/?url=consommateur/declaration') ?>">
                        <input type="hidden" name="token" value="<?= fatourtiDeclarationEscape($_SESSION['fatourti_declaration_token']) ?>">
                        <section class="panel readings-card">
                            <div class="section-heading">
                                <div><p class="eyebrow">RELEVÉ DU MOIS</p><h2>Vos index de compteur</h2></div>
                                <span class="required-label">Tous les champs sont obligatoires</span>
                            </div>
                            <div class="meter-grid">
                                <article class="meter-panel">
                                    <div class="meter-title"><span class="meter-icon water-icon">≈</span><div><h3>Compteur d’eau</h3><p>Index en m³</p></div></div>
                                    <label for="indexEau">Nouvel index</label>
                                    <input id="indexEau" name="indexEau" type="number" step="0.01" min="0" required value="<?= fatourtiDeclarationEscape($existingWater) ?>" placeholder="Ex. 128.50">
                                    <p class="previous-reading">Index précédent <strong><?= number_format($previousWater, 2, ',', ' ') ?> m³</strong></p>
                                    <?php if ($previous && !empty($previous['cheminCompteurEauImg']) && is_file(ROOT . '/' . $previous['cheminCompteurEauImg'])): ?>
                                        <img class="previous-photo" src="<?= fatourtiDeclarationEscape($basePath . '/' . $previous['cheminCompteurEauImg']) ?>" alt="Dernière photo du compteur d’eau">
                                    <?php endif; ?>
                                    <label for="photoEau">Photo lisible du compteur</label>
                                    <input id="photoEau" name="photoEau" type="file" accept="image/jpeg,image/png,image/webp" <?= $current ? '' : 'required' ?>>
                                </article>
                                <article class="meter-panel">
                                    <div class="meter-title"><span class="meter-icon power-icon">ϟ</span><div><h3>Compteur électrique</h3><p>Index en kWh</p></div></div>
                                    <label for="indexElectricite">Nouvel index</label>
                                    <input id="indexElectricite" name="indexElectricite" type="number" step="0.01" min="0" required value="<?= fatourtiDeclarationEscape($existingElectricity) ?>" placeholder="Ex. 2450.00">
                                    <p class="previous-reading">Index précédent <strong><?= number_format($previousElectricity, 2, ',', ' ') ?> kWh</strong></p>
                                    <?php if ($previous && !empty($previous['cheminCompteurElectriciteImg']) && is_file(ROOT . '/' . $previous['cheminCompteurElectriciteImg'])): ?>
                                        <img class="previous-photo" src="<?= fatourtiDeclarationEscape($basePath . '/' . $previous['cheminCompteurElectriciteImg']) ?>" alt="Dernière photo du compteur électrique">
                                    <?php endif; ?>
                                    <label for="photoElectricite">Photo lisible du compteur</label>
                                    <input id="photoElectricite" name="photoElectricite" type="file" accept="image/jpeg,image/png,image/webp" <?= $current ? '' : 'required' ?>>
                                </article>
                            </div>
                            <p class="form-hint">La consommation mensuelle correspond au nouvel index moins l’index précédent. Une facture est créée si les index ne diminuent pas.</p>
                            <button class="button button-primary" type="submit"><?= $current ? 'Corriger et renvoyer ma déclaration' : 'Enregistrer ma déclaration' ?></button>
                        </section>
                    </form>
                <?php elseif ($current && $current['statut'] === 'Anomalie de saisie'): ?>
                    <section class="panel closed-card"><div class="calendar-icon">!</div><div><h2>Une correction est nécessaire</h2><p>Corrigez les index pendant la période de saisie, ouverte du 18 au dernier jour du mois.</p></div></section>
                <?php elseif (!$invoiceId && !$current): ?>
                    <section class="panel closed-card"><div><h2>La période de saisie est terminée</h2><p>La déclaration de ce mois n’est plus disponible.</p></div></section>
                <?php endif; ?>

                <p class="footnote">Les index d’eau et d’électricité sont vérifiés avant la création de la facture. TVA appliquée : 18 %.</p>
            </div>
        </section>
    </main>
</body>
</html>
