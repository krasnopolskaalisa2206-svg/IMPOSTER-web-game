// server.js - Socket.IO server for Imposter game
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const mysql = require('mysql2/promise');
const fetch = require('node-fetch'); // npm install node-fetch@2

const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
    cors: {
        origin: "http://localhost",
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

class GameRoom {
    constructor(roomId, roomCode) {
        this.roomId = roomId;
        this.roomCode = roomCode;
        this.players = new Map();       // socketId -> playerData
        this.status = 'lobby';
        this.hostId = null;
        this.gameData = null;           // { word, category, imposterSocketId }

        // Turn system
        this.turnOrder = [];            // shuffled array of socketIds
        this.currentTurnIndex = 0;
        this.playersWhoWent = new Set();// track who has already gone

        // Voting
        this.votes = new Map();         // voterSocketId -> votedSocketId
        // Timer
        this.turnTimer = null;   // turn timer handle so it can be cleared at any point
    }

    addPlayer(socketId, playerData) {
        this.players.set(socketId, playerData);
        if (!this.hostId) this.hostId = socketId;
    }

    removePlayer(socketId) {
        this.players.delete(socketId);
        if (this.hostId === socketId && this.players.size > 0) {
            this.hostId = this.players.keys().next().value;
        }
    }

    getPlayerList() {
        return Array.from(this.players.values());
    }

    getTurnOrderList() {
        return this.turnOrder
            .filter(sid => this.players.has(sid))
            .map(sid => ({
                socketId: sid,
                username: this.players.get(sid).username
            }));
    }

    currentTurnSocketId() {
        // Skip any players who have left
        while (
            this.currentTurnIndex < this.turnOrder.length &&
            !this.players.has(this.turnOrder[this.currentTurnIndex])
        ) {
            this.currentTurnIndex++;
        }
        return this.turnOrder[this.currentTurnIndex] ?? null;
    }

    advanceTurn() {
        const justWent = this.turnOrder[this.currentTurnIndex];
        this.playersWhoWent.add(justWent);
        this.currentTurnIndex++;
    }

    // True when every (still connected) player has had a turn
    allPlayersWent() {
        for (const sid of this.players.keys()) {
            if (!this.playersWhoWent.has(sid)) return false;
        }
        return true;
    }
}

function shuffleArray(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

async function fetchWordFromPHP() {
    try {
        const res = await fetch('http://localhost/y6-team-project/includes/get-word.inc.php');
        const data = await res.json();
        return data; // { word, category }
    } catch (err) {
        console.error('Failed to fetch word from PHP:', err);
        return null;
    }
}

// ── ADDED: Turn timer helper ───────────────────────────────────────────────────
// Starts a 30-second countdown for the current player's turn.
// If they don't submit in time, their turn is skipped and the next player goes.
const TURN_TIME_MS = 30000;

function startTurnTimer(gameRoom, roomCode) {
    // Clear any existing timer first
    if (gameRoom.turnTimer) clearTimeout(gameRoom.turnTimer);

    const currentSid = gameRoom.currentTurnSocketId();
    if (!currentSid) return;

    // Tell all clients when this turn's deadline is so they can count down
    io.to(roomCode).emit('turn-timer-start', {
        durationMs: TURN_TIME_MS,
        currentTurnSocketId: currentSid
    });

    gameRoom.turnTimer = setTimeout(() => {
        // Double-check the same player is still up (could have disconnected)
        if (gameRoom.currentTurnSocketId() !== currentSid) return;
        if (gameRoom.status !== 'in-progress') return;

        const timedOutPlayer = gameRoom.players.get(currentSid);
        const username = timedOutPlayer?.username ?? 'Unknown';

        // Notify room that this player ran out of time
        io.to(roomCode).emit('chat-message', {
            username: '⏰ Timer',
            message: `${username} ran out of time and was skipped!`,
            timestamp: Date.now(),
            isSystem: true
        });

        // Advance turn just like a normal submission
        gameRoom.advanceTurn();

        if (gameRoom.allPlayersWent()) {
            gameRoom.status = 'voting';
            io.to(roomCode).emit('chat-message', {
                username: '🎮 Game',
                message: 'Everyone has spoken. Time to vote!',
                timestamp: Date.now(),
                isSystem: true
            });
            io.to(roomCode).emit('start-voting', { players: gameRoom.getTurnOrderList() });
            return;
        }

        const nextSid = gameRoom.currentTurnSocketId();
        const nextPlayer = gameRoom.players.get(nextSid);

        io.to(roomCode).emit('chat-message', {
            username: '🎮 Game',
            message: `${nextPlayer.username}'s turn!`,
            timestamp: Date.now(),
            isSystem: true
        });

        io.to(roomCode).emit('turn-update', {
            currentTurnSocketId: nextSid,
            currentTurnUsername: nextPlayer.username
        });

        // Start the timer for the next player
        startTurnTimer(gameRoom, roomCode);
    }, TURN_TIME_MS);
}
// ────────────────────────────────────────────────────────────────────

// ── SOCKET EVENTS ─────────────────────────────────────────────────────────────

io.on('connection', (socket) => {
    console.log('New client connected:', socket.id);

    // ── JOIN ROOM ────────────────────────────────────────
    socket.on('join-room', async (data) => {
        try {
            const { roomCode, playerId, username } = data;

            const [rows] = await pool.query(
                'SELECT id, status FROM rooms WHERE room_code = ?',
                [roomCode]
            );
            if (rows.length === 0) {
                socket.emit('join-error', { message: 'Room not found' });
                return;
            }

            const roomId = rows[0].id;

            const [playerSession] = await pool.query(
                'SELECT * FROM player_session WHERE player_id = ? AND room_id = ?',
                [playerId, roomId]
            );
            if (playerSession.length === 0) {
                socket.emit('join-error', { message: 'You are not registered in this room' });
                return;
            }

            if (!activeRooms.has(roomCode)) {
                activeRooms.set(roomCode, new GameRoom(roomId, roomCode));
            }

            const gameRoom = activeRooms.get(roomCode);

            if (gameRoom.players.size >= 10) {
                socket.emit('join-error', { message: 'Room is full' });
                return;
            }

            gameRoom.addPlayer(socket.id, { playerId, username, socketId: socket.id });
            // If rejoining mid-game, replace old socketId in turnOrder
            if (gameRoom.gameData) {
                const oldSid = gameRoom.turnOrder.find(sid => 
                    gameRoom.players.get(sid)?.playerId === playerId && sid !== socket.id);

                console.log('=== REJOIN DEBUG ===');
                console.log('turnOrder:', gameRoom.turnOrder);
                console.log('players map keys:', [...gameRoom.players.keys()]);
                console.log('getTurnOrderList():', gameRoom.getTurnOrderList());
                console.log('gameData:', gameRoom.gameData);
                console.log('===================');
                if (oldSid) {
                    const idx = gameRoom.turnOrder.indexOf(oldSid);
                    if (idx !== -1) gameRoom.turnOrder[idx] = socket.id;
                    gameRoom.players.delete(oldSid);
                    if (gameRoom.gameData.imposterSocketId === oldSid) {
                        gameRoom.gameData.imposterSocketId = socket.id;
                    }
                }
            }

            socket.join(roomCode);
            socket.roomCode = roomCode;
            socket.emit('join-success', {
                roomCode,
                roomId,
                players: gameRoom.getPlayerList(),
                isHost: gameRoom.hostId === socket.id
            });

            io.to(roomCode).emit('player-joined', {
                player: { playerId, username },
                players: gameRoom.getPlayerList(),
                playerCount: gameRoom.players.size
            });

            // If game already in progress (player rejoining game.php), resend their data
            console.log(`Rejoin check — status: ${gameRoom.status}, hasGameData: ${!!gameRoom.gameData}`);
            if (gameRoom.gameData) {
                const { word, category, imposterSocketId } = gameRoom.gameData;
                const isImposter = socket.id === imposterSocketId;
                socket.emit('game-data', {
                    isImposter,
                    category,
                    word: isImposter ? null : word
                });

                socket.emit('turn-order', { players: gameRoom.getTurnOrderList() });
                socket.emit('game-data', {
                    isImposter: socket.id === gameRoom.gameData.imposterSocketId,
                    category: gameRoom.gameData.category,
                    word: socket.id === gameRoom.gameData.imposterSocketId ? null : gameRoom.gameData.word
                });

                const currentSid = gameRoom.currentTurnSocketId();
                const currentPlayer = gameRoom.players.get(currentSid);
                if (currentPlayer) {
                    socket.emit('turn-update', {
                        currentTurnSocketId: currentSid,
                        currentTurnUsername: currentPlayer.username
                    });
                }
            }

            console.log(`Player ${username} joined room ${roomCode}`);
        } catch (error) {
            console.error('Join room error:', error);
            socket.emit('join-error', { message: 'Server error' });
        }
    });

    // ── LEAVE ROOM ───────────────────────────────────────
    socket.on('leave-room', () => handlePlayerLeave(socket));

    // ── START GAME ───────────────────────────────────────
    socket.on('start-game', async () => {
        try {
            const roomCode = socket.roomCode;
            if (!roomCode) return;

            const gameRoom = activeRooms.get(roomCode);
            if (!gameRoom) return;

            if (gameRoom.hostId !== socket.id) {
                socket.emit('error', { message: 'Only host can start the game' });
                return;
            }
            if (gameRoom.players.size < 3) {
                socket.emit('error', { message: 'Need at least 3 players to start' });
                return;
            }

            // Fetch word from PHP
            const wordData = await fetchWordFromPHP();
            if (!wordData) {
                socket.emit('error', { message: 'Failed to get a word. Try again.' });
                return;
            }
            const { word, category } = wordData;

            // Pick random imposter
            const socketIds = Array.from(gameRoom.players.keys());
            const imposterSocketId = socketIds[Math.floor(Math.random() * socketIds.length)];

            // Shuffle turn order
            gameRoom.turnOrder = shuffleArray(socketIds);
            gameRoom.currentTurnIndex = 0;
            gameRoom.playersWhoWent = new Set();
            gameRoom.votes = new Map();

            // Store game data
            gameRoom.gameData = { word, category, imposterSocketId };
            gameRoom.status = 'in-progress';

            await pool.query(
                'UPDATE rooms SET status = ? WHERE room_code = ?',
                ['in-progress', roomCode]
            );

            // Send role data privately to each player
            for (const [sid, player] of gameRoom.players) {
                const isImposter = sid === imposterSocketId;
                io.to(sid).emit('game-data', {
                    isImposter,
                    category,
                    word: isImposter ? null : word
                });
            }

            // Send turn order to everyone
            io.to(roomCode).emit('turn-order', {
                players: gameRoom.getTurnOrderList()
            });

            // Tell everyone game is starting (once)
            io.to(roomCode).emit('game-started', { message: 'Game is starting!' });


            // Announce first turn
            const firstSid = gameRoom.currentTurnSocketId();
            const firstPlayer = gameRoom.players.get(firstSid);

            io.to(roomCode).emit('chat-message', {
                username: '🎮 Game',
                message: `${firstPlayer.username}'s turn! Type a word related to the category.`,
                timestamp: Date.now(),
                isSystem: true
            });

            io.to(roomCode).emit('turn-update', {
                currentTurnSocketId: firstSid,
                currentTurnUsername: firstPlayer.username
            });

            // ── ADDED: kick off the first player's 30-second timer ──
            startTurnTimer(gameRoom, roomCode);
       

            console.log(`Game started in ${roomCode}. Imposter: ${gameRoom.players.get(imposterSocketId).username}. Word: ${word}`);

        } catch (error) {
            console.error('Start game error:', error);
            socket.emit('error', { message: 'Failed to start game' });
        }
    });

    // ── CHAT MESSAGE ─────────────────────────────────────
    socket.on('chat-message', (message) => {
        const roomCode = socket.roomCode;
        if (!roomCode) return;

        const gameRoom = activeRooms.get(roomCode);
        if (!gameRoom) return;

        const player = gameRoom.players.get(socket.id);

        if (gameRoom.status === 'in-progress') {
            // Enforce turn
            if (gameRoom.currentTurnSocketId() !== socket.id) {
                socket.emit('chat-error', { message: "It's not your turn!" });
                return;
            }

            // ── ADDED: server-side character limit enforcement ──
            if (typeof message !== 'string' || message.trim().length === 0) return;
            if (message.length > 128) {
                socket.emit('chat-error', { message: 'Your message exceeds the 128 character limit.' });
                return;
            }

            // ── ADDED: clear the running timer since this player submitted in time ──
            if (gameRoom.turnTimer) {
                clearTimeout(gameRoom.turnTimer);
                gameRoom.turnTimer = null;
            }

            // Broadcast the message
            io.to(roomCode).emit('chat-message', {
                username: player.username,
                message,
                timestamp: Date.now()
            });

            // Advance turn
            gameRoom.advanceTurn();

            // Check if everyone has gone
            if (gameRoom.allPlayersWent()) {
                // Switch to voting phase
                gameRoom.status = 'voting';

                io.to(roomCode).emit('chat-message', {
                    username: '🎮 Game',
                    message: 'Everyone has spoken. Time to vote!',
                    timestamp: Date.now(),
                    isSystem: true
                });

                io.to(roomCode).emit('start-voting', {
                    players: gameRoom.getTurnOrderList()
                });
                return;
            }

            // Announce next turn
            const nextSid = gameRoom.currentTurnSocketId();
            const nextPlayer = gameRoom.players.get(nextSid);

            io.to(roomCode).emit('chat-message', {
                username: 'Game',
                message: `${nextPlayer.username}'s turn!`,
                timestamp: Date.now(),
                isSystem: true
            });

            io.to(roomCode).emit('turn-update', {
                currentTurnSocketId: nextSid,
                currentTurnUsername: nextPlayer.username
            });

            // ── ADDED: start the next player's 30-second timer ──
            startTurnTimer(gameRoom, roomCode);

        } else {
            // Lobby free chat
            io.to(roomCode).emit('chat-message', {
                username: player.username,
                message,
                timestamp: Date.now()
            });
        }
    });

    // ── SUBMIT VOTE ──────────────────────────────────────
    socket.on('submit-vote', (data) => {
        const roomCode = socket.roomCode;
        if (!roomCode) return;

        const gameRoom = activeRooms.get(roomCode);
        if (!gameRoom || gameRoom.status !== 'voting') return;

        // Only count one vote per player
        if (gameRoom.votes.has(socket.id)) return;

        gameRoom.votes.set(socket.id, data.votedSocketId);

        console.log(`Vote in ${roomCode}: ${gameRoom.players.get(socket.id)?.username} voted for ${gameRoom.players.get(data.votedSocketId)?.username}`);

        // Check if everyone has voted
        if (gameRoom.votes.size >= gameRoom.players.size) {
            resolveVotes(gameRoom, roomCode);
        }
    });

    // ── ROUND CONTINUE ───────────────────────────────────────
    socket.on('round-continue', () => {
        const roomCode = socket.roomCode;
        const gameRoom = activeRooms.get(roomCode);
        if (!gameRoom || gameRoom.hostId !== socket.id) return;

        // Reset turn tracking but keep same order
        gameRoom.playersWhoWent = new Set();
        gameRoom.currentTurnIndex = 0;
        gameRoom.status = 'in-progress';

        io.to(roomCode).emit('round-continue', { players: gameRoom.getTurnOrderList() });

        const firstSid = gameRoom.currentTurnSocketId();
        const firstPlayer = gameRoom.players.get(firstSid);
        io.to(roomCode).emit('turn-update', {
            currentTurnSocketId: firstSid,
            currentTurnUsername: firstPlayer.username
        });
        startTurnTimer(gameRoom, roomCode);
    });

    // ── REQUEST VOTING ───────────────────────────────────────
    socket.on('request-voting', () => {
        const roomCode = socket.roomCode;
        const gameRoom = activeRooms.get(roomCode);
        if (!gameRoom || gameRoom.hostId !== socket.id) return;

        gameRoom.status = 'voting';
        io.to(roomCode).emit('go-to-voting', { players: gameRoom.getTurnOrderList() });
    });
    // ── DISCONNECT ───────────────────────────────────────
    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
        handlePlayerLeave(socket);
    });
});

// ── VOTE RESOLUTION ───────────────────────────────────────────────────────────
function resolveVotes(gameRoom, roomCode) {
    // Count votes per socketId
    const voteCounts = new Map(); // socketId -> count
    for (const votedSid of gameRoom.votes.values()) {
        voteCounts.set(votedSid, (voteCounts.get(votedSid) ?? 0) + 1);
    }

    // Find who got the most votes
    let maxVotes = 0;
    let mostVotedSid = null;
    for (const [sid, count] of voteCounts) {
        if (count > maxVotes) {
            maxVotes = count;
            mostVotedSid = sid;
        }
    }

    const { imposterSocketId, word } = gameRoom.gameData;
    const imposterPlayer = gameRoom.players.get(imposterSocketId);
    const imposterCaught = mostVotedSid === imposterSocketId;

    // Build vote count breakdown by username
    const voteCountsByName = {};
    for (const [sid, count] of voteCounts) {
        const p = gameRoom.players.get(sid);
        if (p) voteCountsByName[p.username] = count;
    }

    io.to(roomCode).emit('vote-results', {
        imposterUsername: imposterPlayer?.username ?? 'Unknown',
        imposterCaught,
        word,
        voteCounts: voteCountsByName
    });

    // Reset room status
    gameRoom.status = 'lobby';
    gameRoom.gameData = null;
    gameRoom.turnOrder = [];
    gameRoom.votes = new Map();
    gameRoom.playersWhoWent = new Set();
}

// ── PLAYER LEAVE ──────────────────────────────────────────────────────────────
function handlePlayerLeave(socket) {
    const roomCode = socket.roomCode;
    if (!roomCode) return;

    const gameRoom = activeRooms.get(roomCode);
    if (!gameRoom) return;

    const player = gameRoom.players.get(socket.id);

    // ── ADDED: if the disconnecting player was the active one, clear their timer ──
    if (gameRoom.currentTurnSocketId() === socket.id && gameRoom.turnTimer) {
        clearTimeout(gameRoom.turnTimer);
        gameRoom.turnTimer = null;
    }
    

    // Remove from turn order
    const turnIdx = gameRoom.turnOrder.indexOf(socket.id);
    if (turnIdx !== -1) {
        gameRoom.turnOrder.splice(turnIdx, 1);
        if (turnIdx < gameRoom.currentTurnIndex && gameRoom.currentTurnIndex > 0) {
            gameRoom.currentTurnIndex--;
        }
        if (gameRoom.turnOrder.length > 0) {
            gameRoom.currentTurnIndex = gameRoom.currentTurnIndex % gameRoom.turnOrder.length;
        }
    }

    gameRoom.removePlayer(socket.id);

    io.to(roomCode).emit('player-left', {
        player: player ? { playerId: player.playerId, username: player.username } : null,
        players: gameRoom.getPlayerList(),
        playerCount: gameRoom.players.size,
        newHost: gameRoom.hostId
    });

    // If game is running, check if remaining players all went (someone left mid-round)
    if (gameRoom.status === 'in-progress' && gameRoom.players.size > 0) {
        if (gameRoom.allPlayersWent()) {
            gameRoom.status = 'voting';
            io.to(roomCode).emit('start-voting', { players: gameRoom.getTurnOrderList() });
        } else {
            const nextSid = gameRoom.currentTurnSocketId();
            const nextPlayer = gameRoom.players.get(nextSid);
            if (nextPlayer) {
                io.to(roomCode).emit('turn-update', {
                    currentTurnSocketId: nextSid,
                    currentTurnUsername: nextPlayer.username
                });
                // ── ADDED: restart timer for the next player after a disconnect ──
                startTurnTimer(gameRoom, roomCode);
                
            }
        }
    }

    // If voting and now everyone has voted, resolve
    if (gameRoom.status === 'voting' && gameRoom.votes.size >= gameRoom.players.size) {
        resolveVotes(gameRoom, roomCode);
    }

    if (gameRoom.players.size === 0) {
        activeRooms.delete(roomCode);
        console.log(`Room ${roomCode} removed (empty)`);
    }
}

const PORT = process.env.PORT || 4000;
server.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
});