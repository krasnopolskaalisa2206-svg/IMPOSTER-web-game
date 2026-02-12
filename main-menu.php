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

            <div class="top-left-buttons">
                <button class="circle-btn"><img src="style-assets/settings-logo.png" class="logo"></button>
                <button class="circle-btn"><img src="style-assets/cosmetics-logo.png" class="logo"></button>
            </div>

            <div class="button-space">
            <button type="button" class="main-menu-button" id="join-room-btn"> 
            <img src="style-assets/green-play-button-icon.png" class="play-icon">
                <span>JOIN ROOM</span>
            </button>
            <button type="button" class="main-menu-button" id="host-room-btn"> 
            <img src="style-assets/red-play-button-icon.png" class="play-icon">
                <span>HOST ROOM</span>
            </button>
            </div>

        </div>
    </div>

    <script>
        document.getElementById("join-room-btn").addEventListener('click', function() {
            window.location.href="join-room.php";
        });

        document.getElementById("host-room-btn").addEventListener('click', function() {
            window.location.href="host-room.php";
        });

    </script>

</body>
</html>