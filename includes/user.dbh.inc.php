<?php
    require_once "dbh.inc.php";
    require_once "system.dbh.inc.php";
    require_once "user.validation.inc.php";
    
    function insertUser($username, $raw_password){
        return withConnection(function($connection) use ($username, $raw_password){
        // password hash
        $password = password_hash($raw_password, PASSWORD_BCRYPT);
        
        $parameters = [$username, $password];
        if (insertFunction($connection, 'players', ["username","password"], $parameters)){
            return true;
        } else {
            return false; 
        }
        });
    }

    function fetchPlayerRecord($username){
         return withConnection(function($connection) use ($username){
            if(empty($username)){
                return false;
            }
            try{
                $parameters = array($username);  
                $result = selectFunction($connection, "*", "players", "", "username = ?", $parameters);
                if (mysqli_num_rows($result) !== 1){
                    return false;
                }
                $assoc = [];
                while($row = mysqli_fetch_assoc($result)){
                    array_push($assoc, $row);
                }
                // assumption of this return is that we already validated that there is only one record of that player
                return $assoc[0];
            } catch (Exception $e){
                echo "Error fetching player Record";
            }
        });
    }
    function insertOwnerPlayerSessionRecord(){
        return withConnection(function($connection){
            try {
                if(!playerSessionRecordValidation()){
                    return false;
                }
                $player_id = $_SESSION["id"];
                $room_id = $_SESSION["room_id"];
                $parameters = array($player_id, $room_id);

                if(insertFunction($connection, "player_session", ["player_id","room_id"], $parameters)){
                    $_SESSION["room_id"] = $room_id;
                    return true;
                } else{
                    return false; 
                }
            } catch (Exception $e){
                echo $e->getMessage();
                return false;
            }
        });
    }

?>