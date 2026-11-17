<?php
require_once ROOT.'IHM/commun/outils.php';
function h($valeur) { return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8'); }
$url = link . 'declaration/';
$ouvert = declarationOuverte($date);
$dejaFait = false;
foreach ($declarations as $ligne) if ((int)$ligne['idAdresseCompteur'] === $idAdresseCompteur && substr($ligne['dateSaisie'], 0, 7) === $date->format('Y-m')) $dejaFait = true;

// Affichage
$coqueTitre = 'Mes relevés';
$coqueActif = 'releves';
$jourReleve = function ($jour) { return $jour ? date('d/m/Y', strtotime($jour)) : '—'; };
// Couleur de la pill selon le statut : validée = vert, anomalie = rouge, le reste = neutre
$pillReleve = function ($statut) {
    if ($statut === 'Validée') return 'ok';
    return strpos($statut, 'Anomalie') === 0 ? 'alerte' : '';
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/consommateur/menu.php'; ?>

<main class="contenu entree">
    <header class="entete ligne">
        <div>
            <h1 class="titre">Mes relevés</h1>
            <p class="sous-titre">Bienvenue, <?=h($client['prenom'].' '.$client['nom'])?>.</p>
        </div>
        <label class="champ" style="width: 22rem; max-width: 100%"><span class="libelle">Adresse du compteur</span>
            <select onchange="window.location.href='<?=$url?>all/' + this.value">
                <?php foreach ($adresses as $adresse): ?>
                <option value="<?=h($adresse['id'])?>" <?=((int)$adresse['id'] === $idAdresseCompteur ? 'selected' : '')?>><?=h($adresse['adresse'].' — '.$adresse['categorie'])?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </header>

    <?php if ($message): ?><p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?=h($message)?></span></p><?php endif; ?>
    <?php if ($erreur): ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?=h($erreur)?></span></p><?php endif; ?>

    <div class="pile">
        <section class="carte">
            <h2 class="titre-carte">Nouvelle déclaration</h2>
            <?php if ($precedente): ?>
                <!-- Le relevé du mois dernier : la valeur et la photo de chaque compteur, pour comparer avant de saisir -->
                <p class="legende">Dernier relevé valide, du <?=$jourReleve($precedente['dateSaisie'])?> :</p>
                <div class="photos photos-releve" style="margin-top: 0.75rem">
                    <figure>
                        <?php if ($precedente['cheminCompteurEauImg']): ?>
                            <img src="<?=$url?>eau/<?=h($precedente['id'])?>" alt="Photo du compteur d'eau au dernier relevé">
                        <?php endif; ?>
                        <figcaption><i class="fa-solid fa-droplet" style="color: var(--eau)"></i> Eau · <?=h(nombreFr($precedente['indexEau'], 2))?> m³<?= $precedente['cheminCompteurEauImg'] ? '' : ' <span class="note">(relevé de référence, sans photo)</span>' ?></figcaption>
                    </figure>
                    <figure>
                        <?php if ($precedente['cheminCompteurElectriciteImg']): ?>
                            <img src="<?=$url?>electricite/<?=h($precedente['id'])?>" alt="Photo du compteur d'électricité au dernier relevé">
                        <?php endif; ?>
                        <figcaption><i class="fa-solid fa-bolt" style="color: var(--elec)"></i> Électricité · <?=h(nombreFr($precedente['indexElectricite'], 2))?> kWh<?= $precedente['cheminCompteurElectriciteImg'] ? '' : ' <span class="note">(relevé de référence, sans photo)</span>' ?></figcaption>
                    </figure>
                </div>
            <?php else: ?>
                <p class="legende">Votre premier relevé servira de référence. Il ne donnera pas lieu à une facture.</p>
            <?php endif; ?>

            <?php if (!$ouvert): ?>
                <p class="bloc" style="margin-top: 1rem">La déclaration est ouverte du 18 au dernier jour de chaque mois. Vous pouvez consulter vos anciens relevés et factures ci-dessous.</p>
            <?php elseif ($dejaFait): ?>
                <p class="bloc" style="margin-top: 1rem">Vous avez déjà envoyé votre déclaration ce mois-ci.</p>
            <?php else: ?>
            <form class="formulaire" style="margin-top: 1rem" method="post" action="<?=$url?>ajouter" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?=h($_SESSION['csrf_consommation'])?>">
                <input type="hidden" name="idAdresseCompteur" value="<?=h($idAdresseCompteur)?>">

                <!-- Eau -->
                <div class="bloc pile" style="gap: 0.875rem">
                    <p class="bloc-titre" style="margin: 0; color: var(--eau)"><i class="fa-solid fa-droplet"></i> Eau</p>
                    <label class="champ"><span class="libelle">Index du compteur (m³)</span>
                        <input type="text" inputmode="decimal" name="indexEau" required pattern="[0-9]+([.,][0-9]{1,4})?">
                    </label>
                    <label class="champ"><span class="libelle">Photo du compteur (JPEG, 3 Mo max)</span>
                        <input type="file" name="photoEau" accept="image/jpeg" required>
                    </label>
                </div>

                <!-- Électricité -->
                <div class="bloc pile" style="gap: 0.875rem">
                    <p class="bloc-titre" style="margin: 0; color: var(--elec)"><i class="fa-solid fa-bolt"></i> Électricité</p>
                    <label class="champ"><span class="libelle">Index du compteur (kWh)</span>
                        <input type="text" inputmode="decimal" name="indexElectricite" required pattern="[0-9]+([.,][0-9]{1,4})?">
                    </label>
                    <label class="champ"><span class="libelle">Photo du compteur (JPEG, 3 Mo max)</span>
                        <input type="file" name="photoElectricite" accept="image/jpeg" required>
                    </label>
                </div>

                <div class="entier actions fin">
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-paper-plane"></i> Enregistrer la déclaration</button>
                </div>
            </form>
            <?php endif; ?>
        </section>

        <section class="carte">
            <h2 class="titre-carte" style="margin-bottom: 1rem">Historique et factures</h2>
            <?php if (!$declarations): ?>
                <p class="vide">Aucune déclaration enregistrée.</p>
            <?php else: ?>
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Date</th><th class="secondaire">Adresse</th><th class="fin cache-mobile">Eau (m³)</th><th class="fin cache-mobile">Électricité (kWh)</th>
                        <th>Statut</th><th class="secondaire">Photos</th><th>Facture</th>
                    </tr>
                </thead>
                <tbody data-pagine="6"><?php foreach ($declarations as $ligne): ?>
                    <tr>
                        <td class="nombre"><?=$jourReleve($ligne['dateSaisie'])?></td>
                        <td class="secondaire"><div class="tronque" title="<?=h($ligne['adresse'])?>"><?=h($ligne['adresse'])?></div></td>
                        <td class="fin nombre cache-mobile"><?=h($ligne['indexEau'])?></td>
                        <td class="fin nombre cache-mobile"><?=h($ligne['indexElectricite'])?></td>
                        <td><span class="etat <?=$pillReleve($ligne['statut'])?>" title="<?=h($ligne['statut'])?>"><span class="tronque" style="max-width: 10rem"><?=h($ligne['statut'])?></span></span></td>
                        <td class="secondaire">
                            <?php if ($ligne['cheminCompteurEauImg'] || $ligne['cheminCompteurElectriciteImg']): ?>
                            <div class="actions">
                                <?php if ($ligne['cheminCompteurEauImg']): ?><a class="bouton-rond" href="<?=$url?>eau/<?=h($ligne['id'])?>" title="Photo du compteur d'eau" aria-label="Photo du compteur d'eau"><i class="fa-solid fa-droplet" style="color: var(--eau)"></i></a><?php endif; ?>
                                <?php if ($ligne['cheminCompteurElectriciteImg']): ?><a class="bouton-rond" href="<?=$url?>electricite/<?=h($ligne['id'])?>" title="Photo du compteur d'électricité" aria-label="Photo du compteur d'électricité"><i class="fa-solid fa-bolt" style="color: var(--elec)"></i></a><?php endif; ?>
                            </div>
                            <?php else: ?><span class="note">Sans photo</span><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ligne['idFacture']): ?>
                                <a class="fort" href="<?=$url?>facture/<?=h($ligne['id'])?>"><i class="fa-solid fa-download"></i> N° <?=h($ligne['idFacture'])?></a>
                                <?php list($libelle, $classe) = etatFacture($ligne['etat'], $ligne['dateSaisie']); ?><small><span class="etat <?= $classe ?>"><?= $libelle ?><?php if ($ligne['datePaiement']): ?> le <?=$jourReleve($ligne['datePaiement'])?><?php endif; ?></span></small>
                            <?php else: ?><span class="note">Aucune</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table>
            <?php endif; ?>
        </section>
    </div>

    <p class="note" style="margin-top: 1rem">Tarifs et seuils affichés à titre de démonstration. TVA : 18 %.</p>
</main>
</body>
</html>
