<?php if (!isset($data, $action, $message)) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factures fournisseur — Fatourti</title>
<link rel="stylesheet" href="<?= dir ?>/public/style/dashboardF.css">
<link rel="stylesheet" href="<?= dir ?>/public/style/facturesFournisseur.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
<script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body class="factures-module">
<nav class="menu" aria-label="Navigation fournisseur">
    <ul class="menu-list">
        <li><span class="title">Fatourti</span></li>
        <li>
            <div class="grid">
                <div class="border"><a class="section-txt" href="<?= link ?>fournisseur/tableauDeBord"><i class="fa-solid fa-user-group"></i> Consulter le tableau de bord</a></div>
                <div class="border"><a class="section-txt" href="<?= link ?>consommateur/all"><i class="fa-solid fa-user-group"></i> Consulter Vos Clients</a></div>
                <div class="border selected"><a class="section-txt" href="<?= link ?>facture/all" aria-current="page"><i class="fa-solid fa-list"></i> Consulter les factures</a></div>
                <div class="border"><a class="section-txt" href="<?= link ?>declaration/anomalies"><i class="fa-solid fa-triangle-exclamation"></i> Corriger les anomalies</a></div>
                <div class="border"><a class="section-txt" href="<?=link?>annuelle/all"><i class="fa-solid fa-check"></i> Verifier Consommation Anuelle</a></div>
                <div class="border"><a class="section-txt" href=""><i class="fa-solid fa-exclamation"></i> Consulter La liste des reclamations</a></div>
            </div>
        </li>
        <li>
            <div class="connexion-section">
                <div class="login-creds">
                    <p class="identity"><?= texteFacture(($_SESSION['user']['nom'] ?? '') . ' ' . ($_SESSION['user']['prenom'] ?? '')) ?></p>
                    <p class="role-style">Fournisseur</p>
                </div>
                <a class="element" href="<?= link ?>utilisateur/deconnexion"><button class="connexion-btn" type="button"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</button></a>
            </div>
        </li>
    </ul>
</nav>
<main class="main"><div class="container">
<?php if ($message !== '') { ?><p class="notice" role="status"><?= texteFacture($message) ?></p><?php } ?>
<?php if ($action === 'detail') { include __DIR__.'/factureDetail.php'; } else { ?>
<h1>Factures de vos clients</h1>
<p>Consultez les factures et enregistrez les paiements reçus en espèces.</p>
<nav class="filters" aria-label="Filtrer les factures">
<?php foreach (['' => 'Toutes', 'Payée' => 'Payées', 'Impayée' => 'Impayées'] as $valeur => $libelle) { ?>
<a class="filter <?= $etat === $valeur ? 'active' : '' ?>" href="<?= link ?>facture/all?etat=<?= rawurlencode($valeur) ?>" <?= $etat === $valeur ? 'aria-current="page"' : '' ?>><?= $libelle ?></a>
<?php } ?></nav>
<div class="table-scroll"><table><thead><tr><th>Facture</th><th>Client</th><th>Adresse</th><th>Génération</th><th>Période</th><th>Montant TTC</th><th>État</th><th>Action</th></tr></thead><tbody>
<?php foreach ($data as $ligne) { ?>
<tr><td>#<?= (int)$ligne['id'] ?></td><td><?= texteFacture($ligne['nom'].' '.$ligne['prenom']) ?></td><td><?= texteFacture($ligne['adresse']) ?></td><td><?= texteFacture($ligne['dateGeneration']) ?></td><td><?= texteFacture($ligne['dateDebut']) ?> — <?= texteFacture($ligne['dateFin'] ?? 'Non renseignée') ?></td><td><?= $ligne['prixTTC'] === null ? 'Non renseigné' : texteFacture(number_format((float)$ligne['prixTTC'], 2, ',', ' ')).' DH' ?></td><td><span class="badge <?= $ligne['etat'] === 'Payée' ? 'paid' : '' ?>"><?= texteFacture($ligne['etat']) ?></span></td><td><a class="modify-btn" href="<?= link ?>facture/detail/<?= (int)$ligne['id'] ?>">Consulter</a></td></tr>
<?php } ?>
<?php if (!$data) { ?><tr><td colspan="8">Aucune facture pour ce filtre.</td></tr><?php } ?>
</tbody></table></div>
<?php } ?>
</div></main></body></html>
