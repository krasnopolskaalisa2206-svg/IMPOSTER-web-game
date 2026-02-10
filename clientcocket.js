class GameClient {
    constructor(serverUrl = 'http://localhost:4000') {
        this.socket = io(serverUrl);
        this.roomCode = null;
        this.playerId = null;
        this.username = null;
        this.isHost = false;
        
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Connection events
        this.socket.on('connect', () => {
            console.log('Connected to server');
        });

        this.socket.on('disconnect', () => {
            console.log('Disconnected from server');
        });

        // Join events
        this.socket.on('join-success', (data) => {
            console.log('Successfully joined room:', data);
            this.roomCode = data.roomCode;
            this.isHost = data.isHost;
            this.onJoinSuccess(data);
        });

        this.socket.on('join-error', (data) => {
            console.error('Failed to join room:', data.message);
            this.onJoinError(data);
        });

        // Player events
        this.socket.on('player-joined', (data) => {
            console.log('Player joined:', data);
            this.onPlayerJoined(data);
        });

        this.socket.on('player-left', (data) => {
            console.log('Player left:', data);
            this.onPlayerLeft(data);
        });

        // Game events
        this.socket.on('game-started', (data) => {
            console.log('Game started:', data);
            this.onGameStarted(data);
        });

        // Chat events
        this.socket.on('chat-message', (data) => {
            console.log('Chat message:', data);
            this.onChatMessage(data);
        });

        // Error events
        this.socket.on('error', (data) => {
            console.error('Socket error:', data.message);
            this.onError(data);
        });
    }

    // Join room with code from PHP session
    async joinRoom(roomCode, playerId, username) {
        this.playerId = playerId;
        this.username = username;
        
        this.socket.emit('join-room', {
            roomCode,
            playerId,
            username
        });
    }

    // Leave current room
    leaveRoom() {
        if (this.roomCode) {
            this.socket.emit('leave-room');
            this.roomCode = null;
            this.isHost = false;
        }
    }

    // Start game (host only)
    startGame() {
        if (this.isHost) {
            this.socket.emit('start-game');
        } else {
            console.warn('Only host can start the game');
        }
    }

    // Send chat message
    sendMessage(message) {
        if (this.roomCode) {
            this.socket.emit('chat-message', message);
        }
    }

    // Override these methods in your implementation
    onJoinSuccess(data) {
        // Update UI with player list
        // data: { roomCode, roomId, players, isHost }
    }

    onJoinError(data) {
        // Show error message to user
        // data: { message }
    }

    onPlayerJoined(data) {
        // Update player list in UI
        // data: { player, players, playerCount }
    }

    onPlayerLeft(data) {
        // Update player list in UI
        // data: { player, players, playerCount, newHost }
        if (data.newHost === this.socket.id) {
            this.isHost = true;
        }
    }

    onGameStarted(data) {
        // Redirect to game screen or update UI
        // data: { message, players }
    }

    onChatMessage(data) {
        // Display chat message
        // data: { username, message, timestamp }
    }

    onError(data) {
        // Show error to user
        // data: { message }
    }
}

// Example usage:
/*
// After user joins room via PHP
const gameClient = new GameClient();

// Get session data from PHP (you'd do this via AJAX or embedded in page)
fetch('/api/get-session-data.php')
    .then(res => res.json())
    .then(sessionData => {
        gameClient.joinRoom(
            sessionData.room_code,
            sessionData.player_id,
            sessionData.username
        );
    });

// Override methods for your UI
gameClient.onJoinSuccess = (data) => {
    document.getElementById('lobby').style.display = 'block';
    updatePlayerList(data.players);
    
    if (data.isHost) {
        document.getElementById('start-btn').style.display = 'block';
    }
};

gameClient.onPlayerJoined = (data) => {
    updatePlayerList(data.players);
    showNotification(`${data.player.username} joined!`);
};

// Start game button (host only)
document.getElementById('start-btn').addEventListener('click', () => {
    gameClient.startGame();
});
*/