<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();
    function checkFournisseur($ID)
    {
        global $link;
        $sql = "SELECT * FROM fournisseur
                WHERE id=".(int)$ID;
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }
?>