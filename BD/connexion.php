<?php
function connexion($serv="localhost", $user="root",
                   $pass="", $bd="fatourti")
{
    return mysqli_connect($serv, $user, $pass, $bd);
}
?>