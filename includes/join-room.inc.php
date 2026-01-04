<?php
include "user.validation.inc.php";
include "system.validation.inc.php";

header("Content-Type: application/json");

try {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!is_array($data) || !isset($data["room_id"])) {
        throw new Exception("Invalid input");
    }

    $room_id = $data["room_id"];
    $val = roomValidation($room_id);

    $result = [
       "validated" => ($val === true)
    ];
    $result["validated"] = true;
    echo json_encode($result);
    exit();

} catch (Exception $e) {
    echo json_encode([
        "error" => "Validation failed",
        "message" => $e->getMessage()
    ]);
}
?>
