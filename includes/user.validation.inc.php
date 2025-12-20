<?php
    /** 
     * Script Log: 
     * 19.12.2025: Artem: Implemented user registration form validation
     *             To-dos: Change the redirect function such that it accepts an error as a parameter
     *                     and concatenates it to the url so it can be displayed
    */

    /**
     *  Initial user registration form validation
     */
    function registrationFormValidation($username, $password){
        try{
            if(empty($username) || empty($password)){
                echo "The output is false";
                return false;
            }
            return true;
        } catch(Exception $e){
            return false;
        }
    }
    function uidDoesNotExist($username){
        return withConnection(function($connection) use ($username){
            $parameters = array($username);
            $result = selectFunction($connection, "username", "players", "",  "username = ?", $parameters);
            if(mysqli_num_rows($result) > 0){
                return false;
            } else{
                return true;
            }
        });
    }

?>