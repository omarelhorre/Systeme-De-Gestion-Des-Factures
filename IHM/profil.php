<?php
// Page "Mon compte". Variables fournies par profil() :
// $utilisateur, $role ('fournisseur', 'consommateur' ou 'agent'), $consommateur, $message, $erreur.
$coqueTitre = 'Mon compte';
$coqueActif = 'profil';
$e = function ($texte) { return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8'); };
$ouNonRenseigne = function ($texte) use ($e) { return trim((string)$texte) === '' ? 'Non renseigné' : $e($texte); };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/'.$role.'/menu.php'; ?>

<main class="contenu entree">
    <header class="entete">
        <h1 class="titre">Mon compte</h1>
        <p class="sous-titre">Modifiez votre email ou votre mot de passe.</p>
    </header>

    <?php if ($message) { ?><p class="message" role="status"><i class="fa-solid fa-circle-check"></i><span><?= $e($message) ?></span></p><?php } ?>
    <?php if ($erreur) { ?><p class="erreur-bloc" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= $e($erreur) ?></span></p><?php } ?>

    <div class="grille">
        <!-- Identité : affichée en gris, non modifiable par l'utilisateur -->
        <section class="carte" style="grid-column: 1 / -1">
            <div class="compte-ligne">
            <div class="identite">
                <i class="fa-solid fa-circle-user avatar-defaut" aria-hidden="true"></i>
                <div class="tronque">
                    <p class="titre-carte"><?= $e($utilisateur['prenom'].' '.$utilisateur['nom']) ?></p>
                    <p class="legende" style="margin-top: 0.125rem"><?= ucfirst($role) ?></p>
                </div>
            </div>

            <div class="compte-champs">
                <label class="champ"><span class="libelle">Prénom</span>
                    <input type="text" value="<?= $e($utilisateur['prenom']) ?>" disabled>
                </label>
                <label class="champ"><span class="libelle">Nom</span>
                    <input type="text" value="<?= $e($utilisateur['nom']) ?>" disabled>
                </label>
                <?php if ($role === 'consommateur') { ?>
                    <label class="champ"><span class="libelle">Numéro d'identité</span>
                        <input type="text" value="<?= $ouNonRenseigne($consommateur['cin'] ?? '') ?>" disabled>
                    </label>
                    <label class="champ"><span class="libelle">Téléphone</span>
                        <input type="text" value="<?= $ouNonRenseigne($consommateur['telephone'] ?? '') ?>" disabled>
                    </label>
                <?php } ?>
            </div>
            </div>
            <?php if ($role === 'consommateur') { ?>
                <p class="note" style="margin-top: 1rem">Ces informations sont gérées par votre fournisseur. Contactez-le pour les corriger.</p>
            <?php } ?>
        </section>

        <section class="carte large">
            <h2 class="titre-carte">Email</h2>
            <form class="formulaire" style="margin-top: 1rem" method="post" action="<?= link ?>utilisateur/modifierEmail">
                <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_profil']) ?>">
                <label class="champ"><span class="libelle">Email</span>
                    <input type="email" name="email" maxlength="100" value="<?= $e($_POST['email'] ?? $utilisateur['email']) ?>" autocomplete="email" required>
                </label>
                <label class="champ"><span class="libelle">Mot de passe actuel</span>
                    <input type="password" name="motDePasseActuel" autocomplete="current-password" required>
                </label>
                <div class="entier actions fin">
                    <button class="bouton plein" type="submit"><i class="fa-solid fa-envelope"></i> Changer l'email</button>
                </div>
            </form>
        </section>

        <section class="carte large">
            <h2 class="titre-carte">Mot de passe</h2>
            <form class="formulaire" style="margin-top: 1rem" method="post" action="<?= link ?>utilisateur/modifierMotDePasse">
                <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_profil']) ?>">
                <label class="champ entier"><span class="libelle">Mot de passe actuel</span>
                    <input type="password" name="motDePasseActuel" autocomplete="current-password" required>
                </label>
                <label class="champ"><span class="libelle">Nouveau mot de passe</span>
                    <input type="password" name="nouveauMotDePasse" minlength="8" maxlength="72" autocomplete="new-password" required>
                </label>
                <label class="champ"><span class="libelle">Confirmer le nouveau</span>
                    <input type="password" name="confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
                </label>
                <div class="entier actions fin">
                    <button class="bouton accent" type="submit"><i class="fa-solid fa-lock"></i> Changer le mot de passe</button>
                </div>
            </form>
        </section>
    </div>
</main>
</body>
</html>
