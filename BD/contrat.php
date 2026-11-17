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

    // ----- Résiliation en deux temps -----
    // 1. Le fournisseur lance la résiliation : le contrat passe "en résiliation" (il n'est pas encore fermé).
    // 2. Le client déclare son relevé final : sa facture finale est créée, puis le contrat passe "resilié".
    const CONTRAT_EN_RESILIATION = 'en résiliation';

    function demarrerResiliation($id)
    {
        global $link;
        $q = mysqli_prepare($link, "UPDATE contrat SET statut=? WHERE id=? AND statut='actif'");
        $statut = CONTRAT_EN_RESILIATION;
        mysqli_stmt_bind_param($q, 'si', $statut, $id);
        mysqli_stmt_execute($q);
        return mysqli_stmt_affected_rows($q) === 1;
    }

    // État du contrat d'un compteur : 'actif', 'en résiliation', 'resilié', ou null s'il n'a pas de contrat.
    // Si le compteur a plusieurs contrats, le plus "vivant" compte (actif, puis en résiliation, puis le dernier).
    function statutContratAdresse($idAdresseCompteur)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT statut FROM contrat WHERE idAdresseCompteur=?
                                    ORDER BY statut='actif' DESC, statut=? DESC, id DESC LIMIT 1");
        $enResiliation = CONTRAT_EN_RESILIATION;
        mysqli_stmt_bind_param($q, 'is', $idAdresseCompteur, $enResiliation);
        mysqli_stmt_execute($q);
        $ligne = mysqli_fetch_row(mysqli_stmt_get_result($q));
        return $ligne ? $ligne[0] : null;
    }

    // Ferme le contrat "en résiliation" d'un compteur (après son relevé final). Renvoie l'id du contrat fermé, ou null.
    function cloturerContratAdresse($idAdresseCompteur)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT id FROM contrat WHERE idAdresseCompteur=? AND statut=? LIMIT 1");
        $enResiliation = CONTRAT_EN_RESILIATION;
        mysqli_stmt_bind_param($q, 'is', $idAdresseCompteur, $enResiliation);
        mysqli_stmt_execute($q);
        $ligne = mysqli_fetch_row(mysqli_stmt_get_result($q));
        if (!$ligne) return null;
        resilier_BDD((int)$ligne[0]);
        return (int)$ligne[0];
    }
?>