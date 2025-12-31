const express = require("express");
const cors = require("cors");
// the http server is running
// This creates an Express (HTTP) server
const app = express();
app.use(
    cors({
        origin: "*"
    })
);
// This creates requests that accepts requests on the port 4000
const expressServer = app.listen(4000);
const socketio = require("socket.io");
// This creates a socket.io server on the Express server
const io = socketio(expressServer, {
    cors: {
        origin: "*"
    }
});

// Accepting the handshake
io.on("connect", socket => {
    console.log("User " + socket.id + " connected!");
    socket.emit("welcome", "Welcome to the chat!");
    socket.on("send-message", message =>{
        socket.broadcast.emit("receive-message", message);
    });
});
