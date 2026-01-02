<?php
    // This monstrosity is a special validation script invoked by the js module to set up a connection
    include "user.validation.inc.php";
    include "system.validation.inc.php";

    header("Content-type: application/json");
    
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    $room_id = $data["room_id"];
    $result = [];
    try{
        if(!$room_id){
            throw new Exception();
        }
        $val = roomValidation($room_id);

        if($val !== true){
            $result["validated"] = false;
        } else {
            $result["validated"] = true;
        }

    } catch(Exception $e){
        // This is a placeholder
        print "Oopsie daisy, something went wrong";
        exit();
    }
    echo json_encode($result);
    exit();
?>