<?php
// Mes relevés : l'historique des relevés et des factures du client.
// Le formulaire est sur sa propre page (declaration/ajouter), comme "Ajouter un client".
// Variables fournies par traiterConsommation() : $client, $adresses, $declarations, $statutsAdresses, $date, $message, $erreur.
require_once ROOT.'IHM/commun/outils.php';
function h($valeur) { return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8'); }
$url = link . 'declaration/';
$coqueTitre = 'Mes relevés';
$coqueActif = 'releves';
$moisLongs = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
$jourReleve = function ($jour) { return $jour ? date('d/m/Y', strtotime($jour)) : '—'; };
// Couleur du point selon le statut : validée = vert, anomalie = rouge, le reste = gris
$etatReleve = function ($statut) {
    if ($statut === 'Validée') return 'ok';
    return strpos($statut, 'Anomalie') === 0 ? 'alerte' : '';
};
// Compteurs dont le relevé final est attendu (contrat en résiliation)
$relevesFinaux = array_values(array_filter($adresses, function ($a) use ($statutsAdresses) { return ($statutsAdresses[(int)$a['id']] ?? null) === CONTRAT_EN_RESILIATION; }));
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
            <p class="sous-titre">
                <?php if (declarationOuverte($date)) { ?>
                    Déclaration ouverte jusqu'au <?= $date->format('t').' '.$moisLongs[(int)$date->format('n')] ?>
                <?php } else { ?>
                    Prochaine déclaration du 18 au <?= $date->format('t').' '.$moisLongs[(int)$date->format('n')] ?>
                <?php } ?>
            </p>
        </div>
        <a class="bouton accent" href="<?= $url ?>ajouter<?= $relevesFinaux ? '/'.(int)$relevesFinaux[0]['id'] : '' ?>"><i class="fa-solid fa-plus"></i> Déclarer ma consommation</a>
    </header>

    <?php if ($message): ?><p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?=h($message)?></span></p><?php endif; ?>
    <?php if ($erreur): ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?=h($erreur)?></span></p><?php endif; ?>

    <?php if ($relevesFinaux): ?>
        <!-- Contrat en résiliation : le relevé final peut être déclaré tout de suite -->
        <p class="message" role="status"><i class="fa-solid fa-file-signature" style="color: var(--attente)"></i>
            <span>Relevé final attendu pour
                <?php foreach ($relevesFinaux as $i => $adresse): ?><?= $i > 0 ? ', ' : '' ?><a class="fort" style="text-decoration: underline" href="<?= $url ?>ajouter/<?= (int)$adresse['id'] ?>"><?= h($adresse['adresse']) ?></a><?php endforeach; ?>
                : il clôt le contrat et crée la facture finale.</span></p>
    <?php endif; ?>

    <section class="liste">
        <?php if (!$declarations): ?>
            <p class="vide">Aucun relevé pour le moment.</p>
        <?php else: ?>
        <table class="tableau">
            <thead>
                <tr>
                    <th>Date</th><th class="secondaire">Compteur</th><th class="fin cache-mobile">Eau (m³)</th><th class="fin cache-mobile">Électr. (kWh)</th>
                    <th>Statut</th><th class="secondaire">Photos</th><th>Facture</th><th>Paiement</th>
                </tr>
            </thead>
            <tbody data-pagine="8"><?php foreach ($declarations as $ligne): ?>
                <tr>
                    <td class="nombre"><?=$jourReleve($ligne['dateSaisie'])?></td>
                    <td class="secondaire"><div class="tronque" style="max-width: 10rem" title="<?=h($ligne['adresse'])?>"><?=h($ligne['adresse'])?></div></td>
                    <td class="fin nombre cache-mobile"><?=indexFr($ligne['indexEau'])?></td>
                    <td class="fin nombre cache-mobile"><?=indexFr($ligne['indexElectricite'])?></td>
                    <td><span class="etat <?=$etatReleve($ligne['statut'])?>" title="<?=h($ligne['statut'])?>"><span class="tronque" style="max-width: 8.5rem"><?=h($ligne['statut'] === 'Référence initiale' ? 'Relevé de départ' : $ligne['statut'])?></span></span></td>
                    <td class="secondaire">
                        <?php if ($ligne['cheminCompteurEauImg'] || $ligne['cheminCompteurElectriciteImg']): ?>
                        <div class="actions-ligne">
                            <?php if ($ligne['cheminCompteurEauImg']): ?><a class="bouton-rond" href="<?=$url?>eau/<?=h($ligne['id'])?>" title="Photo du compteur d'eau" aria-label="Photo du compteur d'eau"><i class="fa-solid fa-droplet" style="color: var(--eau)"></i></a><?php endif; ?>
                            <?php if ($ligne['cheminCompteurElectriciteImg']): ?><a class="bouton-rond" href="<?=$url?>electricite/<?=h($ligne['id'])?>" title="Photo du compteur d'électricité" aria-label="Photo du compteur d'électricité"><i class="fa-solid fa-bolt" style="color: var(--elec)"></i></a><?php endif; ?>
                        </div>
                        <?php else: ?><span class="note">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($ligne['idFacture']): ?>
                            <a class="fort" href="<?=$url?>facture/<?=h($ligne['id'])?>" title="Télécharger le PDF"><i class="fa-solid fa-download"></i> N° <?=h($ligne['idFacture'])?></a>
                            <?php list($libelle, $classe) = etatFacture($ligne['etat'], $ligne['dateSaisie']); ?><small><span class="etat <?= $classe ?>"><?= $libelle ?></span></small>
                        <?php else: ?><span class="note">—</span><?php endif; ?>
                    </td>
                    <!-- Paiement en ligne : seulement pour une facture impayée -->
                    <td class="nombre">
                        <?php if ($ligne['idFacture'] && $ligne['etat'] === 'Impayée'): ?>
                            <?= boutonPayerEnLigne($ligne['idFacture'], $ligne['prixTTC']) ?>
                        <?php elseif ($ligne['datePaiement']): ?>
                            <?= $ligne['modePaiement'] === 'en ligne' ? 'En ligne' : 'Espèces' ?><small>le <?= date('d/m/Y', strtotime($ligne['datePaiement'])) ?></small>
                        <?php else: ?><span class="note">—</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?></tbody>
        </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
