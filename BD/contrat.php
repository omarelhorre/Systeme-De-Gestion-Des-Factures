<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();
    function selectContractByConsommateur($id)
    {
        global $link;
        $sql = "SELECT * FROM contrat JOIN consommateur ON 
        contrat.idConsommateur = consommateur.id WHERE statut = 'actif' ";
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }
    function ajouterContrat($id,$cheminGeneration)
    {
        global $link;
        $sql = "INSERT INTO contrat(cheminGeneration,idConsommateur,statut)
        VALUES('".$cheminGeneration."',".$id.",'actif')";
        return mysqli_query($link,$sql);
    }

    function resilier_BDD($id)
    {
        global $link;
        $sql= "UPDATE contrat
                SET statut ='resilié' 
                WHERE contrat.id = ".$id;
                return mysqli_query($link,$sql);
    }
?>