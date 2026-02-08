<?php
    require_once "system.dbh.inc.php";
    
    session_start();
    function roomValidation($room_id){     // im not sure if any other files use this one so i didnt replace it
        return withConnection(function($connection) use ($room_id){
            $result = fetchRoomRecord($room_id);
            if($result === false){
                return false;
            } else {
                $room_status = $result["status"];
                if ($room_status !== "lobby"){
                    return false;
                } else {
                    return true;
                }
            }
        });
    }

    function joinRoomByCode($room_code, $player_id) {
        return withConnection(function($connection) use ($room_code, $player_id) {

            $parameters = array($room_code);
            $result = selectFunction($connection, "id, status", "rooms", "", "room_code = ?", $parameters);
            
            if (mysqli_num_rows($result) !== 1) {
                throw new Exception("Room not found");
            }
            
            $room = mysqli_fetch_assoc($result);
            
            if($room['status'] !== 'lobby') {     // only allow to join rooms that are in the lobby stage
                throw new Exception("Room is not accepting players");
            }
            
            $room_id = $room['id'];
            $parameters = array($player_id, $room_id);     // checks if player already exists in the room
            $result = selectFunction($connection, "id", "player_session", "", "player_id = ? AND room_id = ?", $parameters); // "" is a placeholder but im not sure what for
            
            if(mysqli_num_rows($result) > 0) {
                throw new Exception("You are already in this room");
            }
            
            $parameters = array($room_id);
            $result = selectFunction($connection, "COUNT(*) as player_count", "player_session", "", "room_id = ?", $parameters);
            $count = mysqli_fetch_assoc($result);
            
            if($count['player_count'] >= 10) {        // checks the max capacity
                throw new Exception("Room is full (max 10 players)");
            }
            
            $parameters = array($player_id, $room_id);    // adds player to the room
            if(!insertFunction($connection, "player_session", ["player_id", "room_id"], $parameters)) {
                throw new Exception("Failed to add player to room");
            }
            
            return $room_id;
        });
    }

?>