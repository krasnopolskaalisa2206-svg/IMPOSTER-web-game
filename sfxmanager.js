const mouseClick = new Audio("sfx/mouse_click.mp3")
const newNotification = new Audio("sfx/new_notification.mp3")
const textMessage = new Audio("sfx/text_message.mp3")

document.addEventListener("DOMContentLoaded", () => {
    console.log("manager hit")
    document.querySelectorAll("button").forEach(button => {
        console.log("button hit")
        button.addEventListener("click", () => {
            console.log("event hit")
            mouseClick.currentTime = 0
            mouseClick.play()
        })
    })
})