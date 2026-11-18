<?php 
    function connexion($serv="db",$user="root",$bd="fatourti")
    {
        $pass = getenv('mysql_password_root');
        $lien = mysqli_connect($serv,$user,$pass,$bd);
        if ($lien && is_file('/tmp/fatourti-demo-date')) mysqli_query($lien, 'SET timestamp = '.time());
        return $lien;
    }
?>