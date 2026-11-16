<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();
    function checkAgent($ID)
    {
        global $link;
        $sql = "SELECT * FROM agent WHERE id='".$ID."'";
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }
    function insertAgent($id)
    {
        global $link;
        $sql = "INSERT INTO agent(id) VALUES ('".$id."')";
        return mysqli_query($link,$sql);
    }
?>
