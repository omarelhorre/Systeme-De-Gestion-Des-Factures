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
    function insertConsommateur($lastUser,$idFournisseur,$adresse)
    {
        global $link;
        $sql = "INSERT INTO consommateur(id,idFournisseur,adresse)
                VALUES ('".$lastUser."','".$idFournisseur."','".$adresse."')";
        mysqli_query($link,$sql);
    }
    function insertAdresseCompteur($idConsommateur,$idFournisseur,$adresse,$categorie,$idxEau,$idxElec)
    {
        global $link;
        $sql = "INSERT INTO adresseCompteur(idConsommateur,adresse,categorie)
                VALUES ('".$idConsommateur."','".$adresse."','".$categorie."')";
        mysqli_query($link,$sql);
        $idAdresseCompteur = mysqli_insert_id($link);
        $sql = "INSERT INTO declaration(idConsommateur,idFournisseur,idAdresseCompteur,indexEau,indexElectricite,statut)
                VALUES (".$idConsommateur.",".$idFournisseur.",".$idAdresseCompteur.",".$idxEau.",".$idxElec.",'Référence initiale')";
        return mysqli_query($link,$sql);
    }

        function selectConsommateur($id)
        {
            global $link;
            $sql = "SELECT * FROM consommateur NATURAL JOIN utilisateur
                    WHERE id=".$id;

            return mysqli_fetch_array(mysqli_query($link,$sql));
        }
       function adressesCompteurDuClient($id)
       {
           global $link;
           $sql = "SELECT * FROM adresseCompteur WHERE idConsommateur=".$id." ORDER BY id";
           return mysqli_query($link,$sql);
       }
       function modifyConsommateur($id,$adresse)
        {
            global $link;
            $sql = "UPDATE consommateur
                    SET adresse='".$adresse."'
                    WHERE id=".$id;
            mysqli_query($link,$sql);
            $sql = "UPDATE adresseCompteur SET adresse='".$adresse."'
                    WHERE idConsommateur=".$id." ORDER BY id LIMIT 1";
            return mysqli_query($link,$sql);
        }
?>
