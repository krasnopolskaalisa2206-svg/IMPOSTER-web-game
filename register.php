<?php
    session_start();
    
    // Redirection
    $uri = $_SERVER["REQUEST_URI"];
    $uri_array = explode("/", $uri);
    $_SESSION['redirect'] = $uri_array[count($uri_array) - 1];
    echo $_SESSION['redirect'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
</head>
<body>
    <a href="index.html">Back</a>
    <br>
    <a href="login.php">login</a>
    <form action = "includes/register.inc.php" method = "POST">
            <input type = "text" id = "username" name = "username">
            <input type = "password" id = "password" name = "password">
            <input type = "submit" id = "submit" name = "submit" value = "Register">
        </form> 
</body>
</html>