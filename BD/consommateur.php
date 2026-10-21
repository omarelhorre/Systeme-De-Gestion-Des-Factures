<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();
    function checkConsommateur($ID)
    {
        global $link;
        $sql = "SELECT * FROM consommateur
                WHERE id='".$ID."'";
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }
    function selectAll()
    {
        global $link;
        $sql = "SELECT id,nom,prenom,adresse FROM consommateur NATURAL JOIN utilisateur";
        return mysqli_query($link,$sql);
    }
    function insertConsommateur($lastUser,$idFournisseur,$adresse) {
        global $link;
        $stmt = mysqli_prepare($link, 'INSERT INTO consommateur(id,idFournisseur,adresse) VALUES(?,?,?)');
        mysqli_stmt_bind_param($stmt, 'iis', $lastUser, $idFournisseur, $adresse);
        return mysqli_stmt_execute($stmt);
    }
        function selectConsommateur($id)
        {
            global $link;
            $sql = "SELECT * FROM consommateur NATURAL JOIN utilisateur
                    WHERE id=".$id;

            return mysqli_fetch_array(mysqli_query($link,$sql));
        }
       function modifyConsommateur($id,$adresse)
        {
            global $link;
            $sql = "UPDATE consommateur
                    SET adresse='".$adresse."'
                    WHERE id=".$id;
            return mysqli_query($link,$sql);
        }
?>