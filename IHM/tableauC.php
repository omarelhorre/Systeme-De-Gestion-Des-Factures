<?php
require_once ROOT.'IHM/commun/outils.php';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
$impayes = $tableau['impayes'];
$coqueTitre = 'Tableau de bord';
$coqueActif = 'tableau';
$moisLongs = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
$declarationOuverteAujourdhui = (int)date('j') >= 18;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/consommateur/menu.php'; ?>

<main class="contenu entree">
    <header class="entete">
        <h1 class="titre">Bonjour, <?= htmlspecialchars($_SESSION['user']['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="sous-titre"><?= horloge() ?></p>
    </header>

    <div class="grille">
        <section class="carte large">
            <h2 class="titre-carte">Déclaration de <?= $moisLongs[(int)date('n')] ?></h2>
            <p class="chiffre geant" style="margin-top: 0.5rem"><?= $declarationOuverteAujourdhui ? 'Ouverte' : 'Le 18' ?></p>
            <p class="legende">
                <?php if ($declarationOuverteAujourdhui) { ?>
                    Vous pouvez envoyer vos index jusqu'au <strong><?= date('t').' '.$moisLongs[(int)date('n')] ?></strong>.
                <?php } else { ?>
                    La déclaration ouvre du <strong>18 au <?= date('t').' '.$moisLongs[(int)date('n')] ?></strong>.
                <?php } ?>
            </p>

            <div class="carte-pied">
                <span></span>
                <a class="bouton accent" href="<?= link ?>declaration/ajouter">Déclarer ma consommation <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </section>

        <section class="carte large">
            <h2 class="titre-carte">Consommation</h2>
            <?php $mois = $tableau['consommation']; if ($mois) {
                $dernier = end($mois);
                $elec = array_column($mois, 'electricite');
                $eau = array_column($mois, 'eau'); ?>
                <p class="legende">Dernier relevé du <?= $e(date('d/m/Y', strtotime($dernier['date']))) ?> : <strong><?= nombreFr($dernier['electricite']) ?> kWh</strong> <?= variationConsommation($elec) ?> · <strong><?= nombreFr($dernier['eau'], 1) ?> m³</strong> <?= variationConsommation($eau) ?></p>
                <div class="graphe-remplir" style="margin-top: 0.75rem; flex: 1; display: flex; flex-direction: column">
                    <?php $points = array(); foreach ($mois as $cle => $valeurs) $points[] = array('mois' => $cle, 'electricite' => $valeurs['electricite'], 'eau' => $valeurs['eau']); ?>
                    <?= graphiqueConsommation($points, '7rem') ?>
                </div>
            <?php } else { ?>
                <p class="vide">Pas encore assez de relevés.</p>
            <?php } ?>
        </section>

        <section class="carte large">
            <div class="carte-entete">
                <div>
                    <h2 class="titre-carte">Dernières factures</h2>
                    <?php if ($impayes['nombre'] > 0) { ?>
                        <p class="legende" style="margin-top: 0.125rem"><span class="etat alerte"><?= (int)$impayes['nombre'] ?> impayée<?= $impayes['nombre'] > 1 ? 's' : '' ?> · <?= nombreFr($impayes['montant'], 2) ?> DH</span></p>
                    <?php } ?>
                </div>
                <a class="bouton contour petit" href="<?= link ?>declaration/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if ($tableau['factures']) { ?>
                <ul class="lignes">
                    <?php foreach ($tableau['factures'] as $facture) { ?>
                        <li class="ligne">
                            <div class="ligne-texte">
                                <p class="ligne-nom">Facture N° <?= (int)$facture['id'] ?></p>
                                <?php list($libelle, $classe) = etatFacture($facture['etat'], $facture['dateGeneration'], $facture['modePaiement']); ?>
                                <p class="ligne-detail"><?= $e(date('d/m/Y', strtotime($facture['dateGeneration']))) ?> · <span class="etat <?= $classe ?>"><?= $libelle ?></span></p>
                            </div>
                            <span class="ligne-montant"><?= nombreFr($facture['prixTTC'], 2) ?> DH</span>
                            <?php if ($facture['etat'] === 'Impayée') echo boutonPayerEnLigne($facture['id'], $facture['prixTTC']); ?>
                            <a class="bouton-rond" href="<?= link ?>declaration/facture/<?= (int)$facture['idDeclaration'] ?>" title="Télécharger le PDF" aria-label="Télécharger la facture <?= (int)$facture['id'] ?>"><i class="fa-solid fa-download"></i></a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p class="vide">Aucune facture pour le moment.</p>
            <?php } ?>
        </section>

        <section class="carte large">
            <div class="carte-entete">
                <h2 class="titre-carte">Mes réclamations</h2>
                <a class="bouton contour petit" href="<?= link ?>reclamation/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <?php if ($tableau['reclamations']) { ?>
                <ul class="lignes">
                    <?php foreach ($tableau['reclamations'] as $reclamation) { ?>
                        <li class="ligne">
                            <div class="ligne-texte">
                                <p class="ligne-nom"><?= $e($reclamation['description']) ?></p>
                                <p class="ligne-detail">N° <?= (int)$reclamation['idReclamation'] ?> · <?= $e(date('d/m/Y', strtotime($reclamation['dateCreation']))) ?><?= trim((string)$reclamation['reponse']) !== '' ? ' · Réponse : '.$e($reclamation['reponse']) : '' ?></p>
                            </div>
                            <?php list($libelle, $classe) = statutReclamation($reclamation['statut']); ?><span class="etat <?= $classe ?>"><?= $libelle ?></span>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p class="vide">Aucune réclamation.</p>
            <?php } ?>
        </section>
    </div>
</main>
</body>
</html>
