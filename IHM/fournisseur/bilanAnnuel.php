<?php function texteBilan($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); } ?>
<?php
$coqueTitre = 'Bilan annuel';
$coqueActif = 'annuel';
$jourBilan = function ($date) { return $date ? date('d/m/Y', strtotime($date)) : '—'; };
$quantite = function ($valeur) { return texteBilan(number_format((float)$valeur, 2, ',', ' ')); };
$iconeEau = '<i class="fa-solid fa-droplet" style="color: var(--eau)"></i>';
$iconeElec = '<i class="fa-solid fa-bolt" style="color: var(--elec)"></i>';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <?php if ($message): ?>
        <p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?=texteBilan($message)?></span></p>
    <?php endif; ?>

    <header class="entete ligne">
        <div>
            <h1 class="titre">Bilan annuel <?=$annee?></h1>
        </div>
        <nav class="filtres" aria-label="Changer d'année">
            <a href="<?=link?>annuelle/all/<?=max(2000,$annee-1)?>"><i class="fa-solid fa-arrow-left"></i>&nbsp; <?=max(2000,$annee-1)?></a>
            <a class="actif" aria-current="page"><?=$annee?></a>
            <?php if ($annee < $anneeActuelle): ?><a href="<?=link?>annuelle/all/<?=$annee+1?>"><?=$annee+1?> &nbsp;<i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
        </nav>
    </header>

    <section class="liste">
        <?php if ($data): ?>
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Client</th><th class="secondaire">Déclaré par le client</th><th class="secondaire">Relevé de l'agent</th>
                        <th class="cache-mobile">Différence</th><th class="fin">Reste estimé TTC</th><th>Rappel</th>
                    </tr>
                </thead>
                <tbody data-pagine="6">
                <?php foreach ($data as $ligne): ?>
                    <tr>
                        <td>
                            <div class="cellule-nom">
                                <i class="fa-solid fa-circle-user avatar-liste" aria-hidden="true"></i>
                                <div class="tronque"><span class="fort"><?=texteBilan($ligne['prenom'].' '.$ligne['nom'])?></span><small>N° <?=texteBilan($ligne['idConsommateur'])?></small></div>
                            </div>
                        </td>
                        <td class="secondaire nombre"><?=$iconeEau?> <?=$quantite($ligne['eauDeclaree'])?> m³<br><?=$iconeElec?> <?=$quantite($ligne['electriciteDeclaree'])?> kWh</td>
                        <td class="secondaire nombre"><?php if ($ligne['idAnnuel']): ?><?=$iconeEau?> <?=$quantite($ligne['ConsommationEau'])?> m³<br><?=$iconeElec?> <?=$quantite($ligne['ConsommationElectricite'])?> kWh<?php else: ?><span class="note">En attente du fichier agent</span><?php endif; ?></td>
                        <td class="nombre cache-mobile"><?php if ($ligne['idAnnuel']): ?><?=$iconeEau?> <?=$quantite($ligne['ecartEau'])?> m³<br><?=$iconeElec?> <?=$quantite($ligne['ecartElectricite'])?> kWh<?php else: ?>—<?php endif; ?></td>
                        <td class="fin nombre fort"><?= $ligne['idRegularisation'] ? texteBilan(number_format((float)$ligne['montantEstime'],2,',',' ')).' DH' : '<span class="note">Aucun reste</span>' ?></td>
                        <td>
                            <?php if ($ligne['dateReglement']): ?>
                                <span class="etat ok">Payé le <?=$jourBilan($ligne['dateReglement'])?></span>
                            <?php elseif ($ligne['idRegularisation']): ?>
                                <small>Échéance : <?=$jourBilan($ligne['dateLimite'])?> · <?=texteBilan($ligne['nombreEnvois'])?> rappel(s)</small>
                                <form method="post" action="<?=link?>annuelle/regler/<?=texteBilan($ligne['idRegularisation'])?>" style="margin-top: 0.375rem">
                                    <input type="hidden" name="csrf" value="<?=texteBilan($_SESSION['csrf_bilan_annuel'])?>">
                                    <input type="hidden" name="annee" value="<?=$annee?>">
                                    <button class="bouton plein petit" type="submit">Marquer payé</button>
                                </form>
                            <?php else: ?>
                                <span class="note"><?= $ligne['idAnnuel'] ? 'Aucun rappel nécessaire' : 'En attente du fichier agent' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="vide">Aucun client enregistré.</p>
        <?php endif; ?>
    </section>

</main>
</body>
</html>
