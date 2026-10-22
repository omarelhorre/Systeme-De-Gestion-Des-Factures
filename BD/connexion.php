<?php
    function connexion($serv="db",$user="root",$bd="fatourti")
    {
        $pass = getenv('mysql_password_root');
        return mysqli_connect($serv,$user,$pass,$bd);
    }
?>