// server.js - Socket.IO server for Imposter game
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const mysql = require('mysql2/promise');

const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
    cors: {
        origin: "http://localhost", // Update with your frontend URL
        methods: ["GET", "POST"]
    }
});

// Database connection pool
const pool = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'imposter_db',
    waitForConnections: true,
    connectionLimit: 10
});

// Store active rooms in memory
const activeRooms = new Map();

// Room structure
class GameRoom {
    constructor(roomId, roomCode) {
        this.roomId = roomId;
        this.roomCode = roomCode;
        this.players = new Map(); // socketId -> playerData
        this.status = 'lobby';
        this.hostId = null;
        this.gameData = null;
    }

    addPlayer(socketId, playerData) {
        this.players.set(socketId, playerData);
        if (!this.hostId) {
            this.hostId = socketId;
        }
    }

    removePlayer(socketId) {
        this.players.delete(socketId);
        // If host leaves, assign new host
        if (this.hostId === socketId && this.players.size > 0) {
            this.hostId = this.players.keys().next().value;
        }
    }

    getPlayerList() {
        return Array.from(this.players.values());
    }
}

io.on('connection', (socket) => {
    console.log('New client connected:', socket.id);

    // Join room event
    socket.on('join-room', async (data) => {
        try {
            const { roomCode, playerId, username } = data;

            // Verify room exists in database
            const [rows] = await pool.query(
                'SELECT id, status FROM rooms WHERE room_code = ?',
                [roomCode]
            );

            if (rows.length === 0) {
                socket.emit('join-error', { message: 'Room not found' });
                return;
            }

            const room = rows[0];
            const roomId = room.id;

            // Check if player is in player_session table
            const [playerSession] = await pool.query(
                'SELECT * FROM player_session WHERE player_id = ? AND room_id = ?',
                [playerId, roomId]
            );

            if (playerSession.length === 0) {
                socket.emit('join-error', { message: 'You are not registered in this room' });
                return;
            }

            // Create room in memory if it doesn't exist
            if (!activeRooms.has(roomCode)) {
                activeRooms.set(roomCode, new GameRoom(roomId, roomCode));
            }

            const gameRoom = activeRooms.get(roomCode);

            // Check if room is full
            if (gameRoom.players.size >= 10) {
                socket.emit('join-error', { message: 'Room is full' });
                return;
            }

            // Add player to room
            gameRoom.addPlayer(socket.id, {
                playerId,
                username,
                socketId: socket.id
            });

            // Join socket.io room
            socket.join(roomCode);
            socket.roomCode = roomCode; // Store for easy access

            // Send success to player
            socket.emit('join-success', {
                roomCode,
                roomId,
                players: gameRoom.getPlayerList(),
                isHost: gameRoom.hostId === socket.id
            });

            // Notify all players in room
            io.to(roomCode).emit('player-joined', {
                player: { playerId, username },
                players: gameRoom.getPlayerList(),
                playerCount: gameRoom.players.size
            });

            console.log(`Player ${username} joined room ${roomCode}`);

        } catch (error) {
            console.error('Join room error:', error);
            socket.emit('join-error', { message: 'Server error' });
        }
    });

    // Leave room event
    socket.on('leave-room', () => {
        handlePlayerLeave(socket);
    });

    // Start game event (host only)
    socket.on('start-game', async () => {
        try {
            const roomCode = socket.roomCode;
            if (!roomCode) return;

            const gameRoom = activeRooms.get(roomCode);
            if (!gameRoom) return;

            // Check if socket is host
            if (gameRoom.hostId !== socket.id) {
                socket.emit('error', { message: 'Only host can start the game' });
                return;
            }

            // Check minimum players
            if (gameRoom.players.size < 3) {
                socket.emit('error', { message: 'Need at least 3 players to start' });
                return;
            }

            // Update room status in database
            await pool.query(
                'UPDATE rooms SET status = ? WHERE room_code = ?',
                ['in-progress', roomCode]
            );

            gameRoom.status = 'in-progress';

            // TODO: Assign imposters, get word from PHP, etc.
            
            // Notify all players
            io.to(roomCode).emit('game-started', {
                message: 'Game is starting!',
                players: gameRoom.getPlayerList()
            });

        } catch (error) {
            console.error('Start game error:', error);
            socket.emit('error', { message: 'Failed to start game' });
        }
    });

    // Chat message
    socket.on('chat-message', (message) => {
        const roomCode = socket.roomCode;
        if (!roomCode) return;

        const gameRoom = activeRooms.get(roomCode);
        if (!gameRoom) return;

        const player = gameRoom.players.get(socket.id);
        
        io.to(roomCode).emit('chat-message', {
            username: player.username,
            message,
            timestamp: Date.now()
        });
    });

    // Disconnect event
    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
        handlePlayerLeave(socket);
    });
});

function handlePlayerLeave(socket) {
    const roomCode = socket.roomCode;
    if (!roomCode) return;

    const gameRoom = activeRooms.get(roomCode);
    if (!gameRoom) return;

    const player = gameRoom.players.get(socket.id);
    gameRoom.removePlayer(socket.id);

    // Notify others
    io.to(roomCode).emit('player-left', {
        player: player ? { playerId: player.playerId, username: player.username } : null,
        players: gameRoom.getPlayerList(),
        playerCount: gameRoom.players.size,
        newHost: gameRoom.hostId
    });

    // Remove room if empty
    if (gameRoom.players.size === 0) {
        activeRooms.delete(roomCode);
        console.log(`Room ${roomCode} removed (empty)`);
    }

    //socket.leave(roomCode);
    //delete socket.roomCode;
}

const PORT = process.env.PORT || 4000;
server.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
});