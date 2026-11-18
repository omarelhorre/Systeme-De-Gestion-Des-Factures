<?php
    include_once(ROOT."BD/connexion.php");
    $link = connexion();
    function checkAgent($ID)
    {
        global $link;
        $sql = "SELECT * FROM agent WHERE id=".(int)$ID;
        return mysqli_fetch_array(mysqli_query($link,$sql));
    }
    function insertAgent($id)
    {
        global $link;
        $sql = "INSERT INTO agent(id) VALUES (".(int)$id.")";
        return mysqli_query($link,$sql);
    }
?>
