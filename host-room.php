<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

//echo "Step 1: Start<br>";
require_once "includes/system.dbh.inc.php";

//echo "Step 2: DB Loaded<br>";


//echo "Step 3: Functions Loaded<br>";

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
    <link rel =  "stylesheet" href="home-style.css">
</head>
<body>
        <div class="outer-border">
            <div class="inner-border">
                <div class="host-container">
                    <p>ENTER ROOM NAME</p>

                    <form action = "includes/host-room.inc.php" method = "POST">
                        <input type = "text" class="input-field" id = "room_name" placeholder="- - - - - - - - - - - - -" name = "room_name" required>
                        <input type="text" name="room_id" value="<?php echo $_SESSION['room_id']; ?>">

                        <div class="btn-container">
                            <button type="button" class="submit-button" id="back-btn"> BACK </button>
                            <input type = "submit" class="submit-button" id = "start-btn" name = "submit" value = "START">
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <script>
        document.getElementById("back-btn").addEventListener('click', function() {
            window.location.href="main-menu.php";
        }); </script>

    <!-- Auto-refresh every 3 seconds to show new players
    <script>
        setTimeout(function() {
            location.reload();
        }, 3000);
    </script> -->

        <!-- Basically it was showing the code of the old room instead of a new one so i removed it. -->

        <!--<//?php if(isset($_SESSION['room_code'])): ?>
            <h2>Room Code: <span id="display-code"><//?php echo $_SESSION['room_code']; ?></span></h2>
        <//?php else: ?>
            <h2>Room Code: <span id="display-code">Creating room...</span></h2>
        <//?php endif; ?>-->
</body>
</html>