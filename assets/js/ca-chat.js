function chatroom_check_updates() {
	let last_update_id = 0;
	let data = new FormData();
	data.append("action", "check_updates");
	data.append("post_id", caChat.postId);
	data.append("last_update_id", last_update_id);
	fetch(caChat.ajaxUrl, {
	  method: "POST",
	  body: data,
	})
	  .then((response) => {
		return response.text();
	  })
	  .then((response) => {
		chats = JSON.parse(response);

		//console.table(chats);
		if (chats !== null) {
		  for (i = 0; i < chats.length; i++) {
			if (
			  document.querySelector(
				"div.chat-container div.chat-message-" + chats[i].id
			  )
			) {
			  continue;
			}
			document.querySelector("div.chat-container").innerHTML =
			  document.querySelector("div.chat-container").innerHTML +
			  chatroom_strip_slashes(chats[i].html);
			last_update_id = chats[i].id;
			document.querySelector("div.chat-container").scrollTop =
			  document.querySelector("div.chat-container").scrollHeight -
			  document.querySelector("div.chat-container").clientHeight;
		  }
		}
	  });

	setTimeout("chatroom_check_updates()", 1000);
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

	// Send the message
	fetch(caChat.ajaxUrl, {
	  method: "POST",
	  body: data,
	})
	  .then((response) => {
		// Convert the response to text
		return response.text();
	  })
	  .then((response) => {
		// Do nothing with the response
	  });
  }
