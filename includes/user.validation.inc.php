<?php
    /** 
     * Script Log: 
     * 19.12.2025: Artem: Implemented user registration form validation
     *             To-dos: Change the redirect function such that it accepts an error as a parameter
     *                     and concatenates it to the url so it can be displayed
     * 
     * 22.12.2025: Artem: decoupled validation from the database
    */

    require_once "user.dbh.inc.php";

    /**
     *  Initial user registration form validation
     */
    function formValidation($username, $password){
        try{
            if(empty($username) || empty($password)){
                return false;
            }
            return true;
        } catch(Exception $e){
            return false;
        }
    }
    function uidValidationUser($username){
        return uidValidationDB($username);
    }
    function passwordValidationUser($username, $password){
        return passwordValidationDB($username, $password);
    }


?>