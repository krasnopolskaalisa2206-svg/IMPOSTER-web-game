<?php
    /** 
     * Script Log: 
     * 19.12.2025: Artem: Implemented user crearion
     * 
     * 
    */
    session_start();
    if(isset($_POST["submit"])){
        try{
            require_once "dbh.inc.php";
            require_once "user.validation.inc.php";
        }
        catch(Exception $e){
            // Redirect the user with an appropriate error
            Redirect();
            exit();
        }
        // Validate
        // Insert or not
        $username = $_POST["username"]; 
        $password = $_POST["password"];

        // if the validation was not passed or if the username exists we redirect
        if (!registrationFormValidation($username, $password) || !uidDoesNotExist($username)){
            Redirect();
        } else {
            withConnection(function($connection) use ($username, $password){
                $parameters = [$username, $password];
                insertFunction($connection, 'players', ["username","password"], $parameters);
            });
            }
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
        header("Location: ../$redirect");
    }
?>