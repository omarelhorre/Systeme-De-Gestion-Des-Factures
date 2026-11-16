<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();
    function selectContractByConsommateur($id)
    {
        global $link;
        $sql = "SELECT * FROM contrat JOIN consommateur ON 
        contrat.idConsommateur = consommateur.id WHERE statut = 'actif'
        AND contrat.idConsommateur = ".(int)$id;
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }
    // Un contrat par adresse de compteur : $idAdresseCompteur relie le contrat à son compteur.
    function ajouterContrat($id,$cheminGeneration,$idAdresseCompteur=null)
    {
        global $link;
        $sql = "INSERT INTO contrat(cheminGeneration,idConsommateur,idAdresseCompteur,statut)
        VALUES('".$cheminGeneration."',".(int)$id.",".($idAdresseCompteur === null ? "NULL" : (int)$idAdresseCompteur).",'actif')";
        return mysqli_query($link,$sql);
    }

    // Tous les contrats d'un client, avec l'adresse du compteur de chacun
    function contratsDuConsommateur($id)
    {
        global $link;
        $sql = "SELECT c.id, c.dateDebut, c.statut, c.idAdresseCompteur, a.adresse, a.categorie
                FROM contrat c LEFT JOIN adresseCompteur a ON a.id = c.idAdresseCompteur
                WHERE c.idConsommateur = ".(int)$id." ORDER BY c.id";
        return mysqli_fetch_all(mysqli_query($link,$sql), MYSQLI_ASSOC);
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