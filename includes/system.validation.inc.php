<?php
    require_once "system.dbh.inc.php";
    
    session_start();
    function roomValidation($room_id){
        return withConnection(function($room_id){
            $parameters = array($room_id);

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

?>