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
        .centering { max-height: calc(100vh - 200px); overflow-y: auto; padding-right: 12px; }
        .adresse-compteur { border-top: 1px solid #d0d0d0; margin-top: 20px; padding-top: 16px; }
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
                        <a class="section-txt" href="../all" ><i class="fa-solid fa-user-group"></i> Consulter Vos Clients
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
                <form action="../modifier/<?=$result["id"]?>" method="post">
                    <div class="form-div">
                        <p>Contrat <?= $contrat["statut"] ?? "resilié"?></p>
                        <div class="champ"><h3>Nom: </h3><input type="text" name="nom" value="<?=$result["nom"]?>"></div>
                        <div class="champ"><h3>Prenom: </h3><input type="text" name="prenom" value="<?=$result["prenom"]?>"></div>
                        <div class="champ"><h3>Adresse: </h3><input type="text" name="adresse" value="<?=$result["adresse"]?>"></div>
                        <div class="champ"><h3>Email: </h3><input type="email" name="email" value="<?=$result["email"]?>"></div>
                        <div class="align-btn">
                        <div class="champ center-btn"> <button type="submit" class="modify-btn"><i class="fa-solid fa-floppy-disk"></i>Enregistrer</button></div>
                        <?php if ($contrat): ?><div class="champ center-btn"> <a href="../../contrat/resilier/<?=$contrat[0]?>"><div class="cancel-btn"><i class="fa-solid fa-xmark"></i>Resilier contrat</div></a></div><?php endif; ?>
                        </div>
                    </div>
                </form>
                <div class="adresse-compteur">
                    <h3>Adresses et compteurs</h3>
                    <?php foreach ($adressesCompteur as $point): ?>
                        <p><?=htmlspecialchars($point['adresse'].' — '.$point['categorie'], ENT_QUOTES, 'UTF-8')?></p>
                    <?php endforeach; ?>
                    <form action="<?=link?>consommateur/ajouterAdresse/<?=$result['id']?>" method="post">
                        <div class="form-div">
                            <div class="champ"><h3>Nouvelle adresse:</h3><input type="text" name="adresse" required></div>
                            <div class="champ"><h3>Catégorie:</h3><select name="categorie">
                                <option value="Résidentiel économique">Résidentiel économique</option>
                                <option value="Résidentiel standard">Résidentiel standard</option>
                                <option value="Résidentiel haut standing">Résidentiel haut standing</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Industriel">Industriel</option>
                            </select></div>
                            <div class="champ"><h3>Index initial eau:</h3><input type="text" name="indexPrecendentEau" inputmode="decimal" required></div>
                            <div class="champ"><h3>Index initial électricité:</h3><input type="text" name="indexPrecendentElec" inputmode="decimal" required></div>
                            <div class="champ center-btn"><button class="modify-btn" type="submit">Ajouter cette adresse</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
