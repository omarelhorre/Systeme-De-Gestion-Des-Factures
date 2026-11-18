<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();
    function checkConsommateur($ID)
    {
        global $link;
        $sql = "SELECT * FROM consommateur
                WHERE id=".(int)$ID;
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }
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
                VALUES (".(int)$lastUser.",".(int)$idFournisseur.",'".mysqli_real_escape_string($link,$adresse)."','".mysqli_real_escape_string($link,$cin)."','".mysqli_real_escape_string($link,$telephone)."')";
        mysqli_query($link,$sql);
    }
    function quartiers()
    {
        global $link;
        return mysqli_fetch_all(mysqli_query($link, "SELECT id, nom, typeActivite FROM quartier ORDER BY typeActivite, nom"), MYSQLI_ASSOC);
    }
    function quartiersParCategorie()
    {
        $groupes = array();
        foreach (quartiers() as $q) $groupes[$q['typeActivite']][] = $q;
        return $groupes;
    }
    function quartierParId($id)
    {
        global $link;
        return mysqli_fetch_assoc(mysqli_query($link, "SELECT id, nom, typeActivite FROM quartier WHERE id=".(int)$id));
    }

    function insertAdresseCompteur($idConsommateur,$idFournisseur,$adresse,$categorie,$idxEau,$idxElec,$idQuartier=null)
    {
        global $link;
        $sql = "INSERT INTO adresseCompteur(idConsommateur,adresse,categorie,idQuartier)
                VALUES (".(int)$idConsommateur.",'".mysqli_real_escape_string($link,$adresse)."','".mysqli_real_escape_string($link,$categorie)."',".($idQuartier === null ? "NULL" : (int)$idQuartier).")";
        mysqli_query($link,$sql);
        $idAdresseCompteur = mysqli_insert_id($link);
        $sql = "INSERT INTO declaration(idConsommateur,idFournisseur,idAdresseCompteur,indexEau,indexElectricite,statut)
                VALUES (".(int)$idConsommateur.",".(int)$idFournisseur.",".(int)$idAdresseCompteur.",".(float)$idxEau.",".(float)$idxElec.",'Référence initiale')";
        mysqli_query($link,$sql);
        return $idAdresseCompteur;
    }

        function selectConsommateur($id)
        {
            global $link;
            $sql = "SELECT * FROM consommateur NATURAL JOIN utilisateur
                    WHERE id=".(int)$id;

            return mysqli_fetch_array(mysqli_query($link,$sql));
        }
       function adressesCompteurDuClient($id)
       {
           global $link;
           $sql = "SELECT a.*, q.nom AS quartier FROM adresseCompteur a LEFT JOIN quartier q ON q.id = a.idQuartier
                   WHERE a.idConsommateur=".(int)$id." ORDER BY a.id";
           return mysqli_query($link,$sql);
       }
       function modifyConsommateur($id,$adresse,$cin='',$telephone='')
        {
            global $link;
            $sql = "UPDATE consommateur
                    SET adresse='".mysqli_real_escape_string($link,$adresse)."',
                        cin='".mysqli_real_escape_string($link,$cin)."',
                        telephone='".mysqli_real_escape_string($link,$telephone)."'
                    WHERE id=".(int)$id;
            mysqli_query($link,$sql);
            $sql = "UPDATE adresseCompteur SET adresse='".mysqli_real_escape_string($link,$adresse)."'
                    WHERE idConsommateur=".(int)$id." ORDER BY id LIMIT 1";
            return mysqli_query($link,$sql);
        }

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

        function supprimerConsommateur($id)
        {
            global $link;
            $id = (int)$id;
            $reclamations = "SELECT idReclamation FROM reclamation WHERE id=".$id;
            $declarations = "SELECT id FROM declaration WHERE idConsommateur=".$id;
            $factures = "SELECT f.id FROM facture f JOIN declaration d ON d.id=f.idDeclaration WHERE d.idConsommateur=".$id;
            $annuelles = "SELECT id FROM ConsommationAnuelle WHERE idConsommateur=".$id;
            $requetes = array(
                "DELETE FROM reclamationFacture WHERE idReclamation IN (".$reclamations.") OR id IN (SELECT id FROM (".$factures.") AS f)",
                "DELETE FROM fuiteInterne WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM fuiteExterne WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM reclamationFuite WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM autre WHERE idReclamation IN (".$reclamations.")",
                "DELETE FROM reclamation WHERE id=".$id,
                "DELETE FROM facture WHERE idDeclaration IN (".$declarations.")",
                "DELETE FROM declaration WHERE idConsommateur=".$id,
                "DELETE FROM regularisationAnnuelle WHERE idConsommationAnuelle IN (".$annuelles.")",
                "DELETE FROM ConsommationAnuelle WHERE idConsommateur=".$id,
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
