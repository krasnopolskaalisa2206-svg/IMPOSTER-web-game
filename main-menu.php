<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel =  "stylesheet" href="main-menu-style.css">
    <script src="sfxmanager.js"></script>
    <title>Imposter</title>
</head>
<body>

    <div class="outer-border">
        <div class="inner-border">

            <div class="top-left-buttons">
                <div class="settings-wrapper">
                    <button class="circle-btn" id="settings-btn">
                        <img src="style-assets/settings-logo.png" class="logo">
                    </button>
                    <div class="settings-menu" id="settings-menu">
                        <button class="menu-item" id="go-home-btn">Back to Home</button>
                    </div>
                </div>

                <button class="circle-btn" id="instructions-btn">
                    <img src="style-assets/instructions-logo.png" class="logo">
                </button>
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


            <div class="instructions" id="instructions" style="display:none">
                <h3 class="head-instructions">Roles are randomly allocated: </h3>
                <ul>
                    <li>One person is the <span class="imposter">IMPOSTER</span></li>
                    <li>Everyone else is a <span class="normie">NORMIE</span></li>
                </ul>
                <h3 class="head-instructions">Rules for the <span class="normie">normies</span>:</h3>
                <ul>
                    <li>All of you receive the same word - in a specific category</li>
                    <li>(In a randomly allocated order) the <span class="normie">normies</span> must say a word to prove their innocence</li>
                    <li>BUT BE AWARE don't make your words too obvious, you mustn't let the <span class="imposter">imposter</span> guess</li>
                </ul>
                <h3 class="head-instructions">Rules for the <span class="imposter">imposter</span>:</h3>
                <ul>
                    <li>You will not receive the word, however you will know the category</li>
                    <li>When it comes to your turn, try to blend in with the <span class="normie">normies</span></li>
                    <li>Try to figure out what the word is based on the clues that the <span class="normie">normies</span> leave</li>
                </ul>
                <h3 class="head-instructions">Voting phase:</h3>
                <ul>
                    <li>When the host decides, the voting phase begins</li>
                    <li><span class="normie">Normies</span> try to vote for the <span class="imposter">imposter</span></li>
                    <li><span class="imposter">Imposter</span> try to vote a <span class="normie">normie</span> who you think may get voted off</li>
                </ul>
                <h3 class="head-instructions">Winning conditions:</h3>
                <ul>
                    <li>If a <span class="normie">normie</span> is voted off <span class="imposter">IMPOSTER</span> wins</li>
                    <li>If the <span class="imposter">imposter</span> is voted off they have a chance to guess the word</li>
                    <li>If they guess correctly, <span class="imposter">IMPOSTER</span> wins</li>
                    <li>If they guess incorrectly, <span class="normie">NORMIES</span> win</li>
                </ul>
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

        // Toggle Settings Menu
        const settingsBtn = document.getElementById("settings-btn");
        const settingsMenu = document.getElementById("settings-menu");

        settingsBtn.addEventListener('click', () => {
            settingsMenu.classList.toggle('active');
        });

        // Toggle Instructions Modal
        const instructionsBtn = document.getElementById("instructions-btn");
        const instructionsModal = document.getElementById("instructions");

        instructionsBtn.addEventListener('click', () => {
            if (instructionsModal.style.display === "block") {
                instructionsModal.style.display = "none";
            } else {
                instructionsModal.style.display = "block";
            }
        });

        document.getElementById("go-home-btn").addEventListener('click', () => {
            window.location.href = "home.html";
        });
    </script>

</body>
</html>