<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
    <script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
    <link rel="stylesheet" href="lobby-style.css">
    <script src="sfxmanager.js"></script>
</head>
<body class="phase-lobby">

<!-- LOBBY -->
<div id="screen-lobby" class="screen">
    <div class="outer-border">
        <div class="inner-border">

            <div class="page">
                <div style="margin-bottom: 28px; text-align:center;">
                    <div class="tag">room code</div>
                    <div class="room-code-display" id="room-code"></div>
                    <div style="font-size:1rem; color:rgba(255,255,255,0.5); margin-top:6px;">
                        <span id="player-count">0</span>/10 players
                    </div>
                </div>
                <div class="card">
                    <div class="card-title">Players</div>
                    <ul id="player-list"></ul>
                </div>
                <div class="card" id="host-controls" style="display:none;">
                    <div class="card-title">Host Only</div>
                    <button class="btn btn-red btn-full" id="start-game-btn">Start Game</button>
                    <div style="font-size:0.9rem; color:rgba(255,255,255,0.5); margin-top:14px; text-align:center;">Need at least 3 players</div>
                </div>
                <button class="btn btn-ghost btn-full" id="leave-room-btn" style="margin-top:8px;">Leave Room</button>
            </div>

<!-- ── SHARED CHAT (floats over every phase except results) ── -->
            <div id="chat">
                <h3>Chat</h3>
                <div id="chat-messages"></div>
                <input type="text" id="chat-input" maxlength="64" placeholder="Say hello to your buddies! :3">
                <button id="send-chat-btn">Send</button>
            </div>
        </div>
    </div>
</div>

<!-- GAME -->
<div id="screen-game" class="screen">
    <div class="outer-border">
        <div class="inner-border">

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
                    <div id="char-counter">0 / 64</div>
                </div>
                <div id="round-end-actions">
                    <button class="btn btn-green" id="continue-btn" style="flex:1">Keep Discussing</button>
                    <button class="btn btn-red" id="vote-btn" style="flex:1">Start Voting</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- VOTING -->
<div id="screen-voting" class="screen">
    <div class="outer-border">
        <div class="inner-border">
            <div class="page">
                <div class="tag">voting phase</div>
                <h1>Who's the<br>Imposter?</h1>
                <ul id="vote-list"></ul>
                <p id="vote-status"></p>
            </div>
        </div>
    </div>
</div>

<!-- IMPOSTER GUESS (shown after voting catches the imposter) -->
<div id="screen-guess" class="screen">
    <div class="outer-border">
        <div class="inner-border">

            <div class="page">
                <div class="tag">last chance</div>
                <h1>Imposter<br>Caught!</h1>
                <div class="card" id="guess-prompt-card">
                    <div class="card-title">Imposter — type the word in chat to win!</div>
                    <p id="guess-status" style="font-size:1rem; color:rgba(255,255,255,0.7); margin-top:8px;"></p>
                </div>

                <div class="card" id="guess-waiting-card" style="display:none;">
                    <div class="card-title">Waiting for the imposter's guess...</div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- RESULTS -->
<div id="screen-results" class="screen">
    <div class="outer-border">
        <div class="inner-border">
            <div class="page">
                <div class="tag">results</div>
                <div id="result-outcome-hero" class="result-hero"></div>
                <div class="card" style="margin-top:16px;">
                    <div id="imposter-reveal" style="margin-bottom:6px; font-size:1.2rem;"></div>
                    <div id="result-word" style="font-size:1rem; color:rgba(255,255,255,0.5);"></div>
                </div>
                <div class="card">
                    <div class="card-title">Vote Breakdown</div>
                    <ul id="vote-breakdown-list"></ul>
                </div>
                <button class="btn btn-ghost btn-full" id="back-to-menu-btn">Back to Menu</button>
                <button class="btn btn-ghost btn-full" id="play-again-btn">Play Again!</button>
            </div>
        </div>
    </div>
</div>

<script>
let socket = null;
let mySocketId = null;
let myUsername = null;
let isHost = false;
let allPlayers = [];
let amIImposter = false;
let currentPhase = 'lobby'; // lobby | game | voting | guess | results

// ── SCREEN MANAGER ────────────────────────────────────────────────
function showScreen(id) {
    document.querySelectorAll('.screen').forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none';
    });
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.classList.add('active');

    currentPhase = id.replace('screen-', '');
    document.body.className = 'phase-' + currentPhase;

    // Hide chat on results, show everywhere else
    const chat = document.getElementById('chat');
    const targetInnerBorder = el.querySelector('.inner-border');
    if (targetInnerBorder) targetInnerBorder.appendChild(chat);
    chat.style.display = (currentPhase === 'results' || currentPhase === 'voting') ? 'none' : 'flex';

    // Update chat input per phase
    const chatInput = document.getElementById('chat-input');
    if (currentPhase === 'game') {
        chatInput.placeholder = 'Wait for your turn...';
        chatInput.maxLength = 64;
        chatInput.disabled = true;
    } else if (currentPhase === 'guess') {
        if (amIImposter) {
            chatInput.placeholder = 'Type your word guess here...';
            chatInput.maxLength = 64;
            chatInput.disabled = false;
            chatInput.focus();
        } else {
            chatInput.placeholder = 'Waiting for imposter to guess...';
            chatInput.disabled = true;
        }
    } else {
        chatInput.placeholder = 'Say something...';
        chatInput.removeAttribute('maxlength');
        chatInput.disabled = false;
    }
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
        allPlayers = data.players;
        updatePlayerList(data.players);
        if (data.isHost) document.getElementById('host-controls').style.display = 'block';
        showScreen('screen-lobby');
    });

    socket.on('join-error', (data) => {
        alert(data.message);
        window.location.href = 'join-room.php';
    });

    socket.on('player-joined', (data) => {
        allPlayers = data.players;
        updatePlayerList(data.players);
        document.getElementById('player-count').textContent = data.playerCount;
        notify(`${data.player.username} joined`);
    });

    socket.on('player-left', (data) => {
        if (data.players) {
            allPlayers = data.players;
            updatePlayerList(data.players);
            document.getElementById('player-count').textContent = data.playerCount;
        }
        if (data.player) notify(`${data.player.username} left`);
        if (data.newHost === socket.id) {
            isHost = true;
            document.getElementById('host-controls').style.display = 'block';
            notify('You are now the host');
        }
        if (data.players) buildTurnList(data.players.map(p => ({ socketId: p.socketId, username: p.username })));
    });

    // ── GAME EVENTS ───────────────────────────────────────────────
    let cachedGameData = null;

    socket.on('game-started', () => {
        showScreen('screen-game');
        appendChatMessage(null, '🎮 Game started!', true);
        if (cachedGameData) {
            applyGameData(cachedGameData);
            cachedGameData = null;
        }
    });

    socket.on('game-data', (data) => {
        console.log('game-data received:', data);
        amIImposter = data.isImposter;
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
        newNotification.currentTime = 0
        textMessage.play()
        const isSystem = !!data.isSystem;
        appendChatMessage(isSystem ? null : data.username, data.message, isSystem);
        if (!isSystem && currentPhase === 'game') {
            markPlayerWent(data.username);
        }
    });

    socket.on('chat-error', (data) => appendChatMessage(data.message, true));

    socket.on('start-voting', (data) => {
        allPlayers = data.players;
        showRoundEndActions(data.players);
    });

    socket.on('round-continue', (data) => {
        resetWentMarks();
        hideRoundEndActions();
        buildTurnList(data.players);
        appendChatMessage(null, 'Another round — same order!', true);
    });

    socket.on('go-to-voting', (data) => {
        allPlayers = data.players;
        showVotingScreen(data.players);
    });

    // vote-results: if imposter caught, go to guess phase first
    socket.on('vote-results', (data) => {
        if (data.imposterCaught) {
            showGuessScreen(data);
        } else {
            showResultsScreen(data);
        }
    });

    // Server resolves the guess and sends final result to everyone
    socket.on('guess-result', (data) => {
        showResultsScreen(data);
    });

    socket.on('error', (data) => alert(data.message));

    setupListeners();
}

// ── SHARED CHAT HELPER ────────────────────────────────────────────
function appendChatMessage(username, message, isSystem = false) {
    const box = document.getElementById('chat-messages');
    if (!box) return;

    if (isSystem) {
        const div = document.createElement('div');
        div.className = 'chat-message system';
        div.textContent = (username ? username + ' ' : '') + message;
        box.appendChild(div);
    } else {
        const colours = ['green', 'red', 'blue', 'pink', 'orange', 'purple', 'brown'];
        const playerIndex = allPlayers.findIndex(p => p.username === username);
        const colour = colours[playerIndex >= 0 ? playerIndex % colours.length : 0];

        const wrapper = document.createElement('div');
        wrapper.className = 'chat-wrapper';

        const nameEl = document.createElement('div');
        nameEl.className = 'chat-username';
        nameEl.textContent = username;

        const bubble = document.createElement('div');
        bubble.className = `chat-message ${colour}`;
        bubble.textContent = message;

        wrapper.appendChild(nameEl);
        wrapper.appendChild(bubble);
        box.appendChild(wrapper);
    }

    box.scrollTop = box.scrollHeight;
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

// ── GAME HELPERS ──────────────────────────────────────────────────
function applyGameData(data) {
    const box = document.getElementById('role-box');
    box.className = data.isImposter ? 'imposter' : 'normie';
    document.getElementById('role-eyebrow').textContent = 'your role';
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
    newNotification.currentTime = 0
    newNotification.play()
    const indicator = document.getElementById('turn-indicator');
    const chatInput = document.getElementById('chat-input');

    if (currentSocketId === mySocketId) {
        indicator.textContent = "Your turn: type a clue!";
        indicator.className = 'your-turn';
        chatInput.disabled = false;
        chatInput.placeholder = 'Type a word related to the category...';
        chatInput.focus();
    } else {
        indicator.textContent = ` ${currentUsername}'s turn...`;
        indicator.className = '';
        chatInput.disabled = true;
        chatInput.placeholder = 'Wait for your turn...';
    }
}

function showRoundEndActions(players) {
    document.getElementById('chat-input').disabled = true;
    document.getElementById('turn-indicator').textContent = 'Everyone has spoken!';
    document.getElementById('turn-indicator').className = '';
    document.getElementById('round-end-actions').classList.add('visible');

    if (!isHost) {
        document.getElementById('continue-btn').disabled = true;
        document.getElementById('vote-btn').disabled = true;
        document.getElementById('vote-btn').style.display = 'none';
        document.getElementById('continue-btn').style.display = 'none';
        appendChatMessage(null, 'Waiting for host to decide...', true);
    } else {
        appendChatMessage(null, 'Keep discussing or start voting?', true);
    }
}

function hideRoundEndActions() {
    document.getElementById('round-end-actions').classList.remove('visible');
    document.getElementById('continue-btn').disabled = false;
    document.getElementById('vote-btn').disabled = false;
    document.getElementById('continue-btn').textContent = 'Keep Discussing';
    document.getElementById('vote-btn').textContent = 'Start Voting';
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
    appendChatMessage(null, 'Voting has started!', true);
}

// ── GUESS PHASE ───────────────────────────────────────────────────
function showGuessScreen(data) {
    showScreen('screen-guess');

    if (amIImposter) {
        document.getElementById('guess-prompt-card').style.display = 'block';
        document.getElementById('guess-waiting-card').style.display = 'none';
        document.getElementById('guess-status').textContent = data.category ? `Category was: ${data.category}` : '';
        appendChatMessage(null, 'You were caught! Type the word in chat to still win!', true);
    } else {
        document.getElementById('guess-prompt-card').style.display = 'none';
        document.getElementById('guess-waiting-card').style.display = 'block';
        appendChatMessage(null, 'Imposter caught! Waiting for their word guess...', true);
    }
}

// ── RESULTS ───────────────────────────────────────────────────────
function showResultsScreen(data) {
    showScreen('screen-results');

    const caught = data.imposterCaught;
    const guessCorrect = data.guessCorrect;

    let outcomeText;
    if (!caught) {
        outcomeText = 'Imposter Wins!';
    } else if (guessCorrect) {
        outcomeText = 'Imposter Wins! (Guessed correctly)';
    } else {
        outcomeText = 'Normies Win!';
    }

    const imposterWon = !caught || guessCorrect;
    const hero = document.getElementById('result-outcome-hero');
    hero.textContent = outcomeText;
    hero.className = `result-hero ${imposterWon ? 'lose' : 'win'}`;

    document.getElementById('imposter-reveal').textContent = `Imposter: ${data.imposterUsername}`;
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
    document.getElementById('start-game-btn').addEventListener('click', () => socket.emit('start-game'));
    document.getElementById('leave-room-btn').addEventListener('click', () => {
        if (confirm('Leave room?')) {
            socket.emit('leave-room');
            socket.disconnect();
            window.location.href = 'main-menu.php';
        }
    });

    // Unified chat send
    document.getElementById('send-chat-btn').addEventListener('click', sendChatMessage);
    document.getElementById('chat-input').addEventListener('keypress', e => {
        if (e.key === 'Enter') sendChatMessage();
    });

    // Char counter (game phase only)
    document.getElementById('chat-input').addEventListener('input', () => {
        if (currentPhase !== 'game') return;
        const len = document.getElementById('chat-input').value.length;
        const counter = document.getElementById('char-counter');
        counter.textContent = `${len} / 64`;
        counter.classList.toggle('over-limit', len >= 64);
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
        window.location.href = 'main-menu.php';
    });
    document.getElementById('play-again-btn').addEventListener('click', () => {
        window.location.href = 'lobby.php';
    });
}

function sendChatMessage() {
    const input = document.getElementById('chat-input');
    const msg = input.value.trim();
    if (!msg) return;

    if (currentPhase === 'game') {
        if (msg.length > 64) {
            appendChatMessage(null, 'Too long (max 64 chars)', true);
            return;
        }
        socket.emit('chat-message', msg);
        document.getElementById('char-counter').textContent = '0 / 64';
    } else if (currentPhase === 'guess') {
        // Imposter submits their word guess via a dedicated socket event
        socket.emit('imposter-guess', { guess: msg });
        input.disabled = true;
        appendChatMessage(null, `Guess submitted: "${msg}"`, true);
    } else {
        // Lobby or voting — normal chat
        socket.emit('chat-message', msg);
    }

    input.value = '';
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