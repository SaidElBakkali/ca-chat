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
    .then((response) => response.json())
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

  /* Selecting the chat content element. */
  let chat_content = document.querySelector("#chat_content");
  /* This is checking if the message container is null. */
  let message_container = document.querySelector(".chat-message-" + chat.id);
  // Get the last message in the chat
  let last_message = chat_content.lastElementChild;

  /* This is checking if the message is private and if it is not, it is checking if the message container is null.
  If it is null, it is adding the message to the chat content and scrolling to the bottom of the chat content. */
  if (chat.is_private) {
    if (!last_message.classList.contains("chat-message-0")) {
      chat_content.innerHTML += message;

      chat_content.scrollTop = chat_content.scrollHeight;
    }
  }

  /* Checking if the message is private and if it is not, it is checking if the message container is null. If it is null, it is adding the message to the chat content and scrolling to the bottom of the
  chat content. */
  if (!chat.is_private) {
    if (null === message_container) {
      chat_content.innerHTML += message;

      chat_content.scrollTop = chat_content.scrollHeight;
    }
  }
}

// Convert url to link.
function chatroom_make_links(str) {
  if (typeof str === "string") {
    return str.replace(
      /(https?:\/\/[^\s]+)/g,
      '<a class="message-link" href="$1" target="_blank">$1</a>'
    );
  }
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

function sendChatMessage(cahtForm) {
  // Create a FormData object
  let data = new FormData(cahtForm);

  // Add the action to send a message
  data.append("action", "send_message");

  // Add the post ID to send the message to
  data.append("post_id", caChat.postId);

  // Add the nonce
  data.append("nonce", caChat.nonce);

  // Send the message
  fetch(caChat.ajaxUrl, {
    method: "POST",
    body: data,
  })
    .then((response) => response.json())
    .then((responseJson) => {
      if (false === responseJson.success) {
        chatroom_get_chat_message_html(responseJson.data);
      }

      if (true === responseJson.success) {
        chatroom_check_updates();
      }
      cahtForm.reset();
    });
}

( () => {
  const chatForm = document.querySelector("#chat_form");
  chatForm.addEventListener("submit", function (e) {
    e.preventDefault();
    sendChatMessage(chatForm);
  });

  // Listen for the user to press enter in the textbox and send the message
  document
    .querySelector("textarea.chat-text-entry")
    .addEventListener("keypress", function (event) {
      if (event.charCode == 13 || event.keyCode == 13) {
        sendChatMessage(chatForm);
        return false;
      }
    });

    setInterval(chatroom_check_updates, 5000);

})();
