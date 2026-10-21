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

    function insertUser($email,$nom,$prenom,$password) {
        global $link;
        $stmt = mysqli_prepare($link, 'INSERT INTO utilisateur(nom,prenom,email,pass) VALUES(?,?,?,?)');
        mysqli_stmt_bind_param($stmt, 'ssss', $nom, $prenom, $email, $password);
        mysqli_stmt_execute($stmt);
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