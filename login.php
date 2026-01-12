<?php
    session_start();
    
    // Redirection
    $uri = $_SERVER["REQUEST_URI"];
    $uri_array = explode("/", $uri);
    $_SESSION['redirect'] = $uri_array[count($uri_array) - 1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
</head>
<body>
    <a href="home.html">Back</a>
    <br>
    <a href="main-menu.php">login</a>
        <form action = "includes/login.inc.php" method = "POST">
            <input type = "text" id = "username" name = "username">
            <input type = "password" id = "password" name = "password">
            <input type = "submit" id = "submit" name = "submit" value = "Log in">
        </form> 
</body>
</html>