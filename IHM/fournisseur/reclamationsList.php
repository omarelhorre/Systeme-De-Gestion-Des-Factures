<?php if (!isset($reclamations)) { http_response_code(404); exit; } ?>
<?php
// Liste simple des réclamations (lecture seule).
$coqueTitre = 'Réclamations';
$coqueActif = 'reclamations';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <header class="entete">
        <h1 class="titre">Réclamations</h1>
        <p class="sous-titre">Consultez les réclamations de vos clients.</p>
    </header>

    <section class="carte">
        <?php if (!empty($reclamations)) { ?>
            <ul class="lignes" data-pagine="5">
                <?php foreach ($reclamations as $reclamation) { ?>
                    <li class="bloc">
                        <div class="entete ligne" style="margin-bottom: 0.5rem; align-items: center">
                            <div>
                                <p class="ligne-nom"><?= texteReclamation($reclamation['prenom'].' '.$reclamation['nom']) ?></p>
                                <p class="ligne-detail">N° <?= (int)$reclamation['idReclamation'] ?> · <?= texteReclamation(date('d/m/Y', strtotime($reclamation['dateCreation']))) ?></p>
                            </div>
                            <span class="pill <?= $reclamation['statut'] === 'Traitée' ? 'ok' : '' ?>"><?= texteReclamation($reclamation['statut']) ?></span>
                        </div>
                        <p style="line-height: 1.5; overflow-wrap: anywhere"><?= nl2br(texteReclamation($reclamation['description'])) ?></p>
                        <p class="note" style="margin-top: 0.5rem; overflow-wrap: anywhere"><?= trim($reclamation['reponse'] ?? '') === '' ? 'Aucune réponse' : 'Réponse : '.nl2br(texteReclamation($reclamation['reponse'])) ?></p>
                    </li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <p class="vide">Aucune réclamation pour le moment.</p>
        <?php } ?>
    </section>
</main>
</body>
</html>
