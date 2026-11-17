<?php
// Mes réclamations : la liste et leur suivi. Le formulaire est sur sa propre page (reclamation/ajouter).
// Variables fournies par traiterReclamationsConsommateur('all') : $reclamations, $message, $erreur.
require_once ROOT.'IHM/commun/outils.php';
function texteReclamation($valeur): string
{
    return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
}
$libellesTypes = [
    'fuite_interne' => 'Fuite interne',
    'fuite_externe' => 'Fuite externe',
    'facture' => 'Facture',
    'autre' => 'Autre',
];
$enCours = count(array_filter($reclamations, function ($r) { return statutReclamation($r['statut'])[1] !== 'ok'; }));

$coqueTitre = 'Mes réclamations';
$coqueActif = 'reclamations';
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
            <h1 class="titre">Mes réclamations</h1>
            <p class="sous-titre"><?= count($reclamations) ?> réclamation<?= count($reclamations) > 1 ? 's' : '' ?> · <?= $enCours ?> en cours de traitement</p>
        </div>
        <a class="bouton accent" href="<?= link ?>reclamation/ajouter"><i class="fa-solid fa-plus"></i> Nouvelle réclamation</a>
    </header>

    <?php if ($message): ?><p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?= texteReclamation($message) ?></span></p><?php endif; ?>

    <section class="liste">
        <?php if (!$reclamations): ?>
            <p class="vide">Aucune réclamation. Signalez une fuite ou un problème de facture avec le bouton ci-dessus.</p>
        <?php else: ?>
            <table class="tableau">
                <thead>
                    <tr><th>N°</th><th>Date</th><th class="secondaire">Type</th><th>Demande et réponse</th><th>Statut</th></tr>
                </thead>
                <tbody data-pagine="8">
                    <?php foreach ($reclamations as $reclamation):
                        $reponse = trim((string)$reclamation['reponse']);
                        list($libelle, $classe) = statutReclamation($reclamation['statut']); ?>
                        <tr>
                            <td class="nombre">N° <?= (int)$reclamation['idReclamation'] ?></td>
                            <td class="nombre"><?= texteReclamation(date('d/m/Y', strtotime($reclamation['dateCreation']))) ?><small><?= ilYa($reclamation['dateCreation']) ?></small></td>
                            <td class="secondaire">
                                <span class="tag"><?= texteReclamation($libellesTypes[$reclamation['typeReclamation']] ?? 'Autre') ?></span>
                                <?php if ($reclamation['idFacture']): ?><small>facture n° <?= (int)$reclamation['idFacture'] ?></small><?php endif; ?>
                            </td>
                            <td>
                                <div class="tronque" style="max-width: 26rem" title="<?= texteReclamation($reclamation['description']) ?>"><?= texteReclamation($reclamation['description']) ?></div>
                                <small class="tronque" style="max-width: 26rem" title="<?= texteReclamation($reponse) ?>"><?= $reponse !== '' ? 'Réponse : '.texteReclamation($reponse) : 'Pas encore de réponse' ?></small>
                            </td>
                            <td><span class="etat <?= $classe ?>"><?= $libelle ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
