<?php 
    function connexion($serv="localhost",$user="root",$bd="fatourti")
    {
        $pass = getenv('password_db');
        return mysqli_connect($serv,$user,$pass,$bd);
    }
?>