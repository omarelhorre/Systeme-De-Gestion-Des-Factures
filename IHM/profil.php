<?php
// Page "Mon compte". Variables fournies par profil() :
// $utilisateur, $role ('fournisseur', 'consommateur' ou 'agent'), $consommateur, $infosCompte, $message, $erreur.
require_once ROOT.'IHM/commun/outils.php';
require_once ROOT.'Traitement/consommateur/reglesConsommation.php';   // grille tarifaire affichée au fournisseur
$jour = function ($date) { return $date ? date('d/m/Y', strtotime($date)) : '—'; };
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

        <?php if ($role === 'consommateur') {
            $factures = $infosCompte['factures'];
            $reclamations = $infosCompte['reclamations'];
            $fournisseurClient = $infosCompte['fournisseur']; ?>
            <!-- Consommateur : factures, réclamations, fournisseur, puis ses compteurs -->
            <section class="carte">
                <div class="carte-entete">
                    <h2 class="titre-carte">Impayé</h2>
                    <a class="bouton contour petit" href="<?= link ?>declaration/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <p class="chiffre <?= $factures['montantImpaye'] > 0 ? 'alerte' : '' ?>"><?= nombreFr($factures['montantImpaye'], 2) ?><span class="unite">DH</span></p>
                <p class="legende"><strong><?= (int)$factures['impayees'] ?></strong> impayée<?= $factures['impayees'] > 1 ? 's' : '' ?> sur <?= (int)$factures['total'] ?> facture<?= $factures['total'] > 1 ? 's' : '' ?></p>
                <p class="legende">Dernière facture : <?= $jour($factures['derniere']) ?></p>
            </section>

            <section class="carte">
                <div class="carte-entete">
                    <h2 class="titre-carte">Réclamations</h2>
                    <a class="bouton contour petit" href="<?= link ?>reclamation/all">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <p class="chiffre"><?= (int)$reclamations['ouvertes'] ?></p>
                <p class="legende">en cours sur <?= (int)$reclamations['total'] ?> au total</p>
                <p class="legende">Dernière : <?= $jour($reclamations['derniere']) ?></p>
            </section>

            <section class="carte large">
                <h2 class="titre-carte">Mon fournisseur</h2>
                <?php if ($fournisseurClient) { ?>
                    <dl class="details" style="margin-top: 1rem">
                        <dt>Nom</dt><dd><?= $e($fournisseurClient['prenom'].' '.$fournisseurClient['nom']) ?></dd>
                        <dt>Email</dt><dd><a href="mailto:<?= $e($fournisseurClient['email']) ?>"><?= $e($fournisseurClient['email']) ?></a></dd>
                    </dl>
                    <p class="note" style="margin-top: 1rem">Pour corriger votre identité, votre téléphone ou une adresse, contactez-le.</p>
                <?php } ?>
            </section>

            <section class="carte" style="grid-column: 1 / -1">
                <h2 class="titre-carte" style="margin-bottom: 1rem">Mes compteurs</h2>
                <?php if ($infosCompte['compteurs']) { ?>
                    <table class="tableau">
                        <thead>
                            <tr><th>Adresse</th><th class="secondaire">Quartier</th><th>Contrat</th><th class="secondaire">Dernier relevé</th><th class="fin cache-mobile">Index eau</th><th class="fin cache-mobile">Index électricité</th></tr>
                        </thead>
                        <tbody data-pagine="5">
                            <?php foreach ($infosCompte['compteurs'] as $compteur) { ?>
                                <tr>
                                    <td><div class="tronque fort"><?= $e($compteur['adresse']) ?></div><small>Compteur N° <?= (int)$compteur['id'] ?></small></td>
                                    <td class="secondaire"><span class="tag"><?= $e($compteur['quartier'] ?? $compteur['categorie']) ?></span></td>
                                    <?php $etatContrat = $compteur['contrat'] === null ? array('Sans contrat', '') : ($compteur['contrat'] === 'actif' ? array('Actif', 'ok') : ($compteur['contrat'] === 'en résiliation' ? array('En résiliation', 'attente') : array('Résilié', ''))); ?>
                                    <td><span class="etat <?= $etatContrat[1] ?>"><?= $etatContrat[0] ?></span></td>
                                    <td class="secondaire nombre"><?= $jour($compteur['dateSaisie']) ?></td>
                                    <td class="fin nombre cache-mobile"><?= $compteur['indexEau'] === null ? '—' : nombreFr($compteur['indexEau'], 1).' m³' ?></td>
                                    <td class="fin nombre cache-mobile"><?= $compteur['indexElectricite'] === null ? '—' : nombreFr($compteur['indexElectricite']).' kWh' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="vide">Aucun compteur enregistré.</p>
                <?php } ?>
            </section>

        <?php } else if ($role === 'fournisseur') { ?>
            <!-- Fournisseur : les chiffres de son activité -->
            <section class="carte">
                <h2 class="titre-carte">Clients</h2>
                <p class="chiffre" style="margin-top: 0.75rem"><?= (int)$infosCompte['clients'] ?></p>
                <p class="legende"><?= (int)$infosCompte['compteurs'] ?> compteur<?= $infosCompte['compteurs'] > 1 ? 's' : '' ?></p>
            </section>
            <section class="carte">
                <h2 class="titre-carte">Contrats actifs</h2>
                <p class="chiffre" style="margin-top: 0.75rem"><?= (int)$infosCompte['contratsActifs'] ?></p>
                <p class="legende"><?= (int)$infosCompte['factures'] ?> facture<?= $infosCompte['factures'] > 1 ? 's' : '' ?> émise<?= $infosCompte['factures'] > 1 ? 's' : '' ?></p>
            </section>
            <section class="carte">
                <h2 class="titre-carte">Impayé</h2>
                <p class="chiffre <?= $infosCompte['montantImpaye'] > 0 ? 'alerte' : '' ?>" style="margin-top: 0.75rem"><?= nombreFr($infosCompte['montantImpaye']) ?><span class="unite">DH</span></p>
                <p class="legende">à encaisser</p>
            </section>
            <section class="carte">
                <h2 class="titre-carte">À traiter</h2>
                <p class="chiffre" style="margin-top: 0.75rem"><?= (int)$infosCompte['reclamationsOuvertes'] + (int)$infosCompte['anomalies'] ?></p>
                <p class="legende"><?= (int)$infosCompte['reclamationsOuvertes'] ?> réclamation<?= $infosCompte['reclamationsOuvertes'] > 1 ? 's' : '' ?> · <?= (int)$infosCompte['anomalies'] ?> anomalie<?= $infosCompte['anomalies'] > 1 ? 's' : '' ?></p>
            </section>

            <!-- Seuils d'anomalie : au-delà, la déclaration devient une anomalie au lieu d'une facture -->
            <section class="carte large">
                <h2 class="titre-carte">Seuils d'anomalie</h2>
                <p class="legende">Une déclaration qui dépasse ces consommations mensuelles devient une anomalie à corriger, sans facture.</p>
                <form class="formulaire" style="margin-top: 1rem" method="post" action="<?= link ?>utilisateur/modifierSeuils">
                    <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf_profil']) ?>">
                    <label class="champ"><span class="libelle"><i class="fa-solid fa-droplet" style="color: var(--eau)"></i> Eau (m³ par mois)</span>
                        <input type="text" name="seuilEau" inputmode="decimal" required value="<?= $e($_POST['seuilEau'] ?? number_format((float)$infosCompte['seuilEau'], 2, ',', '')) ?>">
                    </label>
                    <label class="champ"><span class="libelle"><i class="fa-solid fa-bolt" style="color: var(--elec)"></i> Électricité (kWh par mois)</span>
                        <input type="text" name="seuilElectricite" inputmode="decimal" required value="<?= $e($_POST['seuilElectricite'] ?? number_format((float)$infosCompte['seuilElectricite'], 2, ',', '')) ?>">
                    </label>
                    <div class="entier actions fin">
                        <button class="bouton plein" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer les seuils</button>
                    </div>
                </form>
            </section>

            <!-- Grille tarifaire du sujet, lue dans les règles de facturation (aucune saisie) -->
            <section class="carte large">
                <h2 class="titre-carte" style="margin-bottom: 0.25rem">Grille tarifaire</h2>
                <p class="legende" style="margin-bottom: 0.75rem">Chaque tranche est facturée à son prix, puis TVA de <?= (int)round(TVA_CONSOMMATION * 100) ?> %.</p>
                <table class="tableau">
                    <thead><tr><th>Électricité</th><th class="fin">DH / kWh</th><th>Eau</th><th class="fin">DH / m³</th></tr></thead>
                    <tbody>
                        <?php $libelleTranche = function ($t, $unite) { return $t[1] === null ? 'Au-delà de '.$t[0].' '.$unite : $t[0].' à '.$t[1].' '.$unite; };
                        for ($i = 0; $i < max(count(TRANCHES_ELECTRICITE), count(TRANCHES_EAU)); $i++) {
                            $elec = TRANCHES_ELECTRICITE[$i] ?? null; $eau = TRANCHES_EAU[$i] ?? null; ?>
                            <tr>
                                <td><?= $elec ? $libelleTranche($elec, 'kWh') : '' ?></td>
                                <td class="fin nombre"><?= $elec ? nombreFr($elec[2], 2) : '' ?></td>
                                <td><?= $eau ? $libelleTranche($eau, 'm³') : '' ?></td>
                                <td class="fin nombre"><?= $eau ? nombreFr($eau[2], 2) : '' ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>

        <?php } else { ?>
            <!-- Agent : ses importations annuelles -->
            <section class="carte" style="grid-column: 1 / -1">
                <div class="carte-entete">
                    <h2 class="titre-carte">Importations annuelles</h2>
                    <a class="bouton contour petit" href="<?= link ?>agent/tableauDeBord">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <?php if ($infosCompte) { ?>
                    <table class="tableau">
                        <thead><tr><th>Année</th><th>Clients relevés</th><th class="secondaire">Date du relevé</th><th class="fin">Eau</th><th class="fin">Électricité</th></tr></thead>
                        <tbody data-pagine="5">
                            <?php foreach ($infosCompte as $import) { ?>
                                <tr>
                                    <td class="fort nombre"><?= (int)$import['annee'] ?></td>
                                    <td class="nombre"><?= (int)$import['clients'] ?></td>
                                    <td class="secondaire nombre"><?= $jour($import['dateSaisie']) ?></td>
                                    <td class="fin nombre"><?= nombreFr($import['eau'], 1) ?> m³</td>
                                    <td class="fin nombre"><?= nombreFr($import['electricite']) ?> kWh</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="vide">Aucun fichier importé pour le moment.</p>
                <?php } ?>
            </section>
        <?php } ?>

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
