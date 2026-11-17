<?php if (!isset($action, $statistiques, $reclamations)) { http_response_code(404); exit; } ?>
<?php
// Réclamations des clients : liste avec recherche ($action === 'all'), ou traitement d'une réclamation.
$coqueTitre = 'Réclamations';
$coqueActif = 'reclamations';
// "Traitée" = vert, le reste = neutre
require_once ROOT.'IHM/commun/outils.php';   // statutReclamation() et ilYa()
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
<?php if ($message) { ?>
    <p class="<?= $message['avertissement'] ? 'erreur-bloc' : 'message' ?>" role="status"><i class="fa-solid <?= $message['avertissement'] ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i><span><?= gestionReclamationsTexte($message['texte']) ?></span></p>
<?php } ?>
<?php if ($erreur !== '') { ?>
    <p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= gestionReclamationsTexte($erreur) ?></span></p>
<?php } ?>

<?php if ($action === 'all') { ?>
    <header class="entete">
        <h1 class="titre">Réclamations</h1>
        <p class="sous-titre">Consultez et traitez les réclamations de vos clients.</p>
    </header>

    <div class="grille" aria-label="Statistiques de toutes vos réclamations">
        <section class="carte"><h2 class="titre-carte">Total</h2><p class="chiffre" style="margin-top: 0.75rem"><?= (int)$statistiques['total'] ?></p></section>
        <section class="carte"><h2 class="titre-carte">En attente</h2><p class="chiffre" style="margin-top: 0.75rem"><?= (int)$statistiques['attente'] ?></p></section>
        <section class="carte"><h2 class="titre-carte">En cours</h2><p class="chiffre" style="margin-top: 0.75rem"><?= (int)$statistiques['encours'] ?></p></section>
        <section class="carte"><h2 class="titre-carte">Traitées</h2><p class="chiffre" style="margin-top: 0.75rem"><?= (int)$statistiques['traitee'] ?></p></section>

        <section class="carte" style="grid-column: 1 / -1">
            <!-- Barre d'outils : la recherche part avec Entrée, le type et le statut dès qu'on les change -->
            <form class="barre-outils" method="get" action="<?= link ?>reclamation/all">
                <label class="outil"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" name="recherche" maxlength="200" value="<?= gestionReclamationsTexte($recherche) ?>" placeholder="Rechercher" aria-label="Rechercher un client ou un numéro de réclamation">
                </label>
                <label class="outil"><i class="fa-solid fa-filter" aria-hidden="true"></i>
                    <select name="type" aria-label="Type" onchange="this.form.submit()">
                        <option value="">Type</option>
                        <?php foreach ($types as $option) { ?>
                        <option value="<?= gestionReclamationsTexte($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= gestionReclamationsTexte($option) ?></option>
                        <?php } ?>
                    </select>
                </label>
                <div class="filtres dans-carte" role="radiogroup" aria-label="Statut">
                    <?php foreach ($statuts as $valeur => $libelle) { ?>
                    <label><input type="radio" name="statut" value="<?= $valeur ?>" <?= $filtreStatut === $valeur ? 'checked' : '' ?> onchange="this.form.submit()"> <?= $libelle ?></label>
                    <?php } ?>
                </div>
                <?php if ($recherche !== '' || $type !== '' || $filtreStatut !== array_key_first($statuts)) { ?>
                <a class="lien-discret" href="<?= link ?>reclamation/all">Réinitialiser <i class="fa-solid fa-xmark" aria-hidden="true"></i></a>
                <?php } ?>
            </form>

            <?php if ($reclamations) { ?>
                <table class="tableau">
                    <thead><tr><th class="cache-mobile">N°</th><th>Client</th><th class="secondaire">Type</th><th>Statut</th><th></th></tr></thead>
                    <tbody data-pagine="8">
                    <?php foreach ($reclamations as $ligne) { ?>
                        <tr>
                            <td class="nombre cache-mobile">N° <?= str_pad((string)(int)$ligne['idReclamation'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <div class="tronque"><span class="fort"><?= gestionReclamationsTexte($ligne['prenom'].' '.$ligne['nom']) ?></span>
                                <small><?= gestionReclamationsTexte(date('d/m/Y', strtotime($ligne['dateCreation']))) ?> · <?= ilYa($ligne['dateCreation']) ?></small></div>
                            </td>
                            <td class="secondaire"><span class="tag"><?= gestionReclamationsTexte($ligne['typeReclamation']) ?></span></td>
                            <?php list($libelle, $classe) = statutReclamation($ligne['statut']); ?><td><span class="etat <?= $classe ?>"><?= $libelle ?></span></td>
                            <td class="fin"><a class="bouton-rond" href="<?= link ?>reclamation/detail/<?= (int)$ligne['idReclamation'] ?>" aria-label="Voir la réclamation <?= (int)$ligne['idReclamation'] ?>"><i class="fa-solid fa-arrow-right"></i></a></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p class="vide">Aucune réclamation ne correspond à votre recherche.</p>
            <?php } ?>
        </section>
    </div>
<?php } else { ?>
    <a class="lien-retour" href="<?= link ?>reclamation/all"><i class="fa-solid fa-arrow-left"></i> Retour aux réclamations</a>
    <header class="entete ligne">
        <div>
            <h1 class="titre">Réclamation N° <?= (int)$reclamation['idReclamation'] ?></h1>
            <p class="sous-titre"><?= gestionReclamationsTexte($reclamation['prenom'].' '.$reclamation['nom']) ?> · <?= gestionReclamationsTexte(date('d/m/Y', strtotime($reclamation['dateCreation']))) ?> · <?= ilYa($reclamation['dateCreation']) ?></p>
        </div>
        <?php list($libelle, $classe) = statutReclamation($reclamation['statut']); ?><span class="etat <?= $classe ?>"><?= $libelle ?></span>
    </header>

    <div class="grille">
        <section class="carte large">
            <h2 class="titre-carte">Demande</h2>
            <dl class="details" style="margin-top: 1rem">
                <dt>Client</dt><dd><?= gestionReclamationsTexte($reclamation['prenom'].' '.$reclamation['nom']) ?></dd>
                <dt>E-mail</dt><dd><?= gestionReclamationsTexte($reclamation['email']) ?></dd>
                <dt>Adresse</dt><dd><?= gestionReclamationsTexte($reclamation['adresse']) ?></dd>
                <dt>Type</dt><dd><?= gestionReclamationsTexte($reclamation['typeReclamation']) ?></dd>
            </dl>
            <p class="bloc" style="margin-top: 1rem; line-height: 1.5; overflow-wrap: anywhere"><?= nl2br(gestionReclamationsTexte($reclamation['description'])) ?></p>
        </section>

        <section class="carte large">
            <h2 class="titre-carte">Traiter la réclamation</h2>
            <form class="formulaire" style="margin-top: 1rem" method="post" action="<?= link ?>reclamation/traiter/<?= (int)$reclamation['idReclamation'] ?>">
                <input type="hidden" name="csrf" value="<?= gestionReclamationsTexte($_SESSION['csrf_gestion_reclamations']) ?>">
                <label class="champ entier"><span class="libelle">Statut</span>
                    <select name="statut" required>
                        <?php if (gestionReclamationsStatut($reclamation['statut']) !== 'traitee') { ?>
                        <option value="En cours" <?= ($statut ?? $reclamation['statut']) === 'En cours' ? 'selected' : '' ?>>En cours</option>
                        <?php } ?>
                        <option value="Traitée" <?= ($statut ?? $reclamation['statut']) === 'Traitée' ? 'selected' : '' ?>>Traitée</option>
                    </select>
                </label>
                <label class="champ entier"><span class="libelle">Traitement effectué / réponse au client</span>
                    <textarea name="reponse" rows="6" maxlength="1000" required placeholder="Décrivez les actions effectuées et votre réponse au client."><?= gestionReclamationsTexte($reclamation['reponse']) ?></textarea>
                </label>
                <p class="note entier">1 à 1000 caractères. Après chaque modification enregistrée, cette réponse est envoyée par e-mail au client. Un échec d’envoi sera signalé.</p>
                <div class="entier actions fin">
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-paper-plane"></i> Enregistrer et notifier le client</button>
                </div>
            </form>
        </section>
    </div>
<?php } ?>
</main>
</body>
</html>
