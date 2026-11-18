<?php
// Liste des clients du fournisseur.
// Variables fournies par afficherClients() :
//   $data   : [id, nom, prénom, adresse]
//   $resume : par client, email, CIN, téléphone, compteurs, contrats actifs, impayé
//   $series : par client, consommation des 6 derniers mois avec relevé
require_once ROOT.'IHM/commun/outils.php';
$coqueTitre = 'Clients';
$coqueActif = 'clients';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
$totalImpaye = array_sum(array_map(function ($r) { return (float)$r['impaye']; }, $resume));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <header class="entete ligne">
        <div>
            <h1 class="titre">Clients</h1>
            <p class="sous-titre"><?= count($data) ?> client<?= count($data) > 1 ? 's' : '' ?> · <?= nombreFr($totalImpaye, 2) ?> DH impayés</p>
        </div>
        <a class="bouton accent" href="<?= link ?>consommateur/ajouter"><i class="fa-solid fa-plus"></i> Ajouter un client</a>
    </header>

    <section class="liste">
        <?php if ($data) { ?>
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th class="secondaire">Contact</th>
                        <th class="secondaire">Compteurs</th>
                        <th class="cache-mobile">Consommation <small style="display: inline">· 6 derniers mois, survol pour le détail</small></th>
                        <th class="fin">Impayé</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-pagine="8">
                    <?php foreach ($data as $V) {
                        $id = (int)$V[0];
                        $infos = $resume[$id] ?? array('email' => '', 'cin' => '', 'telephone' => '', 'compteurs' => 0, 'contratsActifs' => 0, 'impaye' => 0, 'facturesImpayees' => 0);
                        $mois = $series[$id] ?? array();
                        $elec = array_column($mois, 'electricite');
                        $eau = array_column($mois, 'eau');
                        $dernier = $mois ? end($mois) : null;
                    ?>
                        <tr>
                            <td>
                                <div class="cellule-nom">
                                    <i class="fa-solid fa-circle-user avatar-liste" aria-hidden="true"></i>
                                    <div class="tronque">
                                        <span class="fort"><?= $e($V[2].' '.$V[1]) ?></span>
                                        <small>N° <?= $id ?><?= trim((string)$infos['cin']) !== '' ? ' · '.$e($infos['cin']) : '' ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="secondaire">
                                <div class="tronque" style="max-width: 13rem"><?= $e($infos['email']) ?></div>
                                <small><?= trim((string)$infos['telephone']) !== '' ? $e($infos['telephone']) : 'Téléphone non renseigné' ?></small>
                            </td>
                            <td class="secondaire">
                                <span class="fort"><?= (int)$infos['compteurs'] ?></span> compteur<?= $infos['compteurs'] > 1 ? 's' : '' ?>
                                <small><span class="etat <?= $infos['contratsActifs'] > 0 ? 'ok' : '' ?>"><?= (int)$infos['contratsActifs'] ?> contrat<?= $infos['contratsActifs'] > 1 ? 's' : '' ?> actif<?= $infos['contratsActifs'] > 1 ? 's' : '' ?></span></small>
                            </td>
                            <td class="cache-mobile">
                                <?php if ($dernier) {
                                    $points = array();
                                    foreach ($mois as $cle => $valeurs) $points[] = array('mois' => $cle, 'electricite' => $valeurs['electricite'], 'eau' => $valeurs['eau']); ?>
                                    <div class="conso-ligne">
                                        <?= miniGraphiqueConsommation($points) ?>
                                        <div class="conso-valeurs">
                                            <span><span class="cle elec"></span><span class="fort"><?= nombreFr($dernier['electricite']) ?> kWh</span> <?= variationConsommation($elec) ?></span>
                                            <span><span class="cle eau"></span><span class="fort"><?= nombreFr($dernier['eau'], 1) ?> m³</span> <?= variationConsommation($eau) ?></span>
                                        </div>
                                        <small>relevé du <?= $e(date('d/m', strtotime($dernier['date']))) ?></small>
                                    </div>
                                <?php } else { ?>
                                    <span class="note">Aucun relevé facturé</span>
                                <?php } ?>
                            </td>
                            <td class="fin nombre">
                                <?php if ((float)$infos['impaye'] > 0) { ?>
                                    <span class="fort"><?= nombreFr($infos['impaye'], 2) ?> DH</span>
                                    <small><?= (int)$infos['facturesImpayees'] ?> facture<?= $infos['facturesImpayees'] > 1 ? 's' : '' ?></small>
                                <?php } else { ?>
                                    <span class="etat ok">À jour</span>
                                <?php } ?>
                            </td>
                            <td class="fin">
                                <div class="actions-ligne">
                                    <a class="bouton-rond" href="<?= link ?>consommateur/modifier/<?= $id ?>" title="Modifier" aria-label="Modifier <?= $e($V[2].' '.$V[1]) ?>"><i class="fa-solid fa-pen"></i></a>
                                    <a class="bouton-rond danger" href="<?= link ?>consommateur/supprimer/<?= $id ?>" title="Supprimer" aria-label="Supprimer <?= $e($V[2].' '.$V[1]) ?>"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="vide">Aucun client.</p>
        <?php } ?>
    </section>
</main>
</body>
</html>
