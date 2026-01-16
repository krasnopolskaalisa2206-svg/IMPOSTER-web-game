<?php
    require_once "system.dbh.inc.php";
    require_once "user.dbh.inc.php";
    require_once "system.validation.inc.php";

    if(isset($_POST["submit"])){
        if(isset($_POST["room_name"])){
            $room_name = $_POST["room_name"];
            try{     
                if(!generateRoom($room_name)){
                    echo "Error at validation";
                    if(!insertOwnerPlayerSessionRecord($_SESSION["id"])){
                        echo " didn't insert OPSR";
                    } else {
                        echo " didn't generate a room";
                    }
                    //exit();
                } else {
                    header("Location: ../lobby.php");
                }
            } catch (Exception $e){
                echo "Exception at validation " . $e->getMessage();
                // Redirect();  
            } 
        } else {
            echo "Room name wasn't set";
            // Redirect();
        }
    }else{
        // Redirect();
    }
    function Redirect(){
        // Redirect the user in case of illegal access
        $redirect = $_SESSION['redirect'];
        if(!$redirect){
            header("Location: ../index.php");
        } else {
            header("Location: ../$redirect");
        }    
    }
?>