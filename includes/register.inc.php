<?php
    /** 
     * Script Log: 
     * 19.12.2025: Artem: Implemented user crearion
     * 
     * 24.12.2025: Artem: changed validation settings
     * 
    */

    session_start();
    require_once "user.validation.inc.php";
    if(isset($_POST["submit"])){

        $username = $_POST["username"]; 
        $password = $_POST["password"];

        // if the validation was not passed or if the username exists we redirect
        if (!formValidation($username, $password) || uidValidation($username)){
            Redirect();
        } else {
            try{
                if(insertUser($username, $password)){              
                    $result = fetchPlayerRecord($username);
                    if($result == false){
                        throw new Exception();
                    }
                    $_SESSION['id'] = $result['id'];
                    $_SESSION["username"] = $username;
                    header("Location: ../main-menu.php");
                } else{
                    // This is a placeholder, replace with an actual error code
                    echo "Something went wrong, user was not inserted";
                    exit();
                }
            } catch(Exception $e){
                echo "Something went wrong, user was invalidated";
            }
        }
        unset($_POST["submit"]);
    } else {
        Redirect();
    }
    function Redirect(){
        // Redirect the user in case of illegal access
        $redirect = $_SESSION['redirect'];
        if(!$redirect){
            header("Location: ../index.php");
        }
        // Optionally include errors sent
        header("Location: ../$redirect?error=invalid");
    }
?>