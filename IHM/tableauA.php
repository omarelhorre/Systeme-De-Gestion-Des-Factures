<?php
// Page de l'agent : dépôt du fichier annuel. Variables fournies par traiterImportAnnuel().
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

    <?php if ($fichierImporte): ?>
        <section class="carte">
            <h2 class="titre-carte">Fichier importé pour <?=$annee?></h2>
            <p class="legende"><i class="fa-solid fa-circle-check" style="color: var(--vert)"></i> Votre fichier annuel a déjà été enregistré. Un second dépôt pour cette année n'est pas nécessaire.</p>
        </section>
    <?php else: ?>
        <div class="grille">
            <section class="carte large">
                <h2 class="titre-carte">Importer le fichier</h2>
                <form class="pile" style="margin-top: 1rem" method="post" action="<?=link?>agent/importer" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['csrf_annuel'], ENT_QUOTES, 'UTF-8')?>">
                    <label class="champ"><span class="libelle">Consommation_Annuelle.txt</span>
                        <input type="file" name="fichier" accept=".txt,text/plain" required>
                    </label>
                    <div class="actions fin">
                        <button class="bouton accent" type="submit"><i class="fa-solid fa-file-arrow-up"></i> Importer le fichier</button>
                    </div>
                </form>
            </section>

            <section class="carte large">
                <h2 class="titre-carte">Format attendu</h2>
                <p class="legende" style="margin-bottom: 0.75rem">Une ligne par client, six champs séparés par des espaces :</p>
                <pre class="code">ID_Client ID_Agent ConsommationEau ConsommationElect Annee Date_Saisie
17 <?=$agent?> 120.0000 850.0000 <?=$annee?> <?=$annee?>-12-31</pre>
                <p class="note" style="margin-top: 0.75rem">Remplacez 17 par l'identifiant réel du client. Les consommations sont les totaux annuels, en m³ et en kWh.</p>
            </section>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
