<?php
function h($valeur) { return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8'); }
$url = link . 'declaration/';
$ouvert = declarationOuverte($date);
$dejaFait = false;
foreach ($declarations as $ligne) if ((int)$ligne['idAdresseCompteur'] === $idAdresseCompteur && substr($ligne['dateSaisie'], 0, 7) === $date->format('Y-m')) $dejaFait = true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes déclarations - Fatourti</title>
    <link rel="stylesheet" href="<?=dir?>/public/style/dashboardF.css">
    <link rel="stylesheet" href="<?=dir?>/public/style/consommation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body class="consumer-page declaration-page">
<nav class="menu" aria-label="Navigation consommateur">
    <ul class="menu-list">
        <li><span class="title">Fatourti</span></li>
        <li>
            <div class="grid">
                <div class="border">
                    <a class="section-txt" href="<?= link ?>consommateur/tableauDeBord"><i class="fa-solid fa-house"></i> Consulter le tableau de bord</a>
                </div>
                <div class="border selected">
                    <a class="section-txt" href="<?= link ?>declaration/all" aria-current="page"><i class="fa-solid fa-pen"></i> Déclarer consommation</a>
                </div>
                <div class="border">
                    <a class="section-txt" href="<?= link ?>reclamations.php"><i class="fa-solid fa-circle-exclamation"></i> Mes réclamations</a>
                </div>
            </div>
        </li>
        <li>
            <div class="connexion-section">
                <div class="login-creds">
                    <p class="identity"><?= htmlspecialchars(($_SESSION['user']['nom'] ?? '') . ' ' . ($_SESSION['user']['prenom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="role-style">Consommateur</p>
                </div>
                <a class="element" href="<?= link ?>utilisateur/deconnexion"><button class="connexion-btn" type="button"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</button></a>
            </div>
        </li>
    </ul>
</nav>
<main class="main">
<div class="container">
    <h1>Mes relevés de consommation</h1>
    <p>Bienvenue, <?=h($client['prenom'].' '.$client['nom'])?>.</p>
    <div>
        <label>Adresse du compteur
            <select onchange="window.location.href='<?=$url?>all/' + this.value">
                <?php foreach ($adresses as $adresse): ?>
                <option value="<?=h($adresse['id'])?>" <?=((int)$adresse['id'] === $idAdresseCompteur ? 'selected' : '')?>><?=h($adresse['adresse'].' — '.$adresse['categorie'])?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
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
        <form method="post" action="<?=$url?>ajouter" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_consommation'])?>">
            <input type="hidden" name="idAdresseCompteur" value="<?=h($idAdresseCompteur)?>">
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
            <thead><tr><th>Date</th><th>Adresse</th><th>Index eau</th><th>Index électricité</th><th>Statut</th><th>Photos</th><th>Facture</th></tr></thead>
            <tbody><?php foreach ($declarations as $ligne): ?>
                <tr>
                    <td><?=h($ligne['dateSaisie'])?></td>
                    <td><?=h($ligne['adresse'])?></td>
                    <td><?=h($ligne['indexEau'])?></td>
                    <td><?=h($ligne['indexElectricite'])?></td>
                    <td><?=h($ligne['statut'])?></td>
                    <td><a href="<?=$url?>eau/<?=h($ligne['id'])?>">Eau</a> · <a href="<?=$url?>electricite/<?=h($ligne['id'])?>">Électricité</a></td>
                    <td><?php if ($ligne['idFacture']): ?><a href="<?=$url?>facture/<?=h($ligne['id'])?>">Télécharger n° <?=h($ligne['idFacture'])?></a><br><?=h($ligne['etat'])?><?php if ($ligne['datePaiement']): ?> (<?=h($ligne['datePaiement'])?>)<?php endif; ?><?php else: ?>Aucune<?php endif; ?></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div><?php endif; ?>
    </section>
    <p class="note">Tarifs et seuils affichés à titre de démonstration. TVA : 18 %.</p>
</div>
</main>
</body>
</html>
