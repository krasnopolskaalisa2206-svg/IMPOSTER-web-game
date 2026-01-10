<?php
    session_start();
    $uid = $_SESSION["username"];
    echo "<h1>Welcome, $uid</h1>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
</head>
<body>
    <a href="index.php">back</a>
    <br>
    <a href="join-room.html">join room</a>
    <br>
    <a href="host-room.php">host room</a>
</body>
</html>