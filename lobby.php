<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
    <script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
    <link rel="stylesheet" href="lobby-style.css">
</head>
<body>
<!-- LOBBY -->
<div id="screen-lobby" class="screen">
    <div class="page">
        <div style="margin-bottom: 28px;">
            <div class="tag">room code</div>
            <div class="room-code-display" id="room-code"></div>
            <div style="font-size:0.8rem; color:var(--muted); margin-top:6px;">
                <span id="player-count">0</span>/10 players
            </div>
        </div>

        <div class="card">
            <div class="card-title">Players</div>
            <ul id="player-list"></ul>
        </div>

        <div class="card" id="host-controls" style="display:none;">
            <div class="card-title">Host Controls</div>
            <button class="btn btn-red btn-full" id="start-game-btn">Start Game</button>
            <div style="font-size:0.75rem; color:var(--muted); margin-top:8px; text-align:center;">Need at least 3 players</div>
        </div>

        <div class="card">
            <div class="card-title">Chat</div>
            <div id="lobby-chat-messages"></div>
            <div class="input-row">
                <input type="text" id="lobby-chat-input" placeholder="Say something...">
                <button class="btn btn-ghost" id="lobby-send-btn">Send</button>
            </div>
        </div>

        <button class="btn btn-ghost btn-full" id="leave-room-btn" style="margin-top:8px;">Leave Room</button>
    </div>
</div>

<!-- GAME -->
<div id="screen-game" class="screen">
    <div class="page">
        <div id="role-box">
            <div class="role-eyebrow" id="role-eyebrow"></div>
            <div id="role-label"></div>
            <div id="category-label"></div>
            <div id="word-label"></div>
        </div>

        <div class="card">
            <div class="card-title">Turn Order</div>
            <ul id="turn-list"></ul>
        </div>

        <div class="card">
            <div id="turn-indicator">Waiting...</div>
            <div id="game-chat-messages"></div>
            <div class="chat-input-wrap">
                <input type="text" id="game-chat-input" placeholder="Wait for your turn..." maxlength="128" disabled>
                <button class="btn btn-red" id="game-send-btn" disabled>Send</button>
            </div>
            <div id="char-counter">0 / 128</div>
        </div>

        <div id="round-end-actions">
            <button class="btn btn-green" id="continue-btn" style="flex:1">🔄 Keep Discussing</button>
            <button class="btn btn-red" id="vote-btn" style="flex:1">🗳️ Start Voting</button>
        </div>
    </div>
</div>

<!-- VOTING -->
<div id="screen-voting" class="screen">
    <div class="page">
        <div class="tag">voting phase</div>
        <h1>Who's the<br>Imposter?</h1>
        <ul id="vote-list"></ul>
        <p id="vote-status"></p>
    </div>
</div>

<!-- RESULTS -->
<div id="screen-results" class="screen">
    <div class="page">
        <div class="tag">results</div>
        <div id="result-outcome-hero" class="result-hero"></div>
        <div class="card" style="margin-top:16px;">
            <div id="imposter-reveal" style="margin-bottom:6px; font-size:1rem;"></div>
            <div id="result-word" style="font-size:0.85rem; color:var(--muted);"></div>
        </div>
        <div class="card">
            <div class="card-title">Vote Breakdown</div>
            <ul id="vote-breakdown-list"></ul>
        </div>
        <button class="btn btn-ghost btn-full" id="back-to-menu-btn">Back to Menu</button>
    </div>
</div>

<script>
    let socket = null;
    let mySocketId = null;
    let myUsername = null;
    let isHost = false;
    let allPlayers = [];

    // ── SCREEN MANAGER ────────────────────────────────────────────────
    function showScreen(id) {
        document.querySelectorAll('.screen, #screen-loading').forEach(s => {
            s.classList.remove('active');
            s.style.display = 'none';
        });
        const el = document.getElementById(id);
        el.style.display = 'block';
        el.classList.add('active');
    }

    // ── INIT ──────────────────────────────────────────────────────────
    async function init() {
        try {
            const res = await fetch('includes/get-session-data.php');
            const session = await res.json();

            if (!session.success || !session.room_code) {
                alert('No active session. Please join a room first.');
                window.location.href = 'join-room.php';
                return;
            }

            myUsername = session.username;
            connectSocket(session);

        } catch (err) {
            console.error('Init error:', err);
            alert('Failed to connect.');
            window.location.href = 'main-menu.html';
        }
    }

    // ── SOCKET ────────────────────────────────────────────────────────
    function connectSocket(session) {
        socket = io('http://localhost:4000', {
            transports: ['websocket', 'polling'],
            reconnection: true,
            reconnectionDelay: 1000,
            reconnectionAttempts: 5
        });

        socket.on('connect', () => {
            mySocketId = socket.id;
            socket.emit('join-room', {
                roomCode: session.room_code,
                playerId: session.player_id,
                username: session.username
            });
        });

        socket.on('connect_error', () => alert('Lost connection to server.'));

        // ── LOBBY EVENTS ──────────────────────────────────────────────
        socket.on('join-success', (data) => {
            isHost = data.isHost;
            document.getElementById('room-code').textContent = data.roomCode;
            document.getElementById('player-count').textContent = data.players.length;
            updatePlayerList(data.players);
            if (data.isHost) document.getElementById('host-controls').style.display = 'block';
            showScreen('screen-lobby');
        });

        socket.on('join-error', (data) => {
            alert(data.message);
            window.location.href = 'join-room.php';
        });

        socket.on('player-joined', (data) => {
            updatePlayerList(data.players);
            document.getElementById('player-count').textContent = data.playerCount;
            notify(`${data.player.username} joined`);
        });

        socket.on('player-left', (data) => {
            if (data.players) {
                updatePlayerList(data.players);
                document.getElementById('player-count').textContent = data.playerCount;
            }
            if (data.player) notify(`${data.player.username} left`);
            if (data.newHost === socket.id) {
                isHost = true;
                document.getElementById('host-controls').style.display = 'block';
                notify('You are now the host');
            }
            // If in game, update turn list too
            if (data.players) buildTurnList(data.players.map(p => ({ socketId: p.socketId, username: p.username })));
        });

        // ── GAME EVENTS ───────────────────────────────────────────────
        let cachedGameData = null;

        socket.on('game-started', () => {
            showScreen('screen-game');
            document.getElementById('game-chat-messages').innerHTML = '';
            if (cachedGameData) {
                applyGameData(cachedGameData);
                cachedGameData = null;
            }
        });

        socket.on('game-data', (data) => {
            console.log('game-data received:', data);
            const inGame = document.getElementById('screen-game').classList.contains('active');
            if (inGame) {
                applyGameData(data);
            } else {
                cachedGameData = data;
            }
        });

        socket.on('turn-order', (data) => {
            allPlayers = data.players;
            buildTurnList(data.players);
        });

        socket.on('turn-update', (data) => {
            updateTurnIndicator(data.currentTurnSocketId, data.currentTurnUsername);
            highlightActiveTurn(data.currentTurnSocketId);
        });

        socket.on('chat-message', (data) => {
            // Route to correct chat box
            const inLobby = document.getElementById('screen-lobby').classList.contains('active');
            if (inLobby) {
                appendLobbyMessage(data.username, data.message);
            } else {
                appendGameMessage(data.username, data.message, data.isSystem);
                if (!data.isSystem) markPlayerWent(data.username);
            }
        });

        socket.on('chat-error', (data) => appendGameMessage('⚠️', data.message, true));

        socket.on('start-voting', (data) => {
            allPlayers = data.players;
            showRoundEndActions(data.players);
        });

        socket.on('round-continue', (data) => {
            resetWentMarks();
            hideRoundEndActions();
            buildTurnList(data.players);
            appendGameMessage('🎮 Game', 'Another round — same order!', true);
        });

        socket.on('go-to-voting', (data) => {
            allPlayers = data.players;
            showVotingScreen(data.players);
        });

        socket.on('vote-results', (data) => showResultsScreen(data));

        socket.on('error', (data) => alert(data.message));

        setupListeners();
    }

    // ── LOBBY HELPERS ─────────────────────────────────────────────────
    function updatePlayerList(players) {
        const list = document.getElementById('player-list');
        list.innerHTML = '';
        players.forEach((p, i) => {
            const li = document.createElement('li');
            li.innerHTML = `<span class="dot"></span> ${p.username} ${i === 0 ? '<span class="host-badge">host</span>' : ''}`;
            list.appendChild(li);
        });
    }

    function appendLobbyMessage(username, message) {
        const box = document.getElementById('lobby-chat-messages');
        const div = document.createElement('div');
        div.className = 'lobby-msg';
        div.innerHTML = `<strong>${username}:</strong> ${message}`;
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    // ── GAME HELPERS ──────────────────────────────────────────────────
    function applyGameData(data) {
        const box = document.getElementById('role-box');
        box.className = data.isImposter ? 'imposter' : 'normie';
        document.getElementById('role-eyebrow').textContent = data.isImposter ? 'your role' : 'your role';
        document.getElementById('role-label').textContent = data.isImposter ? 'IMPOSTER' : 'NORMIE';
        document.getElementById('category-label').textContent = `Category: ${data.category}`;
        document.getElementById('word-label').textContent = data.isImposter
            ? "You don't know the word — blend in!"
            : `Word: ${data.word}`;
    }

    function buildTurnList(players) {
        const list = document.getElementById('turn-list');
        list.innerHTML = '';
        players.forEach((p, i) => {
            const li = document.createElement('li');
            li.textContent = `${i + 1}. ${p.username}`;
            li.dataset.socketId = p.socketId;
            li.dataset.username = p.username;
            list.appendChild(li);
        });
    }

    function highlightActiveTurn(activeSocketId) {
        document.querySelectorAll('#turn-list li').forEach(li => {
            li.classList.toggle('active-turn', li.dataset.socketId === activeSocketId);
        });
    }

    function markPlayerWent(username) {
        document.querySelectorAll('#turn-list li').forEach(li => {
            if (li.dataset.username === username && !li.classList.contains('active-turn')) {
                li.classList.add('went');
            }
        });
    }

    function resetWentMarks() {
        document.querySelectorAll('#turn-list li').forEach(li => li.classList.remove('went', 'active-turn'));
    }

    function updateTurnIndicator(currentSocketId, currentUsername) {
        const indicator = document.getElementById('turn-indicator');
        const input = document.getElementById('game-chat-input');
        const btn = document.getElementById('game-send-btn');

        if (currentSocketId === mySocketId) {
            indicator.textContent = "✏️ Your turn — type a clue!";
            indicator.className = 'your-turn';
            input.disabled = false;
            input.placeholder = 'Type a word related to the category...';
            btn.disabled = false;
            input.focus();
        } else {
            indicator.textContent = `⏳ ${currentUsername}'s turn...`;
            indicator.className = '';
            input.disabled = true;
            input.placeholder = 'Wait for your turn...';
            btn.disabled = true;
        }
    }

    function appendGameMessage(username, message, isSystem = false) {
        const box = document.getElementById('game-chat-messages');
        const div = document.createElement('div');
        div.className = isSystem ? 'system-msg' : 'chat-msg';
        div.innerHTML = isSystem ? `${username} ${message}` : `<strong>${username}:</strong> ${message}`;
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    function showRoundEndActions(players) {
        document.getElementById('game-chat-input').disabled = true;
        document.getElementById('game-send-btn').disabled = true;
        document.getElementById('turn-indicator').textContent = 'Everyone has spoken!';
        document.getElementById('turn-indicator').className = '';
        document.getElementById('round-end-actions').classList.add('visible');

        if (!isHost) {
            document.getElementById('continue-btn').disabled = true;
            document.getElementById('vote-btn').disabled = true;
            appendGameMessage('🎮 Game', 'Waiting for host to decide...', true);
        } else {
            appendGameMessage('🎮 Game', 'Keep discussing or start voting?', true);
        }
    }

    function hideRoundEndActions() {
        document.getElementById('round-end-actions').classList.remove('visible');
        document.getElementById('continue-btn').disabled = false;
        document.getElementById('vote-btn').disabled = false;
    }

    // ── VOTING ────────────────────────────────────────────────────────
    function showVotingScreen(players) {
        showScreen('screen-voting');
        const list = document.getElementById('vote-list');
        list.innerHTML = '';
        players.forEach(p => {
            if (p.socketId === mySocketId) return;
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.textContent = p.username;
            btn.addEventListener('click', () => {
                socket.emit('submit-vote', { votedSocketId: p.socketId });
                document.querySelectorAll('#vote-list button').forEach(b => b.disabled = true);
                document.getElementById('vote-status').textContent = `Voted for ${p.username}. Waiting...`;
            });
            li.appendChild(btn);
            list.appendChild(li);
        });
        document.getElementById('vote-status').textContent = 'Waiting for all votes...';
    }

    // ── RESULTS ───────────────────────────────────────────────────────
    function showResultsScreen(data) {
        showScreen('screen-results');
        const caught = data.imposterCaught;
        const hero = document.getElementById('result-outcome-hero');
        hero.textContent = caught ? 'Innocents Win!' : 'Imposter Wins!';
        hero.className = `result-hero ${caught ? 'win' : 'lose'}`;
        document.getElementById('imposter-reveal').textContent = `🕵️ Imposter: ${data.imposterUsername}`;
        document.getElementById('result-word').textContent = `The word was: ${data.word}`;

        const list = document.getElementById('vote-breakdown-list');
        list.innerHTML = '';
        for (const [username, count] of Object.entries(data.voteCounts)) {
            const li = document.createElement('li');
            li.innerHTML = `<span>${username}</span><span>${count} vote${count !== 1 ? 's' : ''}</span>`;
            list.appendChild(li);
        }
    }

    // ── LISTENERS ─────────────────────────────────────────────────────
    function setupListeners() {
        // Lobby
        document.getElementById('start-game-btn').addEventListener('click', () => socket.emit('start-game'));
        document.getElementById('lobby-send-btn').addEventListener('click', sendLobbyMessage);
        document.getElementById('lobby-chat-input').addEventListener('keypress', e => { if (e.key === 'Enter') sendLobbyMessage(); });
        document.getElementById('leave-room-btn').addEventListener('click', () => {
            if (confirm('Leave room?')) {
                socket.emit('leave-room');
                socket.disconnect();
                window.location.href = 'main-menu.php';
            }
        });

        // Game chat
        document.getElementById('game-send-btn').addEventListener('click', sendGameMessage);
        document.getElementById('game-chat-input').addEventListener('keypress', e => { if (e.key === 'Enter') sendGameMessage(); });
        document.getElementById('game-chat-input').addEventListener('input', () => {
            const len = document.getElementById('game-chat-input').value.length;
            const counter = document.getElementById('char-counter');
            counter.textContent = `${len} / 128`;
            counter.classList.toggle('over-limit', len >= 128);
        });

        // Round end
        document.getElementById('continue-btn').addEventListener('click', () => {
            socket.emit('round-continue');
            hideRoundEndActions();
        });
        document.getElementById('vote-btn').addEventListener('click', () => {
            socket.emit('request-voting');
            hideRoundEndActions();
        });

        // Results
        document.getElementById('back-to-menu-btn').addEventListener('click', () => {
            window.location.href = 'main-menu.html';
        });
    }

    function sendLobbyMessage() {
        const input = document.getElementById('lobby-chat-input');
        const msg = input.value.trim();
        if (msg) { socket.emit('chat-message', msg); input.value = ''; }
    }

    function sendGameMessage() {
        const input = document.getElementById('game-chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        if (msg.length > 128) { appendGameMessage('⚠️', 'Too long (max 128 chars)', true); return; }
        socket.emit('chat-message', msg);
        input.value = '';
        document.getElementById('char-counter').textContent = '0 / 128';
    }

    function notify(msg) {
        const div = document.createElement('div');
        div.className = 'notif';
        div.textContent = msg;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 3000);
    }

    window.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>