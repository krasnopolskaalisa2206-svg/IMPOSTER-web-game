<?php
    require_once "dbh.inc.php";
    
    function insertUser($username, $raw_password){
        withConnection(function($connection) use ($username, $raw_password){
        // password hash
        $password = password_hash($raw_password, PASSWORD_BCRYPT);
        
        $parameters = [$username, $password];
        insertFunction($connection, 'players', ["username","password"], $parameters);
        });
    }

    function passwordValidationDB($username, $raw_password){
        return withConnection(function($connection) use ($username, $raw_password){
            // password hash
            $password = password_hash($raw_password, PASSWORD_BCRYPT);
            $parameters = array($username);  
            $result = selectFunction($connection, "password", "players", "", "username = ?", $parameters);
            echo mysqli_num_rows($result);
            if(mysqli_num_rows($result) == 0){
                return false;
            } else if(mysqli_num_rows($result) !== 1){
                // this case means that there is more than one user with specified credentials
                return false;
            } else {
                return true;
            }
        });
    }
    function uidValidationDB($username){
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

?>