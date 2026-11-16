<?php
    include_once(ROOT."/BD/connexion.php");
    $link = connexion();
    function checkcredentials($email)
    {
        global $link;
        $sql = "SELECT * FROM utilisateur
                WHERE email='".$email."'";
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }

    function insertUser($email,$nom,$prenom,$password)
    {
        global $link;
        $sql = "INSERT INTO utilisateur (nom,prenom,email,pass)  VALUES(
        '".$nom."','".$prenom."','".$email."','".$password."'
        )";
        mysqli_query($link,$sql);
        return mysqli_insert_id($link);
    }
    function modifyUtilisateur($id,$nom,$prenom,$email)
        {
            global $link;
            $sql = "UPDATE utilisateur
                    SET nom='".$nom."',prenom='".$prenom."',email='".$email."'
                    WHERE id=".$id;
            return mysqli_query($link,$sql);
        }

    // ----- Page "Mon compte" -----

    // Lire un utilisateur par son identifiant (avec son mot de passe haché, pour le vérifier)
    function selectUtilisateur($id)
        {
            global $link;
            $q = mysqli_prepare($link, "SELECT * FROM utilisateur WHERE id=?");
            mysqli_stmt_bind_param($q, 'i', $id);
            mysqli_stmt_execute($q);
            return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
        }

    // Vrai si un AUTRE utilisateur a déjà cet email
    function emailDejaUtilise($email,$id)
        {
            global $link;
            $q = mysqli_prepare($link, "SELECT id FROM utilisateur WHERE email=? AND id<>?");
            mysqli_stmt_bind_param($q, 'si', $email, $id);
            mysqli_stmt_execute($q);
            return mysqli_fetch_assoc(mysqli_stmt_get_result($q)) !== null;
        }

    function modifierEmailUtilisateur($id,$email)
        {
            global $link;
            $q = mysqli_prepare($link, "UPDATE utilisateur SET email=? WHERE id=?");
            mysqli_stmt_bind_param($q, 'si', $email, $id);
            return mysqli_stmt_execute($q);
        }

    function modifierMotDePasseUtilisateur($id,$motDePasseHache)
        {
            global $link;
            $q = mysqli_prepare($link, "UPDATE utilisateur SET pass=? WHERE id=?");
            mysqli_stmt_bind_param($q, 'si', $motDePasseHache, $id);
            return mysqli_stmt_execute($q);
        }
?>
