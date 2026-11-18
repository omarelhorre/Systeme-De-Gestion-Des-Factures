<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();

    function notificationCreer($idUtilisateur,$type,$titre,$detail,$lien,$aTraiter,$idObjet)
    {
        global $link;
        $aTraiter = $aTraiter ? 1 : 0;
        $q = mysqli_prepare($link, "INSERT INTO notification(idUtilisateur,type,titre,detail,lien,aTraiter,idObjet) VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($q, 'issssii', $idUtilisateur, $type, $titre, $detail, $lien, $aTraiter, $idObjet);
        mysqli_stmt_execute($q);
        return mysqli_insert_id($link);
    }

    function notificationsDe($idUtilisateur)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT id,type,titre,detail,lien,aTraiter,idObjet,dateCreation,dateLecture,dateResolution
                                    FROM notification WHERE idUtilisateur=? ORDER BY id DESC LIMIT 30");
        mysqli_stmt_bind_param($q, 'i', $idUtilisateur);
        mysqli_stmt_execute($q);
        return mysqli_fetch_all(mysqli_stmt_get_result($q), MYSQLI_ASSOC);
    }

    function notificationsNonLues($idUtilisateur)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT COUNT(*) FROM notification WHERE idUtilisateur=? AND dateLecture IS NULL");
        mysqli_stmt_bind_param($q, 'i', $idUtilisateur);
        mysqli_stmt_execute($q);
        return (int)mysqli_fetch_row(mysqli_stmt_get_result($q))[0];
    }

    function notificationMaintenant()
    {
        global $link;
        return mysqli_fetch_row(mysqli_query($link, "SELECT NOW()"))[0];
    }

    function notificationExiste($idUtilisateur,$type)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT id FROM notification WHERE idUtilisateur=? AND type=? LIMIT 1");
        mysqli_stmt_bind_param($q, 'is', $idUtilisateur, $type);
        mysqli_stmt_execute($q);
        return mysqli_fetch_row(mysqli_stmt_get_result($q)) !== null;
    }

    function notificationLire($id,$idUtilisateur)
    {
        global $link;
        $q = mysqli_prepare($link, "UPDATE notification SET dateLecture=NOW() WHERE id=? AND idUtilisateur=? AND dateLecture IS NULL");
        mysqli_stmt_bind_param($q, 'ii', $id, $idUtilisateur);
        return mysqli_stmt_execute($q);
    }

    function notificationsToutLire($idUtilisateur)
    {
        global $link;
        $q = mysqli_prepare($link, "UPDATE notification SET dateLecture=NOW() WHERE idUtilisateur=? AND dateLecture IS NULL");
        mysqli_stmt_bind_param($q, 'i', $idUtilisateur);
        return mysqli_stmt_execute($q);
    }

    function notificationResoudre($idUtilisateur,$type,$idObjet)
    {
        global $link;
        if ($idObjet === null) {
            $q = mysqli_prepare($link, "UPDATE notification SET dateResolution=NOW(), dateLecture=COALESCE(dateLecture,NOW())
                                        WHERE idUtilisateur=? AND type=? AND dateResolution IS NULL");
            mysqli_stmt_bind_param($q, 'is', $idUtilisateur, $type);
        } else {
            $q = mysqli_prepare($link, "UPDATE notification SET dateResolution=NOW(), dateLecture=COALESCE(dateLecture,NOW())
                                        WHERE idUtilisateur=? AND type=? AND idObjet=? AND dateResolution IS NULL");
            mysqli_stmt_bind_param($q, 'isi', $idUtilisateur, $type, $idObjet);
        }
        return mysqli_stmt_execute($q);
    }

    function notificationDestinataire($idUtilisateur)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT nom,prenom,email FROM utilisateur WHERE id=?");
        mysqli_stmt_bind_param($q, 'i', $idUtilisateur);
        mysqli_stmt_execute($q);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    }

    function notificationFournisseurDe($idConsommateur)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT idFournisseur FROM consommateur WHERE id=?");
        mysqli_stmt_bind_param($q, 'i', $idConsommateur);
        mysqli_stmt_execute($q);
        $ligne = mysqli_fetch_row(mysqli_stmt_get_result($q));
        return $ligne ? (int)$ligne[0] : null;
    }

    function notificationConsommateurDeFacture($idFacture)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT d.idConsommateur FROM facture f JOIN declaration d ON d.id=f.idDeclaration WHERE f.id=?");
        mysqli_stmt_bind_param($q, 'i', $idFacture);
        mysqli_stmt_execute($q);
        $ligne = mysqli_fetch_row(mysqli_stmt_get_result($q));
        return $ligne ? (int)$ligne[0] : null;
    }

    function notificationConsommateurDeDeclaration($idDeclaration)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT idConsommateur FROM declaration WHERE id=?");
        mysqli_stmt_bind_param($q, 'i', $idDeclaration);
        mysqli_stmt_execute($q);
        $ligne = mysqli_fetch_row(mysqli_stmt_get_result($q));
        return $ligne ? (int)$ligne[0] : null;
    }

    function notificationDerniereReclamation($idConsommateur)
    {
        global $link;
        $q = mysqli_prepare($link, "SELECT MAX(idReclamation) FROM reclamation WHERE id=?");
        mysqli_stmt_bind_param($q, 'i', $idConsommateur);
        mysqli_stmt_execute($q);
        $ligne = mysqli_fetch_row(mysqli_stmt_get_result($q));
        return $ligne && $ligne[0] !== null ? (int)$ligne[0] : null;
    }
?>
