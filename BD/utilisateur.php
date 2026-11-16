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
?>
