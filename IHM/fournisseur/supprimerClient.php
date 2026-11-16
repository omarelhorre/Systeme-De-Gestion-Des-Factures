<?php
// Confirmation avant de supprimer un client. Variables fournies par supprimer() :
// $client, $historique (relevés, factures, réclamations), $contrat.
$coqueTitre = 'Supprimer un client';
$coqueActif = 'clients';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
$pluriel = function ($nombre, $mot) { return (int)$nombre.' '.$mot.((int)$nombre > 1 ? 's' : ''); };
$aHistorique = $historique['releves'] > 0 || $historique['factures'] > 0 || $historique['reclamations'] > 0;
$contratActif = $contrat && ($contrat['statut'] ?? '') === 'actif';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <div class="centre">
        <form class="carte" method="post" action="<?= link ?>consommateur/supprimer/<?= (int)$client['id'] ?>">
            <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_suppression_client']) ?>">
            <h1 class="titre-carte">Supprimer <?= $e($client['prenom'].' '.$client['nom']) ?> ?</h1>

            <?php if ($aHistorique) { ?>
                <p class="erreur-bloc" style="margin: 0; background: var(--page)">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Ce client a un historique : <?= $pluriel($historique['releves'], 'relevé') ?>, <?= $pluriel($historique['factures'], 'facture') ?> et <?= $pluriel($historique['reclamations'], 'réclamation') ?>. Tout sera supprimé, et ses factures disparaîtront de vos totaux.</span>
                </p>
                <p class="legende">S'il a simplement cessé d'être client, résiliez plutôt son contrat : son historique est conservé.</p>
            <?php } else { ?>
                <p class="legende">Son compte, ses adresses et son contrat seront supprimés.</p>
            <?php } ?>
            <p class="legende">Cette opération ne peut pas être annulée.</p>

            <div class="actions fin" style="margin-top: 0.75rem">
                <a class="bouton contour" href="<?= link ?>consommateur/all">Retour</a>
                <?php if ($aHistorique && $contratActif) { ?>
                    <a class="bouton plein" href="<?= link ?>contrat/resilier/<?= (int)$client['id'] ?>">Résilier le contrat</a>
                <?php } ?>
                <button class="bouton danger" type="submit"><i class="fa-solid fa-trash"></i> Supprimer définitivement</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
