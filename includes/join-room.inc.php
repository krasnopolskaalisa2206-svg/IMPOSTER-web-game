<?php
include "user.validation.inc.php";
include "system.validation.inc.php";
require_once "system.dbh.inc.php";

header("Content-Type: application/json");

try {    // this one checks if u are logged in in case someone manually types the link in
    if(!isset($_SESSION["id"])) {
        throw new Exception("User not authenticated");
    }

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!is_array($data) || !isset($data["room_code"])) {   // used room_code instead of room_id
        throw new Exception("Invalid input: room code required");
    }

    $room_code = trim($data["room_code"]);   // trim to remove whitespace
    $player_id = $_SESSION["id"];            // gets logged in user's id

    if(!preg_match('/^\d{6}$/', $room_code)) {       // check if code is 6 digits long
        throw new Exception("Room code must be 6 digits");
    }

    $room_id = joinRoomByCode($room_code, $player_id);
    
    if($room_id) {
        $_SESSION["current_room_id"] = $room_id;
        $_SESSION["room_code"] = $room_code;
        
        echo json_encode([
            "validated" => true,
            "room_id" => $room_id,
            "message" => "Successfully joined room"
        ]);
    } else { throw new Exception("Failed to join room")}

} catch (Exception $e) {
    http_response_code(400);    // tells browser it's an error
    echo json_encode([
        "validated" => false,
        "error" => "Validation failed",
        "message" => $e->getMessage()
    ]);
    exit();
}
?>
