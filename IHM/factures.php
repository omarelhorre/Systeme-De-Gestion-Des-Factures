<?php $escape = static function($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factures - Fatourti</title>
<link href="<?=dir?>public/style/dashboardF.css" rel="stylesheet">
<link href="<?=dir?>public/style/factures.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;900&display=swap" rel="stylesheet">
<script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body>
<nav class="menu"><ul class="menu-list">
<li><span class="title">Fatourti</span></li>
<li><div class="grid">
<div class="border selected"><a class="section-txt" href="<?=dir.($fournisseur ? 'fournisseur' : 'consommateur')?>/factures"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i> Consulter les factures</a></div>
<div class="border"><a class="section-txt" href="<?=dir.($fournisseur ? 'fournisseur' : 'consommateur')?>/tableauDeBord"><i class="fa-solid fa-user-group" aria-hidden="true"></i> Consulter le tableau de bord</a></div>
<?php if ($fournisseur) { ?>
<div class="border"><a class="section-txt" href="<?=dir?>consommateur/all"><i class="fa-solid fa-user-group" aria-hidden="true"></i> Consulter Vos Clients</a></div>
<div class="border"><a class="section-txt" href=""><i class="fa-solid fa-list" aria-hidden="true"></i>Consulter Les Declaration</a></div>
<div class="border"><a class="section-txt" href=""><i class="fa-solid fa-check" aria-hidden="true"></i>Verifier Consommation Anuelle</a></div>
<div class="border"><a class="section-txt" href=""><i class="fa-solid fa-exclamation" aria-hidden="true"></i>Consulter La liste des reclamations</a></div>
<?php } ?>
</div></li>
<li><div class="connexion-section"><div class="login-creds"><p class="identity"><?=$escape($_SESSION['user']['nom'].' '.$_SESSION['user']['prenom'])?></p><p class="role-style"><?=$fournisseur ? 'Fournisseur' : 'Consommateur'?></p></div><a class="element" href="<?=dir?>utilisateur/authentification"><button class="connexion-btn"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>Déconnexion</button></a></div></li>
</ul></nav>
<main class="main factures-main"><div class="container factures-container"><div class="centering factures-content">
<h3><?=$fournisseur ? 'Gestion des factures' : 'Mes factures'?></h3>
<?php if ($message) { ?><p role="status" class="factures-message"><?=$escape($message)?></p><?php } ?>
<?php if ($fournisseur) { ?>
<form method="get"><label for="client">Consommateur</label> <select id="client" name="client"><option value="0">Tous mes consommateurs</option><?php foreach ($clients as $client) { ?><option value="<?=(int)$client['id']?>" <?=$clientId === (int)$client['id'] ? 'selected' : ''?>><?=$escape($client['nom'].' '.$client['prenom'])?></option><?php } ?></select> <button class="modify-btn">Afficher</button></form>
<p>Confirmez le règlement uniquement après réception des espèces.</p>
<?php } else { ?><p>Après votre règlement en espèces, le fournisseur enregistre le paiement. Son statut apparaît ici.</p><?php } ?>
<div class="factures-table"><table><thead><tr><th>N°</th><?php if ($fournisseur) { ?><th>Consommateur</th><?php } ?><th>Période</th><th>HT / TTC (DH)</th><th>État</th><th>Date de paiement</th><?php if ($fournisseur) { ?><th>Paiement en espèces</th><?php } ?></tr></thead><tbody>
<?php foreach ($factures as $facture) { $payee = $facture['datePaiement'] !== null || $facture['etat'] === 'Payée'; ?>
<tr><td><?=(int)$facture['id']?></td><?php if ($fournisseur) { ?><td><?=$escape($facture['nom'].' '.$facture['prenom'])?></td><?php } ?><td><?=$escape($facture['dateDebut'])?><br><?=$escape($facture['dateFin'] ?? '—')?></td><td><?=$facture['prixHT'] === null ? '—' : number_format((float)$facture['prixHT'], 2, ',', ' ')?> / <?=$facture['prixTTC'] === null ? '—' : number_format((float)$facture['prixTTC'], 2, ',', ' ')?></td><td><strong><?=$payee ? 'Payée' : $escape($facture['etat'])?></strong></td><td><?=$escape($facture['datePaiement'] ?? '—')?></td>
<?php if ($fournisseur) { ?><td><?php if (!$payee && $facture['etat'] === 'Impayée') { ?><form method="post"><input type="hidden" name="csrf" value="<?=$escape($_SESSION['factures_csrf'])?>"><input type="hidden" name="facture" value="<?=(int)$facture['id']?>"><label><input type="checkbox" name="confirmation" value="1" required> Espèces reçues</label><button class="modify-btn" type="submit">Enregistrer le paiement</button></form><?php } else { ?><?=$payee ? 'Déjà payée' : 'Non payable'?><?php } ?></td><?php } ?></tr>
<?php } ?>
<?php if (!$factures) { ?><tr><td colspan="<?=$fournisseur ? 7 : 6?>">Aucune facture disponible.</td></tr><?php } ?>
</tbody></table></div></div></div></main>
</body></html>
