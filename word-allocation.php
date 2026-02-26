<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter</title>
    <script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
</head>
<body>

    <!-- ── GAME SCREEN ── -->
    <div id="game-screen">

        <div id="role-box">
            <p id="role-label"></p>
            <p id="category-label"></p>
            <p id="word-label"></p>
        </div>

        <div id="player-order">
            <h3>Turn Order</h3>
            <ul id="turn-list"></ul>
        </div>

        <div id="chat-area">
            <div id="turn-indicator"></div>

            <div id="timer-bar-container">
                <div id="timer-label">Time remaining: <span id="timer-seconds">30</span>s</div>
                <div id="timer-bar-track">
                    <div id="timer-bar-fill"></div>
                </div>
            </div>

            <div id="chat-messages"></div>
            <input type="text" id="chat-input" placeholder="Type your word..." disabled maxlength="128">
            <button id="send-btn" disabled>Send</button>

            <div id="char-counter">0 / 128</div>
            <div id="chat-messages"></div>  <!-- this is for css -->
        </div>

    </div>

    <!-- ── VOTING SCREEN ── -->
    <div id="voting-screen" style="display:none;">
        <h2>Vote for the Imposter!</h2>
        <p>Who do you think is the imposter?</p>
        <ul id="vote-list"></ul>
        <p id="vote-status"></p>
    </div>

    <!-- ── RESULTS SCREEN ── -->
    <div id="results-screen" style="display:none;">
        <h2>Results</h2>
        <p id="imposter-reveal"></p>
        <p id="result-word"></p>
        <div id="vote-breakdown">
            <h3>Votes</h3>
            <ul id="vote-breakdown-list"></ul>
        </div>
        <p id="result-outcome"></p>
        <button id="back-to-menu-btn">Back to Menu</button>
    </div>

    <script>
        let socket = null;
        let mySocketId = null;
        let myUsername = null;
        let allPlayers = []; // full player list for voting

        async function initGame() {
            try {
                const response = await fetch('includes/get-session-data.php');
                const sessionData = await response.json();

                if (!sessionData.success || !sessionData.room_code) {
                    alert('No active session. Redirecting...');
                    window.location.href = 'join-room.php';
                    return;
                }

                myUsername = sessionData.username;
                connectSocket(sessionData);

            } catch (err) {
                console.error('Init error:', err);
                alert('Failed to load game.');
                window.location.href = 'main-menu.html';
            }
        }

        function connectSocket(sessionData) {
            socket = io('http://localhost:4000', {
                transports: ['websocket', 'polling'],
                reconnection: true,
                reconnectionDelay: 1000,
                reconnectionAttempts: 5
            });

            socket.on('connect', () => {
                mySocketId = socket.id;

                // Rejoin the socket room so server knows we're here
                socket.emit('join-room', {
                    roomCode: sessionData.room_code,
                    playerId: sessionData.player_id,
                    username: sessionData.username
                });
            });

            socket.on('connect_error', (err) => {
                console.error('Socket error:', err);
                alert('Lost connection to server.');
            });

            // Receive role + word/category
            socket.on('game-data', (data) => {
                const roleLabel = document.getElementById('role-label');
                const categoryLabel = document.getElementById('category-label');
                const wordLabel = document.getElementById('word-label');

                if (data.isImposter) {
                    roleLabel.textContent = 'You are the IMPOSTER';
                    categoryLabel.textContent = `Category: ${data.category}`;
                    wordLabel.textContent = '(You do not know the word — blend in!)';
                } else {
                    roleLabel.textContent = 'You are a normie';
                    categoryLabel.textContent = `Category: ${data.category}`;
                    wordLabel.textContent = `Word: ${data.word}`;
                }
            });

            // Turn order list + whose turn it is
            socket.on('turn-update', (data) => {
                const isMyTurn = data.currentTurnSocketId === socket.id;
                setChatEnabled(isMyTurn);

                const indicator = document.getElementById('turn-indicator');
                indicator.textContent = isMyTurn
                    ? "Your turn — type a word!"
                    : `${data.currentTurnUsername}'s turn...`;

                // Highlight current player in turn list
                document.querySelectorAll('#turn-list li').forEach(li => {
                    li.classList.toggle('active-turn', li.dataset.socketId === data.currentTurnSocketId);
                });
            });

            // Build the turn order list when server sends it
            socket.on('turn-order', (data) => {
                allPlayers = data.players; // [{ socketId, username }, ...]
                const list = document.getElementById('turn-list');
                list.innerHTML = '';
                data.players.forEach((p, i) => {
                    const li = document.createElement('li');
                    li.textContent = `${i + 1}. ${p.username}`;
                    li.dataset.socketId = p.socketId;
                    list.appendChild(li);
                });
            });

            // Incoming chat message
            socket.on('chat-message', (data) => {
                appendMessage(data.username, data.message, data.isSystem);
            });

            socket.on('chat-error', (data) => {
                appendMessage('⚠️', data.message, true);
            });

            // Server says everyone's had their turn — switch to voting
            socket.on('start-voting', (data) => {
                allPlayers = data.players;
                showVotingScreen(data.players);
            });

            // All votes are in — show results
            socket.on('vote-results', (data) => {
                showResultsScreen(data);
            });

            // Player left mid-game
            socket.on('player-left', (data) => {
                updateTurnList(data.players);
            });

            // timer
            let timerInterval = null; // holds the setInterval so we can clear it

            socket.on('turn-timer-start', (data) => {
                const secs = data.durationMs / 1000;
                const fill = document.getElementById('timer-bar-fill');
                const label = document.getElementById('timer-seconds');
                const container = document.getElementById('timer-bar-container');

                // Reset to full instantly (no transition)
                fill.style.transition = 'none';
                fill.style.width = '100%';
                fill.style.background = '#4caf50';
                label.textContent = secs;

                // Force a reflow so the reset takes effect before the transition starts
                fill.offsetWidth;

                // Now set the transition and animate to 0 over the full duration
                fill.style.transition = `width ${secs}s linear, background ${secs}s linear`;
                fill.style.width = '0%';
                fill.style.background = '#e53935';

                // Only thing still needing JS: tick the seconds label
                let remaining = secs;
                const interval = setInterval(() => {
                    remaining--;
                    label.textContent = Math.max(0, remaining);
                    if (remaining <= 0) {
                        clearInterval(interval);
                        container.style.display = 'none';
                    }
                }, 1000); // only fires once per second instead of 4×
            });
            setupListeners();
        }

        // ── CHAT ──────────────────────────────────────────
        function setChatEnabled(enabled) {
            document.getElementById('chat-input').disabled = !enabled;
            document.getElementById('send-btn').disabled = !enabled;
            if (enabled) document.getElementById('chat-input').focus();
        }

        function setupListeners() {
            document.getElementById('send-btn').addEventListener('click', sendMessage);
            document.getElementById('chat-input').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });
            document.getElementById('back-to-menu-btn').addEventListener('click', () => {
                window.location.href = 'main-menu.html';
            });

            // update the char counter while user types
            document.getElementById('chat-input').addEventListener('input', () => {
                const input = document.getElementById('chat-input');
                const counter = document.getElementById('char-counter');
                const len = input.value.length;
                counter.textContent = `${len} / 128`;
                // Turn the counter red when at or over the limit
                counter.classList.toggle('over-limit', len >= 128);
            });
        }

        function sendMessage() {
            const input = document.getElementById('chat-input');
            const msg = input.value.trim();

            if (msg.length > 128) {
                appendMessage('Message too long (max 128 characters).', true);
                return;
            }

            if (msg) {
                socket.emit('chat-message', msg);
                input.value = '';

                const counter = document.getElementById('char-counter'); //reset the char counter after sending ══
                counter.textContent = '0 / 128';
                counter.classList.remove('over-limit');
            }
        }

        function appendMessage(username, message, isSystem = false) {
            const box = document.getElementById('chat-messages');
            const div = document.createElement('div');
            div.className = isSystem ? 'system-msg' : 'chat-msg';
            div.innerHTML = `<strong>${username}:</strong> ${message}`;
            box.appendChild(div);
            box.scrollTop = box.scrollHeight;
        }

        // ── VOTING ────────────────────────────────────────
        function showVotingScreen(players) {
            document.getElementById('game-screen').style.display = 'none';
            document.getElementById('voting-screen').style.display = 'block';

            const list = document.getElementById('vote-list');
            list.innerHTML = '';

            players.forEach(p => {
                // Can't vote for yourself
                if (p.socketId === mySocketId) return;

                const li = document.createElement('li');
                const btn = document.createElement('button');
                btn.textContent = p.username;
                btn.addEventListener('click', () => submitVote(p.socketId, p.username, btn));
                li.appendChild(btn);
                list.appendChild(li);
            });

            document.getElementById('vote-status').textContent = 'Waiting for all votes...';
        }

        function submitVote(votedSocketId, votedUsername, btn) {
            socket.emit('submit-vote', { votedSocketId });

            // Disable all vote buttons after voting
            document.querySelectorAll('#vote-list button').forEach(b => b.disabled = true);
            document.getElementById('vote-status').textContent = `You voted for ${votedUsername}. Waiting for others...`;
        }

        // ── RESULTS ───────────────────────────────────────
        function showResultsScreen(data) {
            document.getElementById('voting-screen').style.display = 'none';
            document.getElementById('results-screen').style.display = 'block';

            document.getElementById('imposter-reveal').textContent =
                `🕵️ The imposter was: ${data.imposterUsername}`;
            document.getElementById('result-word').textContent =
                `The word was: ${data.word}`;

            // Vote breakdown
            const breakdownList = document.getElementById('vote-breakdown-list');
            breakdownList.innerHTML = '';
            for (const [username, count] of Object.entries(data.voteCounts)) {
                const li = document.createElement('li');
                li.textContent = `${username}: ${count} vote${count !== 1 ? 's' : ''}`;
                breakdownList.appendChild(li);
            }

            // Outcome
            const outcome = document.getElementById('result-outcome');
            if (data.imposterCaught) {
                outcome.textContent = 'Innocents win! The imposter was caught.';
            } else {
                outcome.textContent = 'Imposter wins! Yall are cooked.';
            }
        }

        // ── HELPERS ───────────────────────────────────────
        function updateTurnList(players) {
            const list = document.getElementById('turn-list');
            list.innerHTML = '';
            players.forEach((p, i) => {
                const li = document.createElement('li');
                li.textContent = `${i + 1}. ${p.username}`;
                li.dataset.socketId = p.socketId;
                list.appendChild(li);
            });
        }

        window.addEventListener('DOMContentLoaded', initGame);
    </script>
</body>
</html>