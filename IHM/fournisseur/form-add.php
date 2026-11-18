<?php
// Formulaire d'ajout d'un client (ou d'un agent). Les noms des champs sont ceux attendus par add().
$coqueTitre = 'Ajouter un client';
$coqueActif = 'clients';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php include ROOT.'IHM/commun/tete.php'; ?>
</head>
<body>
<?php include ROOT.'IHM/fournisseur/menu.php'; ?>

<main class="contenu entree">
    <a class="lien-retour" href="<?= link ?>consommateur/all"><i class="fa-solid fa-arrow-left"></i> Retour aux clients</a>
    <header class="entete">
        <h1 class="titre">Ajouter un client</h1>
    </header>

    <section class="carte">
        <form class="formulaire" action="<?= link ?>consommateur/ajouter" method="post">
            <label class="champ"><span class="libelle">Nom</span>
                <input type="text" name="nom" required>
            </label>
            <label class="champ"><span class="libelle">Prénom</span>
                <input type="text" name="prenom" required>
            </label>
            <label class="champ"><span class="libelle">Email</span>
                <input type="email" name="email" placeholder="exemple@gmail.com" required>
            </label>
            <label class="champ"><span class="libelle">Type de compte</span>
                <select name="role" id="role" onchange="changerRole()">
                    <option value="consommateur">Consommateur</option>
                    <option value="agent">Agent</option>
                </select>
            </label>
            <label class="champ client-field"><span class="libelle">Numéro d'identité</span>
                <input type="text" name="cin" placeholder="BL175578" maxlength="10" pattern="[A-Za-z]{1,2}[0-9]{4,8}" required>
            </label>
            <label class="champ client-field"><span class="libelle">Téléphone</span>
                <input type="tel" name="telephone" placeholder="0612345678" maxlength="16" pattern="(\+212|0)[5-7][0-9 ]{8,12}" required>
            </label>

            <!-- Une ou plusieurs adresses, chacune avec ses compteurs (seulement pour un consommateur) -->
            <div class="client-field entier pile" id="adresses">
                <div class="adresse-compteur bloc">
                    <p class="bloc-titre">Adresse et compteurs</p>
                    <div class="formulaire">
                        <label class="champ entier"><span class="libelle">Adresse</span>
                            <input type="text" name="adresse[]" required>
                        </label>
                        <label class="champ entier"><span class="libelle">Catégorie et quartier</span>
                            <select name="quartier[]" required>
                                <option value="" disabled selected>Choisir un quartier</option>
                                <?php foreach (quartiersParCategorie() as $categorie => $liste): ?>
                                <optgroup label="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php foreach ($liste as $q): ?><option value="<?= (int)$q['id'] ?>"><?= htmlspecialchars($q['nom'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="champ"><span class="libelle">Index initial eau (m³)</span>
                            <input type="text" name="indexPrecendentEau[]" inputmode="decimal" required>
                        </label>
                        <label class="champ"><span class="libelle">Index initial électricité (kWh)</span>
                            <input type="text" name="indexPrecendentElec[]" inputmode="decimal" required>
                        </label>
                    </div>
                </div>
            </div>

            <div class="client-field entier actions">
                <button class="bouton contour petit" type="button" onclick="ajouterAdresse()"><i class="fa-solid fa-plus"></i> Ajouter une autre adresse</button>
               
            </div>

            <div class="entier actions fin">
                <button class="bouton accent" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
            </div>
        </form>
    </section>
</main>

<script>
    // Copie le premier bloc d'adresse, vidé, à la fin de la liste
    function ajouterAdresse() {
        const modele = document.querySelector('.adresse-compteur');
        const copie = modele.cloneNode(true);
        copie.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        document.getElementById('adresses').appendChild(copie);
    }
    // Un agent n'a pas d'adresse ni de compteur : on cache ces champs
    function changerRole() {
        const client = document.getElementById('role').value === 'consommateur';
        document.querySelectorAll('.client-field').forEach(function (field) {
            field.style.display = client ? '' : 'none';
            field.querySelectorAll('input').forEach(function (input) { input.required = client; });
        });
    }
    changerRole();
</script>
</body>
</html>
