<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="http://localhost:4000/socket.io/socket.io.min.js"></script>
    <script src="includes/JS/scripts.js"></script>
    <link rel =  "stylesheet" href="join-room-style.css">
    <title>Imposter</title>
</head>
<body>
    <div class="outer-border">
        <div class="inner-border">
            <br>
            <form id="joinRoomForm">
                <!--Join the room-->
                <input class="input-code" id="password" placeholder="• • • • • • • • • • • • • •" type="text" required>
                <p></p>
                <button type="submit"name="button" class="join-button" id="join-btn" id="submit">JOIN</button>
            </form>
        </div>
    </div>  
</body>
</html>