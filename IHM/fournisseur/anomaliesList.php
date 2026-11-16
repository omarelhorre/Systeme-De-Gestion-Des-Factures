<?php if (!isset($data, $action, $message, $erreur)) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anomalies de consommation — Fatourti</title>
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
                <div class="border"><a class="section-txt" href="<?= link ?>facture/all"><i class="fa-solid fa-list"></i> Consulter les factures</a></div>
                <div class="border selected"><a class="section-txt" href="<?= link ?>declaration/anomalies" aria-current="page"><i class="fa-solid fa-triangle-exclamation"></i> Corriger les anomalies</a></div>
                <div class="border"><a class="section-txt" href="<?=link?>annuelle/all"><i class="fa-solid fa-check"></i> Verifier Consommation Anuelle</a></div>
                <div class="border"><a class="section-txt" href=""><i class="fa-solid fa-exclamation"></i> Consulter La liste des reclamations</a></div>
            </div>
        </li>
        <li>
            <div class="connexion-section">
                <div class="login-creds">
                    <p class="identity"><?= texteAnomalie(($_SESSION['user']['nom'] ?? '') . ' ' . ($_SESSION['user']['prenom'] ?? '')) ?></p>
                    <p class="role-style">Fournisseur</p>
                </div>
                <a class="element" href="<?= link ?>utilisateur/deconnexion"><button class="connexion-btn" type="button"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</button></a>
            </div>
        </li>
    </ul>
</nav>
<main class="main"><div class="container">
    <?php if ($message !== ''): ?><p class="notice" role="status"><?= texteAnomalie($message) ?></p><?php endif; ?>
    <?php if ($action === 'corriger'): ?>
        <?php require ROOT . 'IHM/fournisseur/anomalieDetail.php'; ?>
    <?php else: ?>
        <h1>Déclarations anormales</h1>
        <p>Consultez les photos avant de corriger les index.</p>
        <div class="table-scroll"><table>
            <thead><tr><th>Déclaration</th><th>Client</th><th>Adresse</th><th>Date</th><th>Index eau</th><th>Index électricité</th><th>Anomalie</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($data as $ligne): ?>
                <tr>
                    <td>#<?= (int)$ligne['id'] ?></td>
                    <td><?= texteAnomalie($ligne['nom'] . ' ' . $ligne['prenom']) ?></td>
                    <td><?= texteAnomalie($ligne['adresse']) ?></td>
                    <td><?= texteAnomalie($ligne['dateSaisie']) ?></td>
                    <td><?= texteAnomalie($ligne['indexEau']) ?></td>
                    <td><?= texteAnomalie($ligne['indexElectricite']) ?></td>
                    <td><?= texteAnomalie($ligne['statut']) ?></td>
                    <td><a class="modify-btn" href="<?= link ?>declaration/corriger/<?= (int)$ligne['id'] ?>">Examiner</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$data): ?><tr><td colspan="8">Aucune anomalie en attente.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div></main>
</body>
</html>
