<?php
function h($valeur) { return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8'); }
$url = h($_SERVER['SCRIPT_NAME']);
$ouvert = declarationOuverte($date);
$dejaFait = false;
foreach ($declarations as $ligne) if (substr($ligne['dateSaisie'], 0, 7) === $date->format('Y-m')) $dejaFait = true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes déclarations - Fatourti</title>
    <link rel="stylesheet" href="<?=h(dirname($_SERVER['SCRIPT_NAME']).'/public/style/consommation.css')?>">
</head>
<body>
<header><strong>Fatourti</strong><nav><a href="<?=h(dirname($_SERVER['SCRIPT_NAME']).'/index.php?url=consommateur/tableauDeBord')?>">Tableau de bord</a></nav></header>
<main>
    <h1>Mes relevés de consommation</h1>
    <p>Bienvenue, <?=h($client['prenom'].' '.$client['nom'])?>.</p>
    <?php if ($message): ?><p class="message"><?=h($message)?></p><?php endif; ?>
    <?php if ($erreur): ?><p class="erreur"><?=h($erreur)?></p><?php endif; ?>
    <section class="carte">
        <h2>Nouvelle déclaration</h2>
        <?php if ($precedente): ?>
            <p>Dernier relevé valide du <?=h($precedente['dateSaisie'])?> : eau <strong><?=h($precedente['indexEau'])?> m³</strong>, électricité <strong><?=h($precedente['indexElectricite'])?> kWh</strong>.</p>
        <?php else: ?><p>Votre premier relevé servira de référence. Il ne donnera pas lieu à une facture.</p><?php endif; ?>
        <?php if (!$ouvert): ?><p class="note">La déclaration est ouverte du 18 au dernier jour de chaque mois. Vous pouvez consulter vos anciens relevés et factures ci-dessous.</p>
        <?php elseif ($dejaFait): ?><p class="note">Vous avez déjà envoyé votre déclaration ce mois-ci.</p>
        <?php else: ?>
        <form method="post" action="<?=$url?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_consommation'])?>">
            <div class="grille">
                <label>Index du compteur d’eau (m³)<input type="text" inputmode="decimal" name="indexEau" required pattern="[0-9]+([.,][0-9]{1,4})?"></label>
                <label>Photo du compteur d’eau (JPEG, 3 Mo max)<input type="file" name="photoEau" accept="image/jpeg" required></label>
                <label>Index du compteur d’électricité (kWh)<input type="text" inputmode="decimal" name="indexElectricite" required pattern="[0-9]+([.,][0-9]{1,4})?"></label>
                <label>Photo du compteur d’électricité (JPEG, 3 Mo max)<input type="file" name="photoElectricite" accept="image/jpeg" required></label>
            </div>
            <button type="submit">Enregistrer la déclaration</button>
        </form>
        <?php endif; ?>
    </section>
    <section class="carte">
        <h2>Historique et factures</h2>
        <?php if (!$declarations): ?><p>Aucune déclaration enregistrée.</p><?php else: ?>
        <div class="defilement"><table>
            <thead><tr><th>Date</th><th>Index eau</th><th>Index électricité</th><th>Statut</th><th>Photos</th><th>Facture</th></tr></thead>
            <tbody><?php foreach ($declarations as $ligne): ?>
                <tr>
                    <td><?=h($ligne['dateSaisie'])?></td>
                    <td><?=h($ligne['indexEau'])?></td>
                    <td><?=h($ligne['indexElectricite'])?></td>
                    <td><?=h($ligne['statut'])?></td>
                    <td><a href="<?=$url?>?fichier=eau&amp;id=<?=h($ligne['id'])?>">Eau</a> · <a href="<?=$url?>?fichier=electricite&amp;id=<?=h($ligne['id'])?>">Électricité</a></td>
                    <td><?php if ($ligne['idFacture']): ?><a href="<?=$url?>?fichier=facture&amp;id=<?=h($ligne['id'])?>">Télécharger n° <?=h($ligne['idFacture'])?></a><br><?=h($ligne['etat'])?><?php if ($ligne['datePaiement']): ?> (<?=h($ligne['datePaiement'])?>)<?php endif; ?><?php else: ?>Aucune<?php endif; ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div><?php endif; ?>
    </section>
    <p class="note">Tarifs et seuils affichés à titre de démonstration. TVA : 18 %.</p>
</main>
</body>
</html>
