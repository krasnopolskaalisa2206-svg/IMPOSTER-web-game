<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby - Imposter</title>
    <script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
</head>
<body>
    <div id="loading" class="loading">
        <h2>Connecting to lobby...</h2>
    </div>

    <div id="lobby" style="display: none;">
        <h1>Room: <span id="room-code"></span></h1>
        <h3>Players (<span id="player-count">0</span>/10)</h3>
        
        <ul id="player-list" class="player-list"></ul>
        
        <div id="host-controls" style="display: none;">
            <button id="start-game-btn">Start Game</button>
        </div>
        
        <div id="chat">
            <h3>Chat</h3>
            <div id="chat-messages"></div>
            <input type="text" id="chat-input" placeholder="Type a message...">
            <button id="send-chat-btn">Send</button>
        </div>
        
        <button id="leave-room-btn">Leave Room</button>
    </div>

    <script>
        let socket = null;

        // Check if user came from join page (has session)
        async function initializeLobby() {
            try {
                // Get session data from PHP
                const response = await fetch('includes/get-session-data.php');
                const sessionData = await response.json();

                if (!sessionData.success || !sessionData.room_code) {
                    alert('No active room session. Please join a room first.');
                    window.location.href = 'join-room.html';
                    return;
                }

                console.log('Session data:', sessionData);

                // Connect to Socket.IO
                connectToSocketIO(sessionData);

            } catch (error) {
                console.error('Error initializing lobby:', error);
                alert('Failed to connect to lobby');
                window.location.href = 'main-menu.html';
            }
        }

        function connectToSocketIO(sessionData) {
            console.log('Connecting to Socket.IO server...');

            socket = io('http://localhost:4000', {
                transports: ['websocket', 'polling'],
                reconnection: true,
                reconnectionDelay: 1000,
                reconnectionAttempts: 5
            });

            // Connection events
            socket.on('connect', () => {
                console.log('Connected to server:', socket.id);
                
                // Join the room
                socket.emit('join-room', {
                    roomCode: sessionData.room_code,
                    playerId: sessionData.player_id,
                    username: sessionData.username
                });
            });

            socket.on('connect_error', (error) => {
                console.error('Connection error:', error);
                alert('Failed to connect to game server. Please try again.');
            });

            socket.on('disconnect', (reason) => {
                console.log('Disconnected:', reason);
                if (reason === 'io server disconnect') {
                    // Server kicked us out
                    alert('Disconnected from server');
                    window.location.href = 'main-menu.html';
                }
            });

            // Join events
            socket.on('join-success', (data) => {
                console.log('Successfully joined room:', data);
                // Hide loading, show lobby
                document.getElementById('loading').style.display = 'none';
                document.getElementById('lobby').style.display = 'block';
                // Update UI
                document.getElementById('room-code').textContent = data.roomCode;
                document.getElementById('player-count').textContent = data.players.length;
                updatePlayerList(data.players);
                
                // Show host controls if this user is host
                if (data.isHost) {
                    document.getElementById('host-controls').style.display = 'block';
                }
            });

            socket.on('join-error', (data) => {
                console.error('Failed to join room:', data);
                alert(data.message);
                window.location.href = 'join-room.html';
            });

            // Player events
            socket.on('player-joined', (data) => {
                console.log('Player joined:', data);
                updatePlayerList(data.players);
                document.getElementById('player-count').textContent = data.playerCount;
                showNotification(`${data.player.username} joined the room!`);
            });

            socket.on('player-left', (data) => {
                console.log('Player left:', data);
                updatePlayerList(data.players);
                document.getElementById('player-count').textContent = data.playerCount;
                
                if (data.player) {
                    showNotification(`${data.player.username} left the room`);
                }
                
                // Check if we became host
                if (data.newHost === socket.id) {
                    document.getElementById('host-controls').style.display = 'block';
                    showNotification('You are now the host!');
                }
            });

            // Game events
            socket.on('game-started', (data) => {
                console.log('Game starting:', data);
                showNotification('Game is starting!');
                
                // TODO: Redirect to game page
                setTimeout(() => {
                    window.location.href = 'word-allocation.php';
                }, 2000);
            });

            // Chat events
            socket.on('chat-message', (data) => {
                displayChatMessage(data);
            });

            // Error events
            socket.on('error', (data) => {
                console.error('Socket error:', data);
                alert(data.message);
            });

            // Setup UI event listeners
            setupEventListeners();
        }

        function setupEventListeners() {
            // Start game button (host only)
            document.getElementById('start-game-btn').addEventListener('click', () => {
                socket.emit('start-game');
            });

            // Send chat message
            document.getElementById('send-chat-btn').addEventListener('click', () => {
                sendChatMessage();
            });

            document.getElementById('chat-input').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });

            // Leave room
            document.getElementById('leave-room-btn').addEventListener('click', () => {
                if (confirm('Are you sure you want to leave the room?')) {
                    socket.emit('leave-room');
                    socket.disconnect();
                    window.location.href = 'main-menu.php';
                }
            });
        }

        function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            
            if (message) {
                socket.emit('chat-message', message);
                input.value = '';
            }
        }

        function updatePlayerList(players) {
            const playerList = document.getElementById('player-list');
            playerList.innerHTML = '';
            
            players.forEach((player, index) => {
                const li = document.createElement('li');
                li.textContent = player.username;
                
                if (index === 0) {
                    li.textContent += ' (Host)';
                    li.classList.add('host');
                }
                
                playerList.appendChild(li);
            });
        }

        function displayChatMessage(data) {
            const chatMessages = document.getElementById('chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';
            messageDiv.innerHTML = `<span class="chat-username">${data.username}:</span> ${data.message}`;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Initialize when page loads
        window.addEventListener('DOMContentLoaded', () => {
            initializeLobby();
        });
    </script>
</body>
</html>