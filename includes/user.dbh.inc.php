<?php
    require_once "dbh.inc.php";
    
    function insertUser($username, $raw_password){
        return withConnection(function($connection) use ($username, $raw_password){
        // password hash
        $password = password_hash($raw_password, PASSWORD_BCRYPT);
        
        $parameters = [$username, $password];
        if(insertFunction($connection, 'players', ["username","password"], $parameters)){
            return true;
        } else{
            return false; 
        }
        });
    }

    function fetchPlayerRecord($username){
         return withConnection(function($connection) use ($username){
            // password hash
            $parameters = array($username);  
            $result = selectFunction($connection, "*", "players", "", "username = ?", $parameters);
            if(mysqli_num_rows($result) !== 1){
                return false;
            }
            $assoc = [];
            while($row = mysqli_fetch_assoc($result)){
                array_push($assoc, $row);
            }
            // assumption of this return is that we already validated that there is only one record of that player
            return $assoc[0];
        });
    }

?>