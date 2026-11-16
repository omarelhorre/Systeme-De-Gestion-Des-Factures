<?php if (!isset($reclamations)) { http_response_code(404); exit; } ?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Réclamations — Fatourti</title><link rel="stylesheet" href="<?= dir ?>/public/style/dashboardF.css"><link rel="stylesheet" href="<?= dir ?>/public/style/facturesFournisseur.css"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet"><script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script></head><body class="factures-module">
<nav class="menu" aria-label="Navigation fournisseur">
    <ul class="menu-list">
        <li><span class="title">Fatourti</span></li>
        <li>
            <div class="grid">
                <div class="border"><a class="section-txt" href="<?= link ?>fournisseur/tableauDeBord"><i class="fa-solid fa-user-group"></i> Consulter le tableau de bord</a></div>
                <div class="border"><a class="section-txt" href="<?= link ?>consommateur/all"><i class="fa-solid fa-user-group"></i> Consulter Vos Clients</a></div>
                <div class="border"><a class="section-txt" href="<?= link ?>facture/all"><i class="fa-solid fa-list"></i> Consulter les factures</a></div>
                <div class="border"><a class="section-txt" href="<?= link ?>declaration/anomalies"><i class="fa-solid fa-triangle-exclamation"></i> Corriger les anomalies</a></div>
                <div class="border"><a class="section-txt" href="<?=link?>annuelle/all"><i class="fa-solid fa-check"></i> Verifier Consommation Anuelle</a></div>
                <div class="border selected"><a class="section-txt" href="<?= link ?>reclamation/all" aria-current="page"><i class="fa-solid fa-exclamation"></i> Consulter La liste des reclamations</a></div>
            </div>
        </li>
        <li>
            <div class="connexion-section">
                <div class="login-creds">
                    <p class="identity"><?= texteReclamation(($_SESSION['user']['nom'] ?? '') . ' ' . ($_SESSION['user']['prenom'] ?? '')) ?></p>
                    <p class="role-style">Fournisseur</p>
                </div>
                <a class="element" href="<?= link ?>utilisateur/deconnexion"><button class="connexion-btn" type="button"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</button></a>
            </div>
        </li>
    </ul>
</nav>
<main class="main"><div class="container">
<h1>Liste des réclamations</h1>
<p>Consultez les réclamations de vos clients.</p>
<div class="table-scroll"><table>
<thead><tr><th>Numéro</th><th>Client</th><th>Date</th><th>Description</th><th>Statut</th><th>Réponse</th></tr></thead>
<tbody>
<?php foreach ($reclamations as $reclamation) { ?>
<tr>
<td>#<?= (int)$reclamation['idReclamation'] ?></td>
<td><?= texteReclamation($reclamation['nom'].' '.$reclamation['prenom']) ?></td>
<td><?= texteReclamation($reclamation['dateCreation']) ?></td>
<td><?= nl2br(texteReclamation($reclamation['description'])) ?></td>
<td><span class="badge"><?= texteReclamation($reclamation['statut']) ?></span></td>
<td><?= trim($reclamation['reponse'] ?? '') === '' ? 'Aucune réponse' : nl2br(texteReclamation($reclamation['reponse'])) ?></td>
</tr>
<?php } ?>
<?php if (empty($reclamations)) { ?>
<tr><td colspan="6">Aucune réclamation pour le moment.</td></tr>
<?php } ?>
</tbody></table></div>
</div></main></body></html>
