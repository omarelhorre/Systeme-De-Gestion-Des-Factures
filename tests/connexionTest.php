<?php
    include_once("BD/connexion.php");
    try
    {
        $connexion = connexion('127.0.0.1');
    }
    catch(mysqli_sql_exception $e)
    {
        exit(1);
    }
?>