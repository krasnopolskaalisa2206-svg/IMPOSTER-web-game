<?php
    require_once "system.dbh.inc.php";
    require_once "user.dbh.inc.php";
    require_once "system.validation.inc.php";
    if(isset($_POST["submit"])){
        if(isset($_POST["room_name"])){
            $room_name = $_POST["room_name"];
            generateRoom($room_name);
            try{    
                insertOwnerPlayerSessionRecord($_SESSION["id"]);
            } catch (Exception $e){
                echo "Error inserting a room";
                Redirect();  
            } 
        } else {
            Redirect();
        }
    }else{
        Redirect();
    }
?>