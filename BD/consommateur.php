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
    function insertConsommateur($lastUser,$idFournisseur,$adresse,$cin='',$telephone='')
    {
        global $link;
        $sql = "INSERT INTO consommateur(id,idFournisseur,adresse,cin,telephone)
                VALUES ('".$lastUser."','".$idFournisseur."','".$adresse."','".mysqli_real_escape_string($link,$cin)."','".mysqli_real_escape_string($link,$telephone)."')";
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
       function modifyConsommateur($id,$adresse,$cin='',$telephone='')
        {
            global $link;
            $sql = "UPDATE consommateur
                    SET adresse='".$adresse."',
                        cin='".mysqli_real_escape_string($link,$cin)."',
                        telephone='".mysqli_real_escape_string($link,$telephone)."'
                    WHERE id=".$id;
            mysqli_query($link,$sql);
            $sql = "UPDATE adresseCompteur SET adresse='".$adresse."'
                    WHERE idConsommateur=".$id." ORDER BY id LIMIT 1";
            return mysqli_query($link,$sql);
        }

        // Compter ce que le client a déjà : relevés (hors référence initiale), factures, réclamations.
        function historiqueConsommateur($id)
        {
            global $link;
            $id = (int)$id;
            $sql = "SELECT
                    (SELECT COUNT(*) FROM declaration WHERE idConsommateur=".$id." AND statut<>'Référence initiale') AS releves,
                    (SELECT COUNT(*) FROM facture f JOIN declaration d ON d.id=f.idDeclaration WHERE d.idConsommateur=".$id.") AS factures,
                    (SELECT COUNT(*) FROM reclamation WHERE id=".$id.") AS reclamations";
            return mysqli_fetch_assoc(mysqli_query($link,$sql));
        }

        // Supprimer un client et tout ce qui lui est lié, dans une seule transaction.
        // L'ordre suit les clés étrangères : on supprime d'abord ce qui dépend du reste.
        function supprimerConsommateur($id)
        {
            global $link;
            $id = (int)$id;
            $reclamations = "SELECT idReclamation FROM reclamation WHERE id=".$id;
            $declarations = "SELECT id FROM declaration WHERE idConsommateur=".$id;
            $factures = "SELECT f.id FROM facture f JOIN declaration d ON d.id=f.idDeclaration WHERE d.idConsommateur=".$id;
            $annuelles = "SELECT id FROM ConsommationAnuelle WHERE idConsommateur=".$id;
            $requetes = array(
                // réclamations et leurs sous-types
                "DELETE FROM reclamationFacture WHERE idReclamation IN (".$reclamations.") OR id IN (SELECT id FROM (".$factures.") AS f)",
                "DELETE FROM fuiteInterne WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM fuiteExterne WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM reclamationFuite WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM autre WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM reclamation WHERE id=".$id,
                // factures puis relevés
                "DELETE FROM facture WHERE idDeclaration IN (".$declarations.")",
                "DELETE FROM declaration WHERE idConsommateur=".$id,
                // bilan annuel
                "DELETE FROM regularisationAnnuelle WHERE idConsommationAnuelle IN (".$annuelles.")",
                "DELETE FROM ConsommationAnuelle WHERE idConsommateur=".$id,
                // contrat, adresses, puis le client et son compte utilisateur
                "DELETE FROM contrat WHERE idConsommateur=".$id,
                "DELETE FROM adresseCompteur WHERE idConsommateur=".$id,
                "DELETE FROM consommateur WHERE id=".$id,
                "DELETE FROM utilisateur WHERE id=".$id." AND id NOT IN (SELECT id FROM agent) AND id NOT IN (SELECT id FROM fournisseur)",
            );
            mysqli_begin_transaction($link);
            try {
                foreach ($requetes as $sql) {
                    mysqli_query($link,$sql);
                }
                mysqli_commit($link);
            } catch (Throwable $e) {
                mysqli_rollback($link);
                throw $e;
            }
        }
?>
