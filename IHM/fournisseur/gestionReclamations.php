<?php if (!isset($action, $statistiques, $reclamations)) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des réclamations — Fatourti</title>
    <link rel="stylesheet" href="<?= dir ?>/public/style/dashboardF.css">
    <link rel="stylesheet" href="<?= dir ?>/public/style/facturesFournisseur.css">
    <link rel="stylesheet" href="<?= dir ?>/public/style/gestionReclamations.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
</head>
<body class="factures-module reclamations-module">
<nav class="menu" aria-label="Navigation fournisseur">
    <ul class="menu-list">
        <li><span class="title">Fatourti</span></li>
        <li><div class="grid">
            <div class="border"><a class="section-txt" href="<?= link ?>fournisseur/tableauDeBord"><i class="fa-solid fa-user-group"></i> Consulter le tableau de bord</a></div>
            <div class="border"><a class="section-txt" href="<?= link ?>consommateur/all"><i class="fa-solid fa-user-group"></i> Consulter Vos Clients</a></div>
            <div class="border"><a class="section-txt" href="<?= link ?>facture/all"><i class="fa-solid fa-list"></i> Consulter les factures</a></div>
            <div class="border"><a class="section-txt" href="<?= link ?>declaration/anomalies"><i class="fa-solid fa-triangle-exclamation"></i> Corriger les anomalies</a></div>
            <div class="border"><a class="section-txt" href="<?=link?>annuelle/all"><i class="fa-solid fa-check"></i> Verifier Consommation Anuelle</a></div>
            <div class="border selected"><a class="section-txt" href="<?= link ?>reclamation/all" aria-current="page"><i class="fa-solid fa-exclamation"></i> Consulter La liste des reclamations</a></div>
        </div></li>
        <li><div class="connexion-section">
            <div class="login-creds">
                <p class="identity"><?= gestionReclamationsTexte(($_SESSION['user']['nom'] ?? '').' '.($_SESSION['user']['prenom'] ?? '')) ?></p>
                <p class="role-style">Fournisseur</p>
            </div>
            <a class="connexion-btn" href="<?= link ?>utilisateur/deconnexion"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
        </div></li>
    </ul>
</nav>
<main class="main"><div class="container">
<?php if ($message) { ?>
    <p class="notice <?= $message['avertissement'] ? 'avertissement' : '' ?>" role="status"><?= gestionReclamationsTexte($message['texte']) ?></p>
<?php } ?>
<?php if ($erreur !== '') { ?>
    <p class="correction-error" role="alert"><?= gestionReclamationsTexte($erreur) ?></p>
<?php } ?>

<?php if ($action === 'all') { ?>
    <h1>Gestion des réclamations</h1>
    <p>Consultez et traitez les réclamations de vos clients.</p>
    <div class="statistiques" aria-label="Statistiques de toutes vos réclamations">
        <div class="statistique"><strong><?= (int)$statistiques['total'] ?></strong><span>Total</span></div>
        <div class="statistique attente"><strong><?= (int)$statistiques['attente'] ?></strong><span>En attente</span></div>
        <div class="statistique encours"><strong><?= (int)$statistiques['encours'] ?></strong><span>En cours</span></div>
        <div class="statistique traitee"><strong><?= (int)$statistiques['traitee'] ?></strong><span>Traitées</span></div>
    </div>
    <form class="recherche-form" method="get" action="<?= link ?>reclamation/all">
        <div class="recherche-ligne">
            <label class="champ-recherche">Rechercher un client ou un numéro
                <input type="search" name="recherche" maxlength="200" value="<?= gestionReclamationsTexte($recherche) ?>" placeholder="Nom du client ou numéro de réclamation…">
            </label>
            <label>Type
                <select name="type">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $option) { ?>
                    <option value="<?= gestionReclamationsTexte($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= gestionReclamationsTexte($option) ?></option>
                    <?php } ?>
                </select>
            </label>
            <button class="connexion-btn" type="submit">Rechercher</button>
            <a class="retour" href="<?= link ?>reclamation/all">Réinitialiser</a>
        </div>
        <fieldset>
            <legend>Statut</legend>
            <?php foreach ($statuts as $valeur => $libelle) { ?>
            <label><input type="radio" name="statut" value="<?= $valeur ?>" <?= $filtreStatut === $valeur ? 'checked' : '' ?>> <?= $libelle ?></label>
            <?php } ?>
        </fieldset>
    </form>
    <p class="resultats"><?= count($reclamations) ?> résultat(s). Les compteurs portent sur toutes vos réclamations.</p>
    <div class="table-scroll"><table>
        <thead><tr><th>N°</th><th>Client / Type</th><th>Statut</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($reclamations as $ligne) { ?>
        <tr>
            <td>#<?= str_pad((string)(int)$ligne['idReclamation'], 3, '0', STR_PAD_LEFT) ?></td>
            <td class="client"><strong><?= gestionReclamationsTexte($ligne['nom'].' '.$ligne['prenom']) ?></strong>
                <small><?= gestionReclamationsTexte($ligne['typeReclamation']) ?> · <?= gestionReclamationsTexte(date('d/m/Y', strtotime($ligne['dateCreation']))) ?></small>
            </td>
            <td><span class="badge <?= gestionReclamationsStatut($ligne['statut']) ?>"><?= gestionReclamationsTexte($ligne['statut']) ?></span></td>
            <td><a class="modify-btn" href="<?= link ?>reclamation/detail/<?= (int)$ligne['idReclamation'] ?>">Voir<span class="sr-only"> la réclamation #<?= (int)$ligne['idReclamation'] ?></span></a></td>
        </tr>
        <?php } ?>
        <?php if (!$reclamations) { ?>
        <tr><td colspan="4">Aucune réclamation ne correspond à votre recherche.</td></tr>
        <?php } ?>
        </tbody>
    </table></div>
<?php } else { ?>
    <a class="retour" href="<?= link ?>reclamation/all">← Retour aux réclamations</a>
    <h1>Réclamation #<?= (int)$reclamation['idReclamation'] ?></h1>
    <dl>
        <dt>Client</dt><dd><?= gestionReclamationsTexte($reclamation['nom'].' '.$reclamation['prenom']) ?></dd>
        <dt>E-mail</dt><dd><?= gestionReclamationsTexte($reclamation['email']) ?></dd>
        <dt>Adresse</dt><dd><?= gestionReclamationsTexte($reclamation['adresse']) ?></dd>
        <dt>Date</dt><dd><?= gestionReclamationsTexte(date('d/m/Y', strtotime($reclamation['dateCreation']))) ?></dd>
        <dt>Type</dt><dd><?= gestionReclamationsTexte($reclamation['typeReclamation']) ?></dd>
        <dt>Statut actuel</dt><dd><span class="badge <?= gestionReclamationsStatut($reclamation['statut']) ?>"><?= gestionReclamationsTexte($reclamation['statut']) ?></span></dd>
        <dt>Description</dt><dd class="texte-long"><?= gestionReclamationsTexte($reclamation['description']) ?></dd>
    </dl>
    <form class="traitement-form" method="post" action="<?= link ?>reclamation/traiter/<?= (int)$reclamation['idReclamation'] ?>">
        <h2>Traiter la réclamation</h2>
        <input type="hidden" name="csrf" value="<?= gestionReclamationsTexte($_SESSION['csrf_gestion_reclamations']) ?>">
        <label>Statut
            <select name="statut" required>
                <?php if (gestionReclamationsStatut($reclamation['statut']) !== 'traitee') { ?>
                <option value="En cours" <?= ($statut ?? $reclamation['statut']) === 'En cours' ? 'selected' : '' ?>>En cours</option>
                <?php } ?>
                <option value="Traitée" <?= ($statut ?? $reclamation['statut']) === 'Traitée' ? 'selected' : '' ?>>Traitée</option>
            </select>
        </label>
        <label>Traitement effectué / réponse au client
            <textarea name="reponse" rows="6" maxlength="1000" required placeholder="Décrivez les actions effectuées et votre réponse au client."><?= gestionReclamationsTexte($reclamation['reponse']) ?></textarea>
        </label>
        <small>1 à 1000 caractères. Après chaque modification enregistrée, cette réponse est envoyée par e-mail au client. Un échec d’envoi sera signalé.</small>
        <button class="connexion-btn" type="submit">Enregistrer et notifier le client</button>
    </form>
<?php } ?>
</div></main>
</body></html>
