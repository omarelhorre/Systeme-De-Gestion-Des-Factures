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
                        <a class="section-txt" href=""><i class="fa-solid fa-list"></i>Consulter Les Declaration</a>
                    </div >
                    <div class="border">
                        <a class="section-txt" href=""><i class="fa-solid fa-check"></i>Verifier Consommation Anuelle</a>
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
                    <a class="element" href=<?=dir."../utilisateur/deconnexion"?>>
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
                            <div class="champ"><h3>Nom:</h3> <input type="text" name="nom" class="input-champ"></div>
                            <div class="champ"><h3>Prenom:</h3> <input type="text" name="prenom" class="input-champ"></div>
                            <div class="champ"><h3>Adresse:</h3> <input type="text" name="adresse" class="input-champ" placeholder=""></div>
                        <div class="champ "><h3>Email:</h3> <input type="email" name="email" class="input-champ" placeholder="exemple@gmail.com"></div>
                            <div class="champ center-btn"><button type="submit" class="modify-btn"><i class="fa-solid fa-floppy-disk"></i>Enregistrer</button></div>
                        </div>
                    </form>
            </div>
        </div>
    </main>
</body>
</html>