<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="http://localhost:4000/socket.io/socket.io.min.js"></script>
    <script src="includes/JS/scripts.js"></script>
    <title>Imposter</title>
</head>
<body>
    <a href="main-menu.html">Back</a>
    <form id="joinRoomForm">
        <input 
            type="text" 
            id="roomBox" 
            name="roomBox" 
            placeholder="000000"
            maxlength="6"
            pattern="[0-9]{6}"
            required
        >
        <div id="error-message" class="error"></div>
        <div id="loading" class="loading">Joining room...</div>
        <button type="submit" id="submit">Join Room</button>
    </form>
    <?php
        echo $PORT;
    ?>

    <script>
        document.getElementById('joinRoomForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const roomCode = document.getElementById('roomBox').value.trim();
            const submitBtn = document.getElementById('submit');
            const errorDiv = document.getElementById('error-message');
            const loadingDiv = document.getElementById('loading');
            
            // Validate input
            if (!roomCode || roomCode.length !== 6 || !/^\d{6}$/.test(roomCode)) {
                errorDiv.textContent = 'Please enter a valid 6-digit room code';
                return;
            }
            
            // Clear previous errors
            errorDiv.textContent = '';
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Joining...';
            loadingDiv.style.display = 'block';
            
            try {
                console.log('Attempting to join room:', roomCode);
                
                const response = await fetch('includes/join-room.inc.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ room_code: roomCode })
                });
                
                // Get the raw response text first
                const text = await response.text();
                console.log('Raw PHP response:', text);
                
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('Failed to parse JSON:', parseError);
                    console.error('Response was:', text);
                    throw new Error('Server returned invalid response. Check console for details.');
                }
                
                if (data.validated) {
                    console.log('✅ PHP validation successful:', data);
                    
                    // Redirect to lobby
                    // The lobby.php page will handle Socket.IO connection
                    window.location.href = 'lobby.php';
                } else {
                    // Show error message
                    errorDiv.textContent = data.message || 'Failed to join room';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Join Room';
                    loadingDiv.style.display = 'none';
                }
                
            } catch (error) {
                console.error('Error joining room:', error);
                errorDiv.textContent = error.message || 'An error occurred. Please try again.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Join Room';
                loadingDiv.style.display = 'none';
            }
        });
        
        // Auto-format room code input
        document.getElementById('roomBox').addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>