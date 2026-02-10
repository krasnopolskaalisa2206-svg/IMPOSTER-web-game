<?php
    session_start();
    require_once "system.dbh.inc.php";
    require_once "user.dbh.inc.php";
    require_once "system.validation.inc.php";

    if(isset($_POST["submit"])){
        if(isset($_POST["room_name"])){
            $room_name = $_POST["room_name"];
            try{   
                $room_code = generateRoom($room_name);  

                if(!$room_code){
                    echo "Error at validation";
                    exit();

                } else {
                    $_SESSION['room_code'] = $room_code;
                    echo "Room code set to: " . $_SESSION["room_code"];
                    header("Location: ../lobby.php");
                }
            } catch (Exception $e){
                echo "Exception at validation " . $e->getMessage();
                Redirect();  
            } 
        } else {
            echo "Room name wasn't set";
            Redirect();
        }
    }else{
        Redirect();      // i uncommented the redirects and exits
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