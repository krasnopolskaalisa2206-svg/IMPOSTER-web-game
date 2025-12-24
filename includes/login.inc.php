<?php
    require_once "user.validation.inc.php";

    session_start();
    if(isset($_POST["submit"])){

        // Validate
        // Insert or not
        $username = $_POST["username"]; 
        $password = $_POST["password"];

        // if the validation was not passed or if the username exists we redirect
        if (!formValidation($username, $password) || !passwordValidationUser($username, $password)){
            // Redirect();
        } else {
            header("Location: ../main-menu.php");
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