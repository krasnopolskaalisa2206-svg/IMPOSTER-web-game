<?php
    require_once "dbh.inc.php";
 //--------------------
    function generateRoom($room_name){      // this function now returns room_code or false (old returned true or false)
        return withConnection(function($connection) use ($room_name){

            $room_code = generateUniqueRoomCode($connection);   // calls function to generate unique room code and if not returns false
            if(!$room_code) {
                return false;
            }

            $parameters = array($room_name, (string)$room_code, "lobby");
            if(insertFunction($connection, "rooms", ["name", "room_code", "status"], $parameters)){
                $room_id = mysqli_insert_id($connection);
                $_SESSION["room_id"] = $room_id;
                $_SESSION["room_code"] = $room_code;

                if(!insertOwnerPlayerSessionRecord()){
                    return false;
                }
                return $room_code;     // it now returns a room_code instead of true
            } else{
                return false;
            }
        });
    }

 //--------------------
    function generateUniqueRoomCode($connection) {
        $maxAttempts = 10;
        
        for($i = 0; $i < $maxAttempts; $i++) {
            $room_code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT); // generates a unique room code
            
            // check if code already exists
            $parameters = array($room_code);
            $result = selectFunction($connection, "id", "rooms", "", "room_code = ?", $parameters);
            
            if (mysqli_num_rows($result) == 0) {
                return $room_code;
            }
        }
        
        return false; // failed to generate unique code after max attempts
    }
 //--------------------
    function fetchRoomRecord($room_id){
         return withConnection(function($connection) use ($room_id){
            if(empty($room_id)){
                return false;
            }
            try{
                // password hash
                $parameters = array($room_id);  
                $result = selectFunction($connection, "*", "rooms", "", "id = ?", $parameters);
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
 //--------------------
    function wordAllocation(){
        return withConnection(function($connection){
            $valid_word = false;
            $attempt_count = 0;
            while(!$valid_word){
                if($attempt_count >= 10){
                    return false;
                }
                try{
                    $result = selectFunction($connection, "*", "categories", "", "1=1 ORDER BY RAND() LIMIT 1");

                    // if the query failed (ret false) or no results found 
                    if (!$result || mysqli_num_rows($result) <= 0){
                        continue;
                    }
                    // get results
                    $row = mysqli_fetch_assoc($result);
                    $cat_id = $row['category_id'];
                    $title = $row['category_title'];
                } catch(Exception $e){
                    echo "Error" . $e->getMessage();
                    return false;
                }
                
                // fetching a word
                try{
                    // there is a pt.tag_id which signifies which point of speech word we are using
                    // Currently it is just set up to noun, which may be a limitation
                    // work on range
                    $parameters = array($cat_id);
                    $result = selectFunction($connection, "*", "words", "", "category_id = ?", $parameters);
                    // if the result was not found
                    if (!$result || mysqli_num_rows($result) <= 0){
                        $attempt_count++;
                        continue;
                    }
                    $valid_word = true;
                } catch(Exception $e){
                    $attempt_count++;
                    continue;
                }
            }
            if ($valid_word && $result) {
                $row = mysqli_fetch_assoc($result);
                $word_id = $row['word_id'];
                $word = $row['word'];
                return ['word' => $word, 'category' => $title];
                exit();
            } else {
                return false;
            }
        });
    }
?>