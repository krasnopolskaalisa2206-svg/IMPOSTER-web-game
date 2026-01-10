<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
</head>
<body>
    <a href="main-menu.html">Back</a>
    <br>
    <a href="lobby.html">host</a>


    <form action = "includes/host-room.inc.php" method = "POST">
        <input type = "text" id = "room_name" name = "room_name">
        <input type = "submit" id = "submit" name = "submit" value = "Start">
    </form> 
</body>
</html>