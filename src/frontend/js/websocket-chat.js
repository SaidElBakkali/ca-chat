/**
 * It takes an event object, parses the data, and if the data type is chat, it appends the message to
 * the chat div
 * @param event - The event object that is passed to the callback function.
 */
const webSocketCallback = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'chat') {
        const chat = document.getElementById('chat');
        const message = document.createElement('div');
        message.innerHTML = data.message;
        chat.appendChild(message);
    }
};

/**
 * It creates a new WebSocket connection to the server, adds an event listener to the chat form, and
 * sends the message to the server when the form is submitted
 */
const webSocketChat = () => {
    const webSocket = new WebSocket('ws://localhost:3000');
    webSocket.onopen = () => {
        const chatForm = document.getElementById('chat-form');
        chatForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const message = document.getElementById('message');
            const data = {
                type: 'chat',
                message: message.value,
            };

            webSocket.send(JSON.stringify(data));
            message.value = '';
        });
    };
    webSocket.onmessage = webSocketCallback;
};

webSocketChat();