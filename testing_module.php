<?php
    $sql_add = "(";
    $sql_add = $sql_add . "username, password, ";
    $sql_add = substr($sql_add, 0, strlen($sql_add) - 2);
    echo $sql_add;
?>