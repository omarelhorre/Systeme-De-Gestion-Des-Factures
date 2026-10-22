<?php if (!isset($data, $action, $message)) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factures fournisseur — Fatourti</title>
<link rel="stylesheet" href="public/style/dashboardF.css">
<link rel="stylesheet" href="public/style/facturesFournisseur.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
</head>
<body class="factures-module">
<nav class="menu" aria-label="Navigation fournisseur"><ul class="menu-list">
<li><span class="title">Fatourti</span></li>
<li><div class="grid">
<div class="border"><a class="section-txt" href="fournisseur/tableauDeBord">Consulter le tableau de bord</a></div>
<div class="border"><a class="section-txt" href="consommateur/all">Consulter vos clients</a></div>
<div class="border selected"><a class="section-txt" href="?action=liste" aria-current="page">Consulter les factures</a></div>
</div></li>
<li><p class="identity"><?= texteFacture(($_SESSION['user']['nom'] ?? '').' '.($_SESSION['user']['prenom'] ?? '')) ?></p><p class="role-style">Fournisseur</p></li>
</ul></nav>
<main class="main"><div class="container">
<?php if ($message !== '') { ?><p class="notice" role="status"><?= texteFacture($message) ?></p><?php } ?>
<?php if ($action === 'detail') { include __DIR__.'/factureDetail.php'; } else { ?>
<h1>Factures de vos clients</h1>
<p>Consultez les factures et enregistrez les paiements reçus en espèces.</p>
<nav class="filters" aria-label="Filtrer les factures">
<?php foreach (['' => 'Toutes', 'Payée' => 'Payées', 'Impayée' => 'Impayées'] as $valeur => $libelle) { ?>
<a class="filter <?= $etat === $valeur ? 'active' : '' ?>" href="?etat=<?= rawurlencode($valeur) ?>" <?= $etat === $valeur ? 'aria-current="page"' : '' ?>><?= $libelle ?></a>
<?php } ?></nav>
<div class="table-scroll"><table><thead><tr><th>Facture</th><th>Client</th><th>Génération</th><th>Période</th><th>Montant TTC</th><th>État</th><th>Action</th></tr></thead><tbody>
<?php foreach ($data as $ligne) { ?>
<tr><td>#<?= (int)$ligne['id'] ?></td><td><?= texteFacture($ligne['nom'].' '.$ligne['prenom']) ?></td><td><?= texteFacture($ligne['dateGeneration']) ?></td><td><?= texteFacture($ligne['dateDebut']) ?> — <?= texteFacture($ligne['dateFin'] ?? 'Non renseignée') ?></td><td><?= $ligne['prixTTC'] === null ? 'Non renseigné' : texteFacture(number_format((float)$ligne['prixTTC'], 2, ',', ' ')).' DH' ?></td><td><span class="badge <?= $ligne['etat'] === 'Payée' ? 'paid' : '' ?>"><?= texteFacture($ligne['etat']) ?></span></td><td><a class="modify-btn" href="?action=detail&amp;id=<?= (int)$ligne['id'] ?>">Consulter</a></td></tr>
<?php } ?>
<?php if (!$data) { ?><tr><td colspan="7">Aucune facture pour ce filtre.</td></tr><?php } ?>
</tbody></table></div>
<?php } ?>
</div></main></body></html>
