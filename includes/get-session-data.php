<?php
session_start();
header("Content-Type: application/json");

try {
    if(!isset($_SESSION["id"])) {
        throw new Exception("User not authenticated");
    }

    // Return session data needed for Socket.IO connection
    echo json_encode([
        "success" => true,
        "player_id" => $_SESSION["id"],
        "username" => $_SESSION["username"] ?? "Player",
        "room_code" => $_SESSION["room_code"] ?? null,
        "room_id" => $_SESSION["current_room_id"] ?? null
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>