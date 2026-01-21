<?php
    
    /**
     * Script Log
     * 18.12.2025: Artem:  Implemented connection handler and word allocation function
     *                     
     * 
     * 19.12.2025: Artem:  Refactored the word allocation function
     * 
     * 20.12.2025: Artem:  Implemented showAttributeNames function
     *                     Finished insertion function
     *             To-dos: Said function is quite insecure and contains no error handling. Fix.
     *                     Rigorous testing of the insertion function is required                   
     */



    /**
     * This is function allows to establish connection needed for an external function
     * 
     * $callback is a function that is being executed
     */
    function withConnection(callable $callback){
        
        // These are placeholder values for LOCAL TESTING
        // Create a CONFIG file
        $db_servername = "dbhost.cs.man.ac.uk";
        $db_username = "q29570ss"; // *** Use your own username and password here ***
        $db_password = "82q/eCbfXuwbp8nWV1nUP5QRJP8tfmSWdu3GYMNjAzY";
        $db_name = "2025_comp1tut_y6";
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
            exit();
        } finally{
            // Dispose
            if ($connection) {
                mysqli_close($connection);
                $connection = null;
            }
        }
    }
    function insertFunction($connection, $table_name, $attribute_names =[], $parameters){
        $sql ="";
        try{
            if(strlen($table_name)<=0 || count($parameters)<=0){
                throw new Exception("Sql statement error");
            }
            
            // sql base preparation
            $sql = $sql . "INSERT INTO $table_name";
            
            // if any attributes are specified
            if($attribute_names != []){
                // Field names resolution
                $column_names = withConnection(
                function($connection) use ($table_name){
                    return showAttributeNames($connection, $table_name);
                    });
                $sql_add ="(";
                
                foreach($attribute_names as $attribute_name){
                    if(in_array($attribute_name, $column_names)){
                        $sql_add = $sql_add . $attribute_name . ", ";  
                    } else {
                        throw new Exception("White list exception.");
                    }
                }
                $sql_add = substr($sql_add, 0, strlen($sql_add) - 2);
                $sql_add = $sql_add . ")";
                $sql = $sql . $sql_add;
            }
            
            // VALUES
            $sql = $sql . " VALUES";
            $sql_add = "(";
            for($i = 0; $i < count($parameters); $i++){
                try{
                    $sql_add = $sql_add . "?" . ", ";
                } catch (Exception $e){
                    echo "Insertion Exception" . $e->getMessage();
                }
            }
            $sql_add = substr($sql_add, 0, strlen($sql_add) - 2);
            $sql_add = $sql_add . ")";
            $sql = $sql . $sql_add;

            // Create a prepared statement
            $stmt = mysqli_stmt_init($connection);
            if (!mysqli_stmt_prepare($stmt, $sql)){
                throw new Exception("Statement preparation failure");
            } else{
                    // Bind parameters
                    if(empty($parameters)){
                        throw new Exception("No parameters passed");
                    } else {
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
                }
                try{
                    mysqli_stmt_execute($stmt);
                } catch(Exception $e){
                    echo "SQL Statement Execution exception" . $e->getMessage();
                }
                return true;
            } catch(Exception $e){
                echo "Error " . $e->getMessage();
                return false;
        }
    }
    function updateFunction($connection){
        // Implement update
    }
    function deleteFunction($connection){
    }

    function selectFunction($connection, $field_names, $table_name, $joins = "", $condition = "", $parameters=[]){
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
            echo "Error Select" . $e->getMessage();
            exit();
        }

    }
    function showAttributeNames($connection, $table_name){
        if($table_name === ''){
            return false;
        }
        $sql = "DESCRIBE $table_name";
        try{
            $result = mysqli_query($connection, $sql);
            $attribute_names = [];
            while($rows = mysqli_fetch_assoc($result)){
                array_push($attribute_names, $rows['Field']);
            }
            return $attribute_names;
        } catch (Exception $e){
            echo "Error with attribute retreival " . $e->getMessage();
            return false;
        }
    }
?>