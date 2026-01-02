document.addEventListener("DOMContentLoaded", () => {
    const joinBtn = document.getElementById("submit");
    joinBtn.addEventListener("click", joinRoom);
    const socket = io("http://localhost:4000");
    socket.on("connect", () =>{
        console.log("the script has been added " + socket.id);
    });
});
function joinRoom(){
    const roomBox = document.getElementById("roomBox");
    roomId = roomBox.value;
    if(roomId == ""){
        return false;
    } else {
        roomObj = {"room_id": roomId};
        fetch("includes/join-room.inc.php", {
            method: "POST",
            headers: {"Content-type": "application/json; charset=utf-8"},
            body: JSON.stringify(roomObj)
            }
        )
        .then(function(response){
            data = response.json();
            return data;
        })
        .then(function(data){
            validated = data["validated"];
            if(validated === true){
                console.log("Yippeeeee");
            } else {
                console.log("Nope");
            }
        });
    }
}