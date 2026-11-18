<?php if (!isset($data, $action, $message, $erreur)) { http_response_code(404); exit; } ?>
<?php
// Déclarations anormales : liste, ou correction d'une déclaration ($action === 'corriger').
$coqueTitre = 'Anomalies';
$coqueActif = 'anomalies';
$jourAnomalie = function ($date) { return $date ? date('d/m/Y', strtotime($date)) : '—'; };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <?php if ($message !== ''): ?>
        <p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?= texteAnomalie($message) ?></span></p>
    <?php endif; ?>

    <?php if ($action === 'corriger'): ?>
        <?php require ROOT . 'IHM/fournisseur/anomalieDetail.php'; ?>
    <?php else: ?>
        <header class="entete">
            <h1 class="titre">Anomalies</h1>
        </header>

        <section class="liste">
            <?php if ($data): ?>
                <table class="tableau">
                    <thead>
                        <tr>
                            <th>Déclaration</th><th>Client</th><th class="secondaire">Adresse</th>
                            <th class="fin secondaire">Index eau</th><th class="fin secondaire">Index électricité</th><th>Anomalie</th><th></th>
                        </tr>
                    </thead>
                    <tbody data-pagine="8">
                    <?php foreach ($data as $ligne): ?>
                        <tr>
                            <td class="nombre">N° <?= (int)$ligne['id'] ?><small><?= $jourAnomalie($ligne['dateSaisie']) ?></small></td>
                            <td><div class="tronque fort"><?= texteAnomalie($ligne['prenom'] . ' ' . $ligne['nom']) ?></div></td>
                            <td class="secondaire"><div class="tronque" title="<?= texteAnomalie($ligne['adresse']) ?>"><?= texteAnomalie($ligne['adresse']) ?></div></td>
                            <td class="fin nombre secondaire"><?= texteAnomalie($ligne['indexEau']) ?></td>
                            <td class="fin nombre secondaire"><?= texteAnomalie($ligne['indexElectricite']) ?></td>
                            <td><span class="etat alerte" title="<?= texteAnomalie($ligne['statut']) ?>"><span class="tronque" style="max-width: 12rem"><?= texteAnomalie($ligne['statut']) ?></span></span></td>
                            <td class="fin"><a class="bouton contour petit" href="<?= link ?>declaration/corriger/<?= (int)$ligne['id'] ?>">Examiner</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="vide">Aucune anomalie en attente.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
