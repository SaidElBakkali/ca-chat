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
        // check if the response is an array
        if (Array.isArray(responseJson.data)) {
          // loop through the array
          for (const chat of responseJson.data) {
            chatroom_get_chat_message_html(chat);
          }
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
    if (null !== last_message) {
      if (!last_message.classList.contains("chat-message-0")) {
        chat_content.innerHTML += message;

        chat_content.scrollTop = chat_content.scrollHeight;
      }
    } else {
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

function sendChatMessage(chatForm) {

  // Disable the submit button
  chatForm.querySelector("#submit_message").disabled = true;

  // Create a FormData object
  let data = new FormData(chatForm);

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
      if (0 === responseJson.data.id) {
        chatroom_get_chat_message_html(responseJson.data);
      }

      if (true === responseJson.success) {
        // check if the response is an array
        if (Array.isArray(responseJson.data)) {
          // loop through the array
          for (const chat of responseJson.data) {
            chatroom_get_chat_message_html(chat);
          }
        }
      }
      chatForm.reset();
    });

    // Enable the submit button
    chatForm.querySelector("#submit_message").disabled = false;
}

(() => {
    const chatForm = document.querySelector("#chat_form");
    chatForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const cahatMessage = chatForm.querySelector("#chat_message").value;

    if (cahatMessage === "") {
      return;
    }
    sendChatMessage(chatForm);
  });

  // Send form when enter is pressed
  chatForm.addEventListener("keypress", function (e) {
    if (e.keyCode === 13) {
      e.preventDefault();
      const cahatMessage = chatForm.querySelector("#chat_message").value;

      if (cahatMessage === "") {
        return;
      }
      sendChatMessage(chatForm);
    }
  });

  // Check for updates every 5 seconds if the user is logged in else check every 10 seconds
  const updatesTime = "1" === caChat.is_post_author ? 1000 : 5000;

  // Check for updates every 5 seconds
  setInterval(chatroom_check_updates, updatesTime);
})();
