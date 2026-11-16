<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Se Connecter</title>
    <link href=<?=dir."/public/style/dashboardF.css"?> rel="stylesheet"/>
    <script src=<?= dir."/public/script/dashboardF.js"?>></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/eab6c3ad6c.js" crossorigin="anonymous"></script>
    <style>
        .form-div { max-height: calc(100vh - 200px); overflow-y: auto; padding-right: 12px; }
        .adresse-compteur { display: grid; gap: 16px; border: 1px solid #d0d0d0; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .adresse-compteur select { padding: 8px 15px; font-size: 16px; border: 2px solid #d0d0d0; border-radius: 20px; }
    </style>

</head>
<body>
        <nav class="menu">
        <ul class="menu-list">
            <li>
                <span class="title">Fatourti<span>
            </li>
            <li>
                <div class="grid"> 
                    <div class="border">
                        <a class="section-txt" href="../fournisseur/tableauDeBord"><i class="fa-solid fa-user-group"></i> Consulter le tableau de bord
                        </a>
                    </div>
                    <div class="border selected">
                        <a class="section-txt" href="../consommateur/all" ><i class="fa-solid fa-user-group"></i> Consulter Vos Clients
                        </a>
                        
                    </div>
                    <div class="border">
                        <a class="section-txt" href="<?=link?>facture/all"><i class="fa-solid fa-list"></i>Consulter les factures</a>
                    </div >
                    <div class="border">
                        <a class="section-txt" href="<?=link?>declaration/anomalies"><i class="fa-solid fa-triangle-exclamation"></i> Corriger les anomalies</a>
                    </div>
                    <div class="border">
                        <a class="section-txt" href="<?=link?>annuelle/all"><i class="fa-solid fa-check"></i>Verifier Consommation Anuelle</a>
                    </div>
                    <div class="border">
                        <a class="section-txt" href=""><i class="fa-solid fa-exclamation"></i>Consulter La liste des reclamations</a>
                    </div>
                </div>
            </li>
            <li>
                <div class="connexion-section">
                    <div class="login-creds">
                    <p class="identity"><?= $_SESSION["user"]["nom"]." ".$_SESSION["user"]["prenom"]?></p><p class="role-style">Fournisseur</p>
                    </div>
                    <a class="element" href=<?=link."utilisateur/deconnexion"?>>
                    <button class="connexion-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i>Déconnexion</button>
                    </a>
                </div>
            </li>
        </ul>
        </nav>
    <main class="main">
        <div class="container">
            <div class="centering">
                    <form action="./ajouter" method="post">
                        <div class="form-div">
                            <div class="champ"><h3>Nom:</h3> <input type="text" name="nom" class="input-champ" required></div>
                            <div class="champ"><h3>Prenom:</h3> <input type="text" name="prenom" class="input-champ" required></div>
                            <div class="champ "><h3>Email:</h3> <input type="email" name="email" class="input-champ" placeholder="exemple@gmail.com" required></div>
                            <div class="champ"><h3>Type de compte:</h3>
                                <select name="role" id="role" class="input-champ" onchange="changerRole()">
                                    <option value="consommateur">Consommateur</option>
                                    <option value="agent">Agent</option>
                                </select>
                            </div>
                            <div class="client-field" id="adresses">
                                <div class="adresse-compteur">
                                    <h3>Adresse et compteurs</h3>
                                    <div class="champ"><h3>Adresse:</h3> <input type="text" name="adresse[]" class="input-champ" required></div>
                                    <div class="champ"><h3>Catégorie:</h3><select name="categorie[]" class="input-champ">
                                        <option value="Résidentiel économique">Résidentiel économique</option>
                                        <option value="Résidentiel standard">Résidentiel standard</option>
                                        <option value="Résidentiel haut standing">Résidentiel haut standing</option>
                                        <option value="Commercial">Commercial</option>
                                        <option value="Industriel">Industriel</option>
                                    </select></div>
                                    <div class="champ"><h3>Index initial eau:</h3> <input type="text" name="indexPrecendentEau[]" class="input-champ" inputmode="decimal" required></div>
                                    <div class="champ"><h3>Index initial électricité:</h3> <input type="text" name="indexPrecendentElec[]" class="input-champ" inputmode="decimal" required></div>
                                </div>
                            </div>
                            <button class="client-field" type="button" onclick="ajouterAdresse()">Ajouter une autre adresse</button>
                            <p class="client-field">Chaque adresse commence avec ses propres index de référence.</p>
                            <div class="champ center-btn"><button type="submit" class="modify-btn"><i class="fa-solid fa-floppy-disk"></i>Enregistrer</button></div>
                        </div>
                    </form>
            </div>
        </div>
    </main>
    <script>
        function ajouterAdresse() {
            const modele = document.querySelector('.adresse-compteur');
            const copie = modele.cloneNode(true);
            copie.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            document.getElementById('adresses').appendChild(copie);
        }
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
