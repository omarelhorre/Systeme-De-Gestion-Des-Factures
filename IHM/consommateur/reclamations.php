<?php
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

// Affichage
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
    <header class="entete">
        <h1 class="titre">Mes réclamations</h1>
        <p class="sous-titre">Déclarez un problème et suivez son traitement.</p>
    </header>

    <?php if ($message): ?><p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?= texteReclamation($message) ?></span></p><?php endif; ?>
    <?php if ($erreur): ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= texteReclamation($erreur) ?></span></p><?php endif; ?>

    <div class="grille">
        <section class="carte large">
            <h2 class="titre-carte">Nouvelle réclamation</h2>
            <form class="formulaire" style="margin-top: 1rem" method="post" action="<?= link ?>reclamation/all">
                <input type="hidden" name="csrf" value="<?= texteReclamation($_SESSION['csrf_reclamations']) ?>">
                <label class="champ"><span class="libelle">Type de réclamation</span>
                    <select name="type" required>
                        <option value="">Choisir un type</option>
                        <?php foreach ($libellesTypes as $code => $libelle): ?>
                            <option value="<?= texteReclamation($code) ?>" <?= $valeurs['type'] === $code ? 'selected' : '' ?>><?= texteReclamation($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="champ"><span class="libelle">Facture concernée (type « Facture »)</span>
                    <select name="idFacture">
                        <option value="">Choisir une facture</option>
                        <?php foreach ($factures as $facture): ?>
                            <option value="<?= (int)$facture['id'] ?>" <?= $valeurs['idFacture'] === (string)$facture['id'] ? 'selected' : '' ?>>N° <?= (int)$facture['id'] ?> — <?= texteReclamation($facture['dateGeneration']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="champ entier"><span class="libelle">Description</span>
                    <textarea name="description" rows="5" maxlength="1000" required><?= texteReclamation($valeurs['description']) ?></textarea>
                </label>
                <div class="entier actions fin">
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-paper-plane"></i> Envoyer la réclamation</button>
                </div>
            </form>
        </section>

        <section class="carte large">
            <h2 class="titre-carte" style="margin-bottom: 1rem">Historique et statut</h2>
            <?php if (!$reclamations): ?>
                <p class="vide">Aucune réclamation enregistrée.</p>
            <?php else: ?>
                <ul class="lignes" data-pagine="3">
                    <?php foreach ($reclamations as $reclamation): ?>
                        <li class="bloc">
                            <div class="entete ligne" style="margin-bottom: 0.5rem; align-items: center">
                                <div>
                                    <p class="ligne-nom"><?= texteReclamation($libellesTypes[$reclamation['typeReclamation']] ?? 'Autre') ?><?php if ($reclamation['idFacture']): ?> — facture n° <?= (int)$reclamation['idFacture'] ?><?php endif; ?></p>
                                    <p class="ligne-detail"><?= texteReclamation(date('d/m/Y', strtotime($reclamation['dateCreation']))) ?></p>
                                </div>
                                <?php list($libelle, $classe) = statutReclamation($reclamation['statut']); ?><span class="etat <?= $classe ?>"><?= $libelle ?></span>
                            </div>
                            <p style="line-height: 1.5; overflow-wrap: anywhere"><?= nl2br(texteReclamation($reclamation['description'])) ?></p>
                            <p class="note" style="margin-top: 0.5rem; overflow-wrap: anywhere"><?= $reclamation['reponse'] !== null && $reclamation['reponse'] !== '' ? 'Réponse : '.nl2br(texteReclamation($reclamation['reponse'])) : 'Aucune réponse' ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
