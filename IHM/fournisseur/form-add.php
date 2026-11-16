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
        <p class="sous-titre">Son mot de passe lui est envoyé par e-mail.</p>
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

            <!-- Une ou plusieurs adresses, chacune avec ses compteurs (seulement pour un consommateur) -->
            <div class="client-field entier pile" id="adresses">
                <div class="adresse-compteur bloc">
                    <p class="bloc-titre">Adresse et compteurs</p>
                    <div class="formulaire">
                        <label class="champ entier"><span class="libelle">Adresse</span>
                            <input type="text" name="adresse[]" required>
                        </label>
                        <label class="champ entier"><span class="libelle">Catégorie</span>
                            <select name="categorie[]">
                                <option value="Résidentiel économique">Résidentiel économique</option>
                                <option value="Résidentiel standard">Résidentiel standard</option>
                                <option value="Résidentiel haut standing">Résidentiel haut standing</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Industriel">Industriel</option>
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
                <span class="note">Chaque adresse commence avec ses propres index de référence.</span>
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
