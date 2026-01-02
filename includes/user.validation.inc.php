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
    function uidValidation($username){
        return withConnection(function($connection) use ($username){ 
            $parameters = array($username);
            $result = selectFunction($connection, "username", "players", "",  "username = ?", $parameters);
            if(mysqli_num_rows($result) > 0){
                // if the username exists then return true
                return true;
            } else{
                // if it doesn't return false
                return false;
            }
        });
    }
    function passwordValidation($username, $raw_password){
        return withConnection(function($connection) use ($username, $raw_password){
            $parameters = array($username);
            $result = selectFunction($connection, "password", "players", "", "username = ?", $parameters);
            
            if(mysqli_num_rows($result) !== 1){
                return false;
            }
            
            $row = mysqli_fetch_assoc($result);
            $stored_hash = $row['password'];
            
            // Verify the password against the stored hash
            if(password_verify($raw_password, $stored_hash)){
                return true;
            } else {
                return false;
            }
        });
    }
    function playerSessionRecordValidation(){
        return withConnection(function($connection){
            // First, fetch the stored hash from the database
            if(empty($_SESSION["id"])){
                return false;
            }
            $parameters = array($_SESSION["id"]);

            try{
                $result = selectFunction($connection, "*", "player_session", "", "player_id = ?", $parameters);
                
                if(mysqli_num_rows($result) !== 0){
                    return false;
                } else {
                    return true;
                }
            } catch(Exception $e){
                echo "Something went wrong";
            }
        });
    }


?>