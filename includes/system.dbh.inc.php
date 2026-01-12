<?php
    require_once "dbh.inc.php";
    
    function generateRoom($room_name){
        return withConnection(function($connection) use ($room_name){
            $parameters = array($room_name, "lobby");

            if(insertFunction($connection, "rooms", ["name", "status"], $parameters)){
                $room_id = mysqli_insert_id($connection);
                $_SESSION["room_id"] = $room_id;
                if(!insertOwnerPlayerSessionRecord()){
                    return false;
                }
                return true;
            } else{
                return false;
            }
        });
    }
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
                    $parameters = array($cat_id, 4, 13);
                    $result = selectFunction($connection, "*", "words w", "JOIN word_pos wp ON w.word_id = wp.word_id 
                                                        JOIN word_categories wc ON wp.word_pos_id = wc.word_pos_id 
                                                        JOIN pos_tags pt ON wp.pos_tag_id = pt.tag_id
                                                        JOIN categories c ON wc.category_id = c.category_id", "c.category_id = ? AND wp.level < ? AND pt.tag_id = ?", $parameters);
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
                return $word;
                exit();
            } else {
                return false;
            }
        });
    }
?>