<?php
// Tableau de bord du consommateur : trois raccourcis vers ses pages.
$coqueTitre = 'Tableau de bord';
$coqueActif = 'tableau';
$moisLongs = array('', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
// Même règle que la page des relevés : la déclaration est ouverte du 18 au dernier jour du mois
$declarationOuverteAujourdhui = (int)date('j') >= 18;
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
        <h1 class="titre">Bonjour, <?= htmlspecialchars($_SESSION['user']['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="sous-titre"><?= (int)date('j').' '.$moisLongs[(int)date('n')].' '.date('Y') ?></p>
    </header>

    <div class="grille">
        <!-- Widget principal : la déclaration du mois -->
        <section class="carte large haute">
            <h2 class="titre-carte">Déclaration de <?= $moisLongs[(int)date('n')] ?></h2>
            <p class="chiffre geant" style="margin-top: 0.5rem"><?= $declarationOuverteAujourdhui ? 'Ouverte' : 'Le 18' ?></p>
            <p class="legende">
                <?php if ($declarationOuverteAujourdhui) { ?>
                    Vous pouvez envoyer vos index jusqu'au <strong><?= date('t').' '.$moisLongs[(int)date('n')] ?></strong>.
                <?php } else { ?>
                    La déclaration ouvre du <strong>18 au <?= date('t').' '.$moisLongs[(int)date('n')] ?></strong>.
                <?php } ?>
            </p>
            <div class="carte-pied">
                <p class="legende">
                    <i class="fa-solid fa-droplet" style="color: var(--eau)"></i> Index d'eau &nbsp;
                    <i class="fa-solid fa-bolt" style="color: var(--elec)"></i> Index d'électricité
                </p>
                <a class="bouton accent" href="<?= link ?>declaration/all">Déclarer ma consommation <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </section>

        <section class="carte large">
            <div class="carte-entete">
                <h2 class="titre-carte">Relevés et factures</h2>
                <a class="bouton-rond" href="<?= link ?>declaration/all" aria-label="Voir mes relevés et factures"><i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <p class="legende">Retrouvez l'historique de vos index, les photos de vos compteurs et vos factures en PDF.</p>
        </section>

        <section class="carte large">
            <div class="carte-entete">
                <h2 class="titre-carte">Réclamations</h2>
                <a class="bouton-rond" href="<?= link ?>reclamations.php" aria-label="Voir mes réclamations"><i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <p class="legende">Signalez une fuite ou un problème de facture et suivez la réponse de votre fournisseur.</p>
        </section>
    </div>
</main>
</body>
</html>
