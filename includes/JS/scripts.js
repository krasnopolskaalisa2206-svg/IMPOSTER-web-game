document.addEventListener("DOMContentLoaded", () => {
    const socket = io("http://localhost:4000");
    console.log("the script has been added " + socket.id)
});