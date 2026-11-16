<?php function texteBilan($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification annuelle - Fatourti</title>
    <link rel="stylesheet" href="<?=dir?>/public/style/dashboardF.css">
    <link rel="stylesheet" href="<?=dir?>/public/style/facturesFournisseur.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body class="factures-module">
<nav class="menu" aria-label="Navigation fournisseur">
    <ul class="menu-list">
        <li><span class="title">Fatourti</span></li>
        <li><div class="grid">
            <div class="border"><a class="section-txt" href="<?=link?>fournisseur/tableauDeBord">Consulter le tableau de bord</a></div>
            <div class="border"><a class="section-txt" href="<?=link?>consommateur/all">Consulter Vos Clients</a></div>
            <div class="border"><a class="section-txt" href="<?=link?>facture/all">Consulter les factures</a></div>
            <div class="border"><a class="section-txt" href="<?=link?>declaration/anomalies">Corriger les anomalies</a></div>
            <div class="border selected"><a class="section-txt" href="<?=link?>annuelle/all">Vérifier Consommation Annuelle</a></div>
        </div></li>
        <li><div class="connexion-section"><div class="login-creds"><p class="identity"><?=texteBilan($_SESSION['user']['nom'].' '.$_SESSION['user']['prenom'])?></p><p class="role-style">Fournisseur</p></div><a class="element" href="<?=link?>utilisateur/deconnexion">Déconnexion</a></div></li>
    </ul>
</nav>
<main class="main"><div class="container">
    <h1>Vérification annuelle <?=$annee?></h1>
    <p>Comparaison des consommations mensuelles validées et du relevé annuel de l'agent, pour toutes les adresses de chaque client.</p>
    <?php if ($message): ?><p class="notice" role="status"><?=texteBilan($message)?></p><?php endif; ?>
    <nav class="filters"><a class="filter" href="<?=link?>annuelle/all/<?=max(2000,$annee-1)?>">Année précédente</a><?php if ($annee < $anneeActuelle): ?><a class="filter" href="<?=link?>annuelle/all/<?=$annee+1?>">Année suivante</a><?php endif; ?></nav>
    <div class="table-scroll"><table>
        <thead><tr><th>Client</th><th>Déclaration annuelle du client</th><th>Relevé annuel de l'agent</th><th>Différence (agent − client)</th><th>Reste estimé TTC</th><th>Rappel</th></tr></thead>
        <tbody>
        <?php foreach ($data as $ligne): ?>
        <tr>
            <td><?=texteBilan($ligne['nom'].' '.$ligne['prenom'])?><br>#<?=texteBilan($ligne['idConsommateur'])?></td>
            <td>Eau : <?=texteBilan(number_format($ligne['eauDeclaree'],4,',',' '))?> m³<br>Électricité : <?=texteBilan(number_format($ligne['electriciteDeclaree'],4,',',' '))?> kWh</td>
            <td><?php if ($ligne['idAnnuel']): ?>Eau : <?=texteBilan($ligne['ConsommationEau'])?> m³<br>Électricité : <?=texteBilan($ligne['ConsommationElectricite'])?> kWh<?php else: ?>En attente du fichier agent<?php endif; ?></td>
            <td><?php if ($ligne['idAnnuel']): ?>Eau : <?=texteBilan(number_format($ligne['ecartEau'],4,',',' '))?> m³<br>Électricité : <?=texteBilan(number_format($ligne['ecartElectricite'],4,',',' '))?> kWh<?php else: ?>—<?php endif; ?></td>
            <td><?= $ligne['idRegularisation'] ? texteBilan(number_format((float)$ligne['montantEstime'],2,',',' ')).' DH' : 'Aucun reste estimé' ?></td>
            <td>
                <?php if ($ligne['dateReglement']): ?>Payé le <?=texteBilan($ligne['dateReglement'])?>
                <?php elseif ($ligne['idRegularisation']): ?>
                    Échéance : <?=texteBilan($ligne['dateLimite'])?><br>
                    Rappels envoyés : <?=texteBilan($ligne['nombreEnvois'])?>
                    <form method="post" action="<?=link?>annuelle/regler/<?=texteBilan($ligne['idRegularisation'])?>">
                        <input type="hidden" name="csrf" value="<?=texteBilan($_SESSION['csrf_bilan_annuel'])?>">
                        <input type="hidden" name="annee" value="<?=$annee?>">
                        <button class="connexion-btn" type="submit">Marquer payé</button>
                    </form>
                <?php else: ?><?= $ligne['idAnnuel'] ? 'Aucun rappel nécessaire' : 'En attente du fichier agent' ?><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$data): ?><tr><td colspan="6">Aucun client enregistré.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <p>Estimation : tranches mensuelles du sujet appliquées à la moyenne annuelle, puis TVA de 18 %. Le fournisseur confirme le montant final.</p>
</div></main>
</body>
</html>
