<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel =  "stylesheet" href="main-menu-style.css">
    <title>Imposter</title>
</head>
<body>

    <div class="outer-border">
        <div class="inner-border">
            <button type="button" class="main-menu-button" id="join-room-btn"> JOIN ROOM </button>
            <button type="button" class="main-menu-button" id="host-room-btn"> HOST ROOM </button>
            <button type="button" class="main-menu-button" id="back-btn"> BACK </button>
        </div>
    </div>

    <script>
        document.getElementById("back-btn").addEventListener('click', function() {
            window.location.href="home.html";
        });

        document.getElementById("join-room-btn").addEventListener('click', function() {
            window.location.href="join-room.php";
        });

        document.getElementById("host-room-btn").addEventListener('click', function() {
            window.location.href="host-room.php";
        });

    </script>

</body>
</html>