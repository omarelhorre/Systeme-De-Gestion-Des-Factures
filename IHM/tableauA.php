<?php
// Page de l'agent : dépôt du fichier annuel et liste des clients à relever.
// Variables fournies par traiterImportAnnuel() : $annee, $agent, $message, $erreur, $fichierImporte, $clientsAgent, $declareAnnee.
require_once ROOT.'IHM/commun/outils.php';   // indexFr()
$coqueTitre = 'Importation annuelle';
$coqueActif = 'tableau';
$coqueRole = 'Agent';
$coqueAccueil = 'agent/tableauDeBord';
$coqueMenu = array(
    array('tableau', 'agent/tableauDeBord', 'fa-file-arrow-up', 'Importation annuelle'),
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/commun/coque.php'; ?>

<main class="contenu entree">
    <header class="entete">
        <h1 class="titre">Consommation annuelle <?=$annee?></h1>
        <p class="sous-titre">Déposez le fichier texte relevé lors de la tournée annuelle.</p>
    </header>

    <?php if ($message): ?><p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?></span></p><?php endif; ?>
    <?php if ($erreur): ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?=htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8')?></span></p><?php endif; ?>

    <!-- Import du fichier : une barre, pas de grandes cartes -->
    <?php if ($fichierImporte): ?>
        <p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span>Votre fichier <?=$annee?> est déjà importé : le bilan annuel est entre les mains des fournisseurs.</span></p>
    <?php else: ?>
        <form class="barre-outils barre-import" method="post" action="<?=link?>agent/importer" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_annuel'], ENT_QUOTES, 'UTF-8')?>">
            <label class="fichier"><input type="file" name="fichier" accept=".txt,text/plain" required>
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Choisir le fichier .txt</span></label>
            <button class="bouton accent" type="submit"><i class="fa-solid fa-file-arrow-up"></i> Importer</button>
            <span class="espace"></span>
            <span class="note">Une ligne par client : <code>ID_Client ID_Agent Eau Électricité Année Date</code>, ex. <code><?= $clientsAgent ? (int)$clientsAgent[0]['id'] : 17 ?> <?=$agent?> 120.0000 850.0000 <?=$annee?> <?=$annee?>-12-31</code></span>
        </form>
    <?php endif; ?>

    <!-- Les clients : leur identifiant pour le fichier, et de quoi vérifier la tournée -->
    <section class="liste">
        <h2 class="titre-carte" style="margin: 1.25rem 0 0.75rem">Clients</h2>
        <?php if (!$clientsAgent): ?>
            <p class="vide">Aucun client enregistré.</p>
        <?php else: ?>
        <table class="tableau">
            <thead>
                <tr><th>ID client</th><th>Client</th><th class="secondaire">Identité</th><th class="cache-mobile">Fournisseur</th><th class="secondaire">Compteurs</th>
                    <th class="fin">Déclaré <?=$annee?></th><th class="fin">Relevé de la tournée</th></tr>
            </thead>
            <tbody data-pagine="8">
                <?php foreach ($clientsAgent as $c):
                    $declare = $declareAnnee[(int)$c['id']] ?? null; ?>
                <tr>
                    <td class="nombre fort"><?= (int)$c['id'] ?></td>
                    <td><span class="fort"><?= htmlspecialchars($c['prenom'].' '.$c['nom'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="secondaire nombre"><?= htmlspecialchars($c['cin'] ?: '—', ENT_QUOTES, 'UTF-8') ?><small><?= htmlspecialchars($c['telephone'] ?: '', ENT_QUOTES, 'UTF-8') ?></small></td>
                    <td class="cache-mobile"><?= htmlspecialchars($c['fournisseur'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="secondaire"><?= (int)$c['compteurs'] ?> compteur<?= $c['compteurs'] > 1 ? 's' : '' ?><small><div class="tronque" title="<?= htmlspecialchars((string)$c['quartiers'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['quartiers'] ?: 'Sans quartier', ENT_QUOTES, 'UTF-8') ?></div></small></td>
                    <td class="fin nombre"><?php if ($declare): ?><?= indexFr($declare['eau']) ?> m³<small><?= indexFr($declare['electricite']) ?> kWh</small><?php else: ?><span class="note">Rien déclaré</span><?php endif; ?></td>
                    <td class="fin nombre"><?php if ($c['releveEau'] !== null): ?><?= indexFr($c['releveEau']) ?> m³<small><?= indexFr($c['releveElectricite']) ?> kWh</small><?php else: ?><span class="note">À relever</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
