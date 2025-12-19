<?php
    
    /**
     * Script Log
     * 18.12.2025: Artem: Implemented connection handler and word allocation function
     *             To-dos: Create a white list with all WHERE clauses (consider it "system queries") 
     *                     
     *                    When combining the sql statement, what if parameters passed are not strings? Fix
     * 
     *                    WordAllocation method is not fully de-coupled from working with a database. Fix
     * 
     * 19.12.2025: Artem: Refactored the word allocation function
     */



    /**
     * This is function allows to establish connection needed for an external function
     * 
     * $callback is a function that is being executed
     */
    function withConnection(callable $callback){
        
        // These are placeholder values for LOCAL TESTING
        $db_servername = "localhost";
        $db_username = "root";
        $db_password = "";
        $db_name = "imposter_db";
        $connection = "";

        // Establish connection
        try{
            $connection = mysqli_connect($db_servername, $db_username, $db_password, $db_name);
            
            // if connection is established output results of a callback
            if($connection){
                return $callback($connection);
            } else{
                echo "Error executing a callback";
            }
        } catch(Exception $e){
            echo "Error connecting to the database" . $e->getMessage();
        } finally{
            // Dispose
            if ($connection) {
                mysqli_close($connection);
                $connection = null;
            }
        }
    }
    function SelectQuery($connection, $field_names, $table_name, $joins = "", $condition = "", $parameters=[]){
        // Implement a white list for column names table names and conditions (based on words)
        $sql ="";
        try{
            
            // sql base preparation
            if(strlen($table_name)<=0 || strlen($field_names)<=0){
                throw new Exception("Sql statement error");
            }
            // Field names
            $sql = $sql . "SELECT $field_names ";
            
            // Table name
            $sql = $sql . "FROM $table_name ";

            // Joins
            if(strlen($joins)>0){
                $sql = $sql . "$joins ";
            }                
            // Condition base
            if(strlen($condition)>0){
                $sql = $sql . "WHERE $condition ";
            }
            // Create a prepared statement
            $stmt = mysqli_stmt_init($connection);
            if (!mysqli_stmt_prepare($stmt, $sql)){
                throw new Exception("Statement preparation failure");
            } else{
                // Bind parameters
                if(!empty($parameters)){
                    // parameter type resolution
                    $types ="";
                    foreach($parameters as $parameter){
                        $type = gettype($parameter);
                        switch($type){
                            case 'string':
                                $types = $types . "s";
                                break; 
                            case 'integer':
                                $types = $types . "i"; 
                                break;
                            case 'double':
                                $types = $types . "d";
                                break;
                            default:
                                throw new Exception("TypeError: Wrong type of parameter is passed into and sql query");
                        }
                    }
                    mysqli_stmt_bind_param($stmt, $types, ...$parameters);
                }
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                return $result;
            }
        } catch(Exception $e){
            echo "Error " . $e->getMessage();
            return false;  
        }

    }
    function WordAllocation(){
        withConnection(function($connection){
            $valid_word = false;
            $attempt_count = 0;
            while(!$valid_word){
                if($attempt_count >= 10){
                    return false;
                }
                try{
                    $result = SelectQuery($connection, "*", "categories", "", "1=1 ORDER BY RAND() LIMIT 1");

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
                }
                
                // fetching a word
                try{
                    // there is a pt.tag_id which signifies which point of speech word we are using
                    // Currently it is just set up to noun, which may be a limitation
                    // work on range
                    $parameters = array($cat_id, 4, 13);
                    $result = SelectQuery($connection, "*", "words w", "JOIN word_pos wp ON w.word_id = wp.word_id 
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
                echo($word_id);
                $word = $row['word'];
                echo $word;
            } else {
                echo "No valid word found.";
            }
        });
    }


WordAllocation();
?>