<?php
// Barre du haut + menu d'icônes, communs à tous les rôles.
// Les fichiers menu.php de chaque rôle définissent :
//   $coqueMenu    : liste de [clé, adresse, icône Font Awesome, libellé]
//   $coqueRole    : nom du rôle affiché à droite
//   $coqueAccueil : adresse du tableau de bord du rôle
// et la page définit $coqueActif (la clé de l'entrée active).
$coqueNom = trim(($_SESSION['user']['prenom'] ?? '').' '.($_SESSION['user']['nom'] ?? ''));
?>
<header class="barre">
    <a class="marque" href="<?= link.$coqueAccueil ?>">Fatourti</a>
    <div class="barre-droite">
        <!-- Cloche des notifications : la liste est chargée et rafraîchie en AJAX par app.js -->
        <button class="cloche" type="button" id="cloche" aria-label="Notifications" aria-expanded="false" aria-controls="notifs" data-site="<?= link ?>">
            <i class="fa-solid fa-bell"></i>
            <span class="cloche-nombre" id="cloche-nombre" hidden></span>
        </button>

        <!-- Le nom mène à la page "Mon compte" ; l'avatar est l'icône par défaut de Font Awesome -->
        <a class="compte" href="<?= link ?>utilisateur/profil" title="Mon compte">
            <span class="compte-nom"><?= htmlspecialchars($coqueNom, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="compte-role"><?= $coqueRole ?></span>
            <i class="fa-solid fa-circle-user compte-avatar" aria-hidden="true"></i>
        </a>
    </div>

    <!-- Fenêtre des notifications (cachée tant qu'on n'a pas cliqué sur la cloche) -->
    <section class="notifs" id="notifs" aria-label="Notifications" hidden>
        <!-- Vue 1 : la liste -->
        <div id="notifs-liste-vue">
            <div class="carte-entete">
                <h2 class="titre-carte">Notifications</h2>
                <button class="notifs-lien" type="button" id="notifs-tout-lire">Tout marquer comme lu</button>
            </div>
            <div class="filtres dans-carte" role="tablist">
                <button type="button" role="tab" data-onglet="toutes" class="actif">Toutes <span></span></button>
                <button type="button" role="tab" data-onglet="traiter">À traiter <span></span></button>
                <button type="button" role="tab" data-onglet="infos">Infos <span></span></button>
            </div>
            <ul class="notifs-liste" id="notifs-liste"></ul>
            <div class="pagination" id="notifs-pagination" hidden>
                <button class="bouton accent petit" type="button">Précédent</button>
                <span class="pagination-numero"></span>
                <button class="bouton accent petit" type="button">Suivant</button>
            </div>
            <p class="notifs-pied"><a href="<?= link ?>utilisateur/profil"><i class="fa-solid fa-user-gear"></i> Mon compte</a><span>Les plus importantes partent aussi par email</span></p>
        </div>

        <!-- Vue 2 : répondre à une réclamation sans quitter la page (fournisseur) -->
        <form id="notifs-reponse" method="post" hidden>
            <button class="notifs-lien" type="button" id="notifs-retour"><i class="fa-solid fa-arrow-left"></i> Notifications</button>
            <h2 class="titre-carte" id="notifs-reponse-titre"></h2>
            <p class="bloc" id="notifs-reponse-detail"></p>
            <input type="hidden" name="csrf">
            <label class="champ"><span class="libelle">Statut</span>
                <select name="statut" required>
                    <option value="En cours">En cours</option>
                    <option value="Traitée">Traitée</option>
                </select>
            </label>
            <label class="champ"><span class="libelle">Réponse au client</span>
                <textarea name="reponse" rows="4" maxlength="1000" required placeholder="Décrivez les actions effectuées."></textarea>
            </label>
            <div class="actions fin">
                <button class="bouton accent" type="submit"><i class="fa-solid fa-paper-plane"></i> Enregistrer et notifier le client</button>
            </div>
        </form>
    </section>
</header>

<nav class="menu" aria-label="Menu <?= strtolower($coqueRole) ?>">
    <div class="menu-liens">
        <?php foreach ($coqueMenu as $coqueEntree) { ?>
            <a class="<?= ($coqueActif ?? '') === $coqueEntree[0] ? 'actif' : '' ?>" href="<?= link.$coqueEntree[1] ?>"
               title="<?= $coqueEntree[3] ?>" aria-label="<?= $coqueEntree[3] ?>"<?= ($coqueActif ?? '') === $coqueEntree[0] ? ' aria-current="page"' : '' ?>>
                <i class="fa-solid <?= $coqueEntree[2] ?>"></i>
            </a>
        <?php } ?>
    </div>
    <!-- Mon compte, juste au-dessus de la déconnexion -->
    <a class="<?= ($coqueActif ?? '') === 'profil' ? 'actif' : '' ?>" href="<?= link ?>utilisateur/profil" title="Mon compte" aria-label="Mon compte">
        <i class="fa-solid fa-user-gear"></i>
    </a>
    <a class="sortie" href="<?= link ?>utilisateur/deconnexion" title="Déconnexion" aria-label="Déconnexion">
        <i class="fa-solid fa-door-open"></i>
    </a>
</nav>
