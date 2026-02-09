<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Start<br>";
require_once "includes/system.dbh.inc.php";

echo "Step 2: DB Loaded<br>";
require_once "includes/user.functions.inc.php";

echo "Step 3: Functions Loaded<br>";
session_start();

// Get room information
$room_id = $_SESSION["room_id"];
$room_code = $_SESSION["room_code"];

// Get room details
$room = fetchRoomRecord($room_id);
$room_name = $room['name'] ?? 'Room';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
</head>
<body>
    <a href="main-menu.html">Back</a>
        <div class="host-container">
        <h2>Room Code: <span id="display-code"><?php echo $_SESSION['room_code']; ?></span></h2>
        <?php if(isset($_SESSION['room_code'])): ?>
            <h2>Room Code: <span id="display-code"><?php echo $_SESSION['room_code']; ?></span></h2>
        <?php else: ?>
            <h2>Room Code: <span id="display-code">Creating room...</span></h2>
        <?php endif; ?>

        <div id="player-list">
            <p>Waiting for players...</p>
        </div>
    
        <form action="includes/host-room.inc.php" method="POST">
            <input type="text" name="room_id" value="<?php echo $_SESSION['room_id']; ?>">
            <button type="submit" name="submit" class="btn-start">Start</button>
        </form>
    </div> 

    <!-- Auto-refresh every 3 seconds to show new players -->
    <script>
        setTimeout(function() {
            location.reload();
        }, 3000);
    </script>
</body>
</html>