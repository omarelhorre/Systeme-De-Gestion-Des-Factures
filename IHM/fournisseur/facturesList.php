<?php if (!isset($data, $action, $message)) { http_response_code(404); exit; } ?>
<?php
// Factures du fournisseur : liste filtrable, ou détail d'une facture ($action === 'detail').
$coqueTitre = 'Factures';
$coqueActif = 'factures';
$jourFacture = function ($date) { return $date ? date('d/m/Y', strtotime($date)) : '—'; };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
<?php if ($message !== '') { ?>
    <p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?= texteFacture($message) ?></span></p>
<?php } ?>

<?php if ($action === 'detail') { include __DIR__.'/factureDetail.php'; } else { ?>
    <header class="entete ligne">
        <div>
            <h1 class="titre">Factures</h1>
            <p class="sous-titre">Consultez les factures et enregistrez les paiements reçus en espèces.</p>
        </div>
        <nav class="filtres" aria-label="Filtrer les factures">
            <?php foreach (['' => 'Toutes', 'Payée' => 'Payées', 'Impayée' => 'Impayées'] as $valeur => $libelle) { ?>
                <a class="<?= $etat === $valeur ? 'actif' : '' ?>" href="<?= link ?>facture/all?etat=<?= rawurlencode($valeur) ?>" <?= $etat === $valeur ? 'aria-current="page"' : '' ?>><?= $libelle ?></a>
            <?php } ?>
        </nav>
    </header>

    <section class="carte">
        <?php if ($data) { ?>
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Facture</th><th>Client</th><th class="secondaire">Adresse</th><th class="secondaire">Période</th>
                        <th class="fin">Montant TTC</th><th>État</th><th></th>
                    </tr>
                </thead>
                <tbody data-pagine="8">
                    <?php foreach ($data as $ligne) { ?>
                        <tr>
                            <td class="nombre">N° <?= (int)$ligne['id'] ?><small><?= $jourFacture($ligne['dateGeneration']) ?></small></td>
                            <td><div class="tronque fort"><?= texteFacture($ligne['prenom'].' '.$ligne['nom']) ?></div></td>
                            <td class="secondaire"><div class="tronque" title="<?= texteFacture($ligne['adresse']) ?>"><?= texteFacture($ligne['adresse']) ?></div></td>
                            <td class="secondaire nombre"><?= $jourFacture($ligne['dateDebut']) ?> — <?= $ligne['dateFin'] ? $jourFacture($ligne['dateFin']) : 'Non renseignée' ?></td>
                            <td class="fin nombre fort"><?= $ligne['prixTTC'] === null ? 'Non renseigné' : texteFacture(number_format((float)$ligne['prixTTC'], 2, ',', ' ')).' DH' ?></td>
                            <td><span class="pill <?= $ligne['etat'] === 'Payée' ? 'ok' : '' ?>"><?= texteFacture($ligne['etat']) ?></span></td>
                            <td class="fin"><a class="bouton-rond" href="<?= link ?>facture/detail/<?= (int)$ligne['id'] ?>" aria-label="Consulter la facture <?= (int)$ligne['id'] ?>"><i class="fa-solid fa-arrow-right"></i></a></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="vide">Aucune facture pour ce filtre.</p>
        <?php } ?>
    </section>
<?php } ?>
</main>
</body>
</html>
