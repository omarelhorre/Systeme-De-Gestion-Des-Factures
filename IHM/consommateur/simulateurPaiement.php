<?php
// Simulateur de paiement (mode démo, sans identifiants CMI) : remplace la page de carte de CMI.
// Reçoit la commande signée par Fatourti et renvoie le même résultat signé que CMI.
// Variables fournies par paiement('simulateur') : $commande (les champs reçus), $id (la facture).
$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$coqueTitre = 'Paiement de test';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<main class="page-seule entree">
    <section class="carte">
        <p class="note"><i class="fa-solid fa-flask"></i> Simulation de la passerelle de paiement : aucune carte, aucun montant réel.</p>
        <h1 class="titre">Paiement de test</h1>
        <p class="chiffre geant"><?= $e(number_format((float)$commande['amount'], 2, ',', ' ')) ?><span class="unite">DH</span></p>
        <dl class="details">
            <dt>Marchand</dt><dd>Fatourti</dd>
            <dt>Client</dt><dd><?= $e($commande['BillToName'] ?? '') ?></dd>
            <dt>Commande</dt><dd><?= $e($commande['oid']) ?></dd>
        </dl>
        <form class="actions" method="post" action="<?= link ?>paiement/simulateur/<?= (int)$id ?>" style="margin-top: 0.5rem">
            <?php foreach ($commande as $nom => $valeur) { ?><input type="hidden" name="<?= $e($nom) ?>" value="<?= $e($valeur) ?>"><?php } ?>
            <button class="bouton contour" type="submit" name="decision" value="refuser">Refuser</button>
            <button class="bouton accent" type="submit" name="decision" value="accepter"><i class="fa-solid fa-check"></i> Accepter le paiement</button>
        </form>
    </section>
</main>
</body>
</html>
