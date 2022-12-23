function chatroom_check_updates() {
  let last_update_id = 0;
  let data = new FormData();
  data.append("action", "check_updates");
  data.append("post_id", caChat.postId);
  data.append("last_update_id", last_update_id);
  data.append("nonce", caChat.nonce);
  fetch(caChat.ajaxUrl, {
    method: "POST",
    body: data,
  })
    .then((response) => {
      return response.json();
    })
    .then((responseJson) => {
      if (responseJson !== null) {
        for (const chat of responseJson) {
          chatroom_get_chat_message_html(chat);
        }
      }
    });
}

// html template for chat message
function chatroom_get_chat_message_html(chat) {

    let message = `
    <div class="chat-message chat-message-${chat.id}">
      <span class="author-avatar">${chat.author_avatar}</span>
      <div class="message-container">
        <div class="message-header">
          <strong class="username">${chat.author_name}</strong>
          <span class="chat-message-date">${chat.message_time}</span>
        </div>
        <div class="message-content">
          ${chatroom_make_links(chat.contents)}
        </div>
      </div>
    </div>`;


  let chat_content = document.querySelector("#chat_content");

  let message_container = document.querySelector(".chat-message-" + chat.id);

  if (null === message_container) {
    chat_content.innerHTML += message;

    //message_container.append(message);

    chat_content.scrollTop = chat_content.scrollHeight;
  }
}

// Convert url to link
function chatroom_make_links(str) {
  return str.replace(
    /(https?:\/\/[^\s]+)/g,
    '<a class="message-link" href="$1" target="_blank">$1</a>'
  );
}

function chatroom_strip_slashes(str) {
  return (str + "").replace(/\\(.?)/g, function (s, n1) {
    switch (n1) {
      case "\\":
        return "\\";
      case "0":
        return "\u0000";
      case "":
        return "";
      default:
        return n1;
    }
  });
}

// Wait for the document to load before running the code
document.addEventListener("DOMContentLoaded", function () {
  var last_update_id = 0;
  chatroom_check_updates();

  // Listen for the user to press enter in the textbox and send the message
  document
    .querySelector("textarea.chat-text-entry")
    .addEventListener("keypress", function (event) {
      if (event.charCode == 13 || event.keyCode == 13) {
        sendChatMessage();
        return false;
      }
    });

  // Listen for the user to click the submit button and send the message
  document
    .querySelector("#submit_message")
    .addEventListener("click", function (e) {
      e.preventDefault();
      sendChatMessage();
    });
});

function sendChatMessage() {
  // Get the message to send from the text entry box
  let message = document.querySelector("textarea.chat-text-entry").value;

  // Clear the text entry box
  document.querySelector("textarea.chat-text-entry").value = "";

  // Create a FormData object
  let data = new FormData();

  // Add the action to send a message
  data.append("action", "send_message");

  // Add the post ID to send the message to
  data.append("post_id", caChat.postId);

  // Add the message to send
  data.append("message", message);

  // Add the nonce
  data.append("nonce", caChat.nonce);

  // Send the message
  fetch(caChat.ajaxUrl, {
    method: "POST",
    body: data,
  })
    .then((response) => {
      return response.json();
    })
    .then((responseJson) => {
      chatroom_check_updates();
    });
}

setInterval(chatroom_check_updates, 5000);
