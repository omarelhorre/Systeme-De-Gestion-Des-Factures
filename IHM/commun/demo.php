<?php
// Barre du mode démo (en bas de l'écran) : la date affichée et des raccourcis pour la changer.
// Incluse par coque.php, seulement si DEMO=1. Voir Traitement/demo.php.
if (!function_exists('demoActive') || !demoActive()) return;
$demoMois = array('', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.');
$demoJour = new DateTimeImmutable('now', new DateTimeZone('Africa/Casablanca'));
$demoDecale = demoDecalage() !== 0;
?>
<form class="demo" method="post" action="<?= link ?>demo/aller/date" aria-label="Mode démo : changer la date">
    <span class="demo-date<?= $demoDecale ? ' decale' : '' ?>" title="<?= $demoDecale ? 'Date de démo' : 'Vraie date' ?>">
        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
        <?= $demoDecale ? 'Démo · ' : '' ?><?= (int)$demoJour->format('j').' '.$demoMois[(int)$demoJour->format('n')].' '.$demoJour->format('Y') ?>
    </span>
    <?php foreach (demoRaccourcis() as $cle => $raccourci) { ?>
        <button type="submit" formaction="<?= link ?>demo/aller/<?= $cle ?>"><?= $raccourci[0] ?></button>
    <?php } ?>
    <!-- n'importe quelle date : le bouton ouvre le calendrier du navigateur -->
    <input type="date" name="date" id="demo-date" tabindex="-1" aria-hidden="true" onchange="this.form.submit()">
    <button type="button" onclick="document.getElementById('demo-date').showPicker()">Autre date…</button>
    <?php if ($demoDecale) { ?>
        <button type="submit" class="demo-reel" formaction="<?= link ?>demo/aller/reel">Date réelle</button>
    <?php } ?>
</form>
