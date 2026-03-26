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
        <link rel="stylesheet" href="home-style.css">
        <script src="sfxmanager.js"></script>
        <title>Imposter</title>
    </head>
    <body>
        <!-- The div class=outber border etc is for css to make double border-->
        <div class="outer-border">
            <div class="inner-border">
                <div class="login-box">
                    <form action = "includes/register.inc.php" method = "POST">
                        <h1>Username</h1>
                        <input type="text" class="input-field" id="username" placeholder="- - - - - - - - - - - - -" name="username" required>
                        <p id="error-msg" style="display: none;" class="error-msg">Invalid username</p> 
                        <!-- SOMEONE STYLE THE ERROR MESSAGE -->
                        <h1>Password</h1>
                        <input type="password" class="input-field" id="password" placeholder="• • • • • • • • • • • • • • •" name="password" required>

                        <button class="submit-button" id="submit-btn" name="submit" type="submit">Register</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            const params = new URLSearchParams(window.location.search);
            const error = params.get("error");

            if (error) {
                document.getElementById("error-msg").style.display = "block";
            }

        </script>
    
        <!-- JavaScript here: just assigning functions to the buttons-->
        <!-- <script>
            function handleRegister() {
                const username = document.getElementById("username").value;
                const password = document.getElementById("password").value;
    
                console.log("Username:", username);         // these are for us in case there's a bug so we can check the usrnm and psswrd
                console.log("Password:", password);
    
                if (username && password) {                    // checks if all fields have been filled in
                    window.location.href = "main-menu.html";
    
                } else {
                    alert("Please fill in all fields");
                }
            }
        </script> -->
    </html>