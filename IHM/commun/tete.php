<?php
// Contenu commun du <head> des pages connectées.
// Avant l'include, la page définit $coqueTitre (le titre de l'onglet).
// ?v=... : date de modification du fichier, pour que le navigateur recharge les styles après chaque mise à jour.
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($coqueTitre ?? 'Fatourti', ENT_QUOTES, 'UTF-8') ?> · Fatourti</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=Inter:wght@400;500;600&family=Outfit:wght@600;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link href="<?= dir ?>/public/style/tokens.css?v=<?= filemtime(ROOT.'public/style/tokens.css') ?>" rel="stylesheet">
    <link href="<?= dir ?>/public/style/app.css?v=<?= filemtime(ROOT.'public/style/app.css') ?>" rel="stylesheet">
    <script src="<?= dir ?>/public/script/app.js?v=<?= filemtime(ROOT.'public/script/app.js') ?>" defer></script>
