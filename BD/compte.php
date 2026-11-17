<?php
    // Page "Mon compte" : informations à afficher selon le rôle. Lecture seule.
    include_once(ROOT."BD/connexion.php");
    $link = connexion();

    function compteLignes($sql, $types, ...$valeurs)
    {
        global $link;
        $q = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($q, $types, ...$valeurs);
        mysqli_stmt_execute($q);
        return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    }

    // Consommateur : ses compteurs (avec contrat et dernier relevé), ses factures, ses réclamations, son fournisseur
    function compteConsommateur($id)
    {
        $compteurs = compteLignes(
            "SELECT a.id, a.adresse, a.categorie,
                    (SELECT k.statut FROM contrat k WHERE k.idAdresseCompteur=a.id ORDER BY k.statut='actif' DESC, k.id DESC LIMIT 1) AS contrat,
                    d.dateSaisie, d.indexEau, d.indexElectricite
             FROM adresseCompteur a
             LEFT JOIN declaration d ON d.id = (SELECT d2.id FROM declaration d2 WHERE d2.idAdresseCompteur=a.id
                                                AND d2.statut IN ('Référence initiale','Validée') ORDER BY d2.dateSaisie DESC, d2.id DESC LIMIT 1)
             WHERE a.idConsommateur=? ORDER BY a.id", 'i', $id);
        $factures = compteLignes(
            "SELECT COUNT(*) AS total,
                    SUM(f.etat='Impayée') AS impayees,
                    COALESCE(SUM(CASE WHEN f.etat='Impayée' THEN d.prixTTC END),0) AS montantImpaye,
                    MAX(f.dateGeneration) AS derniere
             FROM facture f JOIN declaration d ON d.id=f.idDeclaration WHERE d.idConsommateur=?", 'i', $id)[0];
        $reclamations = compteLignes(
            "SELECT COUNT(*) AS total, SUM(statut<>'Traitée') AS ouvertes, MAX(dateCreation) AS derniere
             FROM reclamation WHERE id=?", 'i', $id)[0];
        $fournisseur = compteLignes(
            "SELECT u.nom, u.prenom, u.email FROM consommateur c JOIN utilisateur u ON u.id=c.idFournisseur WHERE c.id=?", 'i', $id);
        return array('compteurs' => $compteurs, 'factures' => $factures, 'reclamations' => $reclamations,
                     'fournisseur' => $fournisseur ? $fournisseur[0] : null);
    }

    // Fournisseur : les chiffres de son activité
    function compteFournisseur($id)
    {
        return compteLignes(
            "SELECT
                (SELECT COUNT(*) FROM consommateur WHERE idFournisseur=?) AS clients,
                (SELECT COUNT(*) FROM contrat k JOIN consommateur c ON c.id=k.idConsommateur WHERE c.idFournisseur=? AND k.statut='actif') AS contratsActifs,
                (SELECT COUNT(*) FROM adresseCompteur a JOIN consommateur c ON c.id=a.idConsommateur WHERE c.idFournisseur=?) AS compteurs,
                (SELECT COUNT(*) FROM facture f JOIN declaration d ON d.id=f.idDeclaration WHERE d.idFournisseur=?) AS factures,
                (SELECT COALESCE(SUM(d.prixTTC),0) FROM facture f JOIN declaration d ON d.id=f.idDeclaration WHERE d.idFournisseur=? AND f.etat='Impayée') AS montantImpaye,
                (SELECT COUNT(*) FROM reclamation r JOIN consommateur c ON c.id=r.id WHERE c.idFournisseur=? AND r.statut<>'Traitée') AS reclamationsOuvertes,
                (SELECT COUNT(*) FROM declaration WHERE idFournisseur=? AND statut LIKE 'Anomalie%') AS anomalies,
                (SELECT seuilEau FROM fournisseur WHERE id=?) AS seuilEau,
                (SELECT seuilElectricite FROM fournisseur WHERE id=?) AS seuilElectricite",
            'iiiiiiiii', $id, $id, $id, $id, $id, $id, $id, $id, $id)[0];
    }

    // Seuils d'anomalie du fournisseur : au-delà, une déclaration devient une anomalie au lieu d'une facture
    function modifierSeuilsFournisseur($id, $seuilEau, $seuilElectricite)
    {
        global $link;
        $q = mysqli_prepare($link, "UPDATE fournisseur SET seuilEau=?, seuilElectricite=? WHERE id=?");
        mysqli_stmt_bind_param($q, 'ddi', $seuilEau, $seuilElectricite, $id);
        return mysqli_stmt_execute($q);
    }

    // Agent : ses importations annuelles
    function compteAgent($id)
    {
        return compteLignes(
            "SELECT annee, COUNT(*) AS clients, MAX(dateSaisie) AS dateSaisie,
                    SUM(ConsommationEau) AS eau, SUM(ConsommationElectricite) AS electricite
             FROM ConsommationAnuelle WHERE agentDepo=? GROUP BY annee ORDER BY annee DESC", 'i', $id);
    }
?>
