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
    // Les clients d'UN fournisseur (chaque fournisseur ne voit que les siens)
    function selectAll($idFournisseur)
    {
        global $link;
        $sql = "SELECT id,nom,prenom,adresse FROM consommateur NATURAL JOIN utilisateur
                WHERE idFournisseur=".(int)$idFournisseur;
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
        mysqli_query($link,$sql);
        // Renvoie l'identifiant de la nouvelle adresse : le contrat de ce compteur y est rattaché.
        return $idAdresseCompteur;
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

        // ----- Liste des clients : informations en plus, en lecture seule -----

        // Pour chaque client : email, CIN, téléphone, compteurs, contrats actifs, montant impayé.
        // Renvoie un tableau indexé par l'identifiant du client.
        function resumeClients()
        {
            global $link;
            $sql = "SELECT c.id, u.email, c.cin, c.telephone,
                    (SELECT COUNT(*) FROM adresseCompteur a WHERE a.idConsommateur=c.id) AS compteurs,
                    (SELECT COUNT(*) FROM contrat k WHERE k.idConsommateur=c.id AND k.statut='actif') AS contratsActifs,
                    (SELECT COALESCE(SUM(d.prixTTC),0) FROM facture f JOIN declaration d ON d.id=f.idDeclaration
                        WHERE d.idConsommateur=c.id AND f.etat='Impayée') AS impaye,
                    (SELECT COUNT(*) FROM facture f JOIN declaration d ON d.id=f.idDeclaration
                        WHERE d.idConsommateur=c.id AND f.etat='Impayée') AS facturesImpayees
                    FROM consommateur c JOIN utilisateur u ON u.id=c.id";
            $resume = array();
            foreach (mysqli_fetch_all(mysqli_query($link,$sql), MYSQLI_ASSOC) as $ligne) {
                $resume[(int)$ligne['id']] = $ligne;
            }
            return $resume;
        }

        // Consommation de chaque client, mois par mois (relevés validés, tous compteurs additionnés).
        // Les index sont cumulés : la consommation d'un relevé = index - index précédent du même compteur.
        // Renvoie [idClient => [ 'AAAA-MM' => ['eau' => m³, 'electricite' => kWh, 'date' => dernier relevé] ]], les 6 derniers mois.
        function consommationRecenteClients()
        {
            global $link;
            $sql = "SELECT x.idConsommateur, DATE_FORMAT(x.dateSaisie,'%Y-%m') AS mois, MAX(x.dateSaisie) AS date,
                           SUM(x.eau) AS eau, SUM(x.electricite) AS electricite
                    FROM (
                        SELECT d.idConsommateur, d.dateSaisie, d.statut,
                               d.indexEau - LAG(d.indexEau) OVER (PARTITION BY d.idAdresseCompteur ORDER BY d.dateSaisie, d.id) AS eau,
                               d.indexElectricite - LAG(d.indexElectricite) OVER (PARTITION BY d.idAdresseCompteur ORDER BY d.dateSaisie, d.id) AS electricite
                        FROM declaration d
                        WHERE d.statut IN ('Référence initiale','Validée')
                    ) x
                    WHERE x.statut='Validée' AND x.eau IS NOT NULL
                    GROUP BY x.idConsommateur, mois
                    ORDER BY x.idConsommateur, mois";
            $series = array();
            foreach (mysqli_fetch_all(mysqli_query($link,$sql), MYSQLI_ASSOC) as $ligne) {
                $series[(int)$ligne['idConsommateur']][$ligne['mois']] = array(
                    'eau' => (float)$ligne['eau'], 'electricite' => (float)$ligne['electricite'], 'date' => $ligne['date']);
            }
            foreach ($series as $id => $mois) {
                $series[$id] = array_slice($mois, -6, null, true);
            }
            return $series;
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
                "DELETE FROM notification WHERE idUtilisateur=".$id,
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
