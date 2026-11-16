<?php
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réclamations - Fatourti</title>
    <link rel="stylesheet" href="<?= dir ?>/public/style/consommation.css">
    <link rel="stylesheet" href="<?= dir ?>/public/style/reclamations.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body class="reclamations-page">
<header><strong>Fatourti</strong><nav>
    <a href="<?= link ?>consommateur/tableauDeBord">Tableau de bord</a> ·
    <a href="<?= link ?>declaration/all">Mes relevés</a>
</nav></header>
<main>
    <h1>Mes réclamations</h1>
    <p>Déclarez un problème et suivez son traitement.</p>
    <?php if ($message): ?><p class="message" role="status"><?= texteReclamation($message) ?></p><?php endif; ?>
    <?php if ($erreur): ?><p class="erreur" role="alert"><?= texteReclamation($erreur) ?></p><?php endif; ?>
    <section class="carte">
        <h2>Nouvelle réclamation</h2>
        <form method="post" action="<?= link ?>reclamations.php">
            <input type="hidden" name="csrf" value="<?= texteReclamation($_SESSION['csrf_reclamations']) ?>">
            <div class="grille">
                <label>Type de réclamation
                    <select name="type" required>
                        <option value="">Choisir un type</option>
                        <?php foreach ($libellesTypes as $code => $libelle): ?>
                            <option value="<?= texteReclamation($code) ?>" <?= $valeurs['type'] === $code ? 'selected' : '' ?>><?= texteReclamation($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Facture concernée (pour le type « Facture »)
                    <select name="idFacture">
                        <option value="">Choisir une facture</option>
                        <?php foreach ($factures as $facture): ?>
                            <option value="<?= (int)$facture['id'] ?>" <?= $valeurs['idFacture'] === (string)$facture['id'] ? 'selected' : '' ?>>N° <?= (int)$facture['id'] ?> — <?= texteReclamation($facture['dateGeneration']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Description
                <textarea name="description" rows="5" maxlength="1000" required><?= texteReclamation($valeurs['description']) ?></textarea>
            </label>
            <button type="submit">Envoyer la réclamation</button>
        </form>
    </section>
    <section class="carte">
        <h2>Historique et statut</h2>
        <?php if (!$reclamations): ?>
            <p>Aucune réclamation enregistrée.</p>
        <?php else: ?>
            <div class="defilement"><table>
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Statut</th><th>Réponse</th></tr></thead>
                <tbody><?php foreach ($reclamations as $reclamation): ?>
                    <tr>
                        <td><?= texteReclamation($reclamation['dateCreation']) ?></td>
                        <td><?= texteReclamation($libellesTypes[$reclamation['typeReclamation']] ?? 'Autre') ?><?php if ($reclamation['idFacture']): ?> — n° <?= (int)$reclamation['idFacture'] ?><?php endif; ?></td>
                        <td class="description"><?= nl2br(texteReclamation($reclamation['description'])) ?></td>
                        <td><?= texteReclamation($reclamation['statut']) ?></td>
                        <td><?= $reclamation['reponse'] !== null && $reclamation['reponse'] !== '' ? nl2br(texteReclamation($reclamation['reponse'])) : 'Aucune réponse' ?></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
