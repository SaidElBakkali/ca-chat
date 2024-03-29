<?php
/**
 * Plugin main class
 *
 * @package CA Chat
 */

/**
 * Class CA_Chat
 */
class CA_Chat {

	/**
	 * It registers the activation and deactivation hooks, enqueues the scripts, creates the chatroom log
	 * file, defines the javascript variables, and sets up the ajax handlers.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_check_updates', array( $this, 'ajax_check_updates_handler' ) );
		add_action( 'wp_ajax_send_message', array( $this, 'ajax_send_message_handler' ) );
	}


	/**
	 * We're checking to see if the post type is a streaming post type. If it is, we're enqueuing the
	 * scripts and styles, and defining the javascript variables.
	 */
	public function enqueue_scripts() {

		global $post;

		if ( isset( $post->post_type ) && 'streaming' === $post->post_type ) {
			wp_enqueue_script( 'ca-chat', CA_CHAT_PLUGIN_URL . 'assets/frontend/js/ca-chat.js', array(), CA_CHAT_VERSION, true );
			wp_enqueue_style( 'ca-chat', CA_CHAT_PLUGIN_URL . 'assets/frontend/css/style.css', array(), CA_CHAT_VERSION );

			// Add RTL support.
			wp_style_add_data( 'ca-chat', 'rtl', 'replace' );

			wp_localize_script(
				'ca-chat',
				'caChat',
				array(
					'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
					'nonce'          => wp_create_nonce( 'ca_chat_nonce' ),
					'postId'         => $post->ID,
					'is_post_author' => current_user_can( 'edit_post', $post->ID ),
				)
			);
		}
	}

	/**
	 * If the post type is streaming, and the log file doesn't exist, create it
	 *
	 * @param int    $post_id The ID of the post that was just created.
	 * @param object $post the post object.
	 */
	public function maybe_create_chatroom_log_file( $post_id, $post ) {
		if ( empty( $post->post_type ) || 'streaming' !== $post->post_type ) {
			return;
		}

		$dir          = $this->get_chatter_folder_path();
		$log_filename = $dir . $post_id . '-' . $this->get_current_date() . '.json';

		if ( file_exists( $log_filename ) ) {
			return;
		}

		// Create the chatter directory if it doesn't exist.
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Open and write to the file using the wp_filesystem API.
		$this->write_log_file( $log_filename, wp_json_encode( array() ) );

	}

	/**
	 * It reads the contents of the log file and returns it
	 *
	 * @param string $log_filename The full path to the log file.
	 *
	 * @return string The contents of the log file.
	 */
	public function parse_messages_log_file( $log_filename ) {
		$wp_filesystem = $this->get_wp_filesystem();

		if ( ! file_exists( $log_filename ) ) {
			return wp_json_encode( array() );
		}

		$contents = $wp_filesystem->get_contents( $log_filename );

		return $contents;
	}

	/**
	 * It returns the path to the log file for a given post
	 *
	 * @param int $post_id The ID of the post that the comment is being made on.
	 *
	 * @return string The log file name.
	 */
	public function get_log_filename( $post_id ) {

		$log_filename = $this->get_chatter_folder_path() . $post_id . '-' . $this->get_current_date() . '.json';

		return $log_filename;
	}

	/**
	 * It returns the log file name
	 */
	public function get_wp_filesystem() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * It writes the contents to the log file
	 *
	 * @param string $log_filename The full path to the log file.
	 * @param string $contents The contents to write to the log file.
	 */
	public function write_log_file( $log_filename, $contents ) {
		$wp_filesystem = $this->get_wp_filesystem();

		$wp_filesystem->put_contents( $log_filename, $contents, FS_CHMOD_FILE );
	}

	/**
	 * It reads the log file, removes all messages that have already been sent to the client, and returns
	 * the remaining messages
	 */
	public function ajax_check_updates_handler() {

		// Check the nonce.
		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( 'ca_chat_nonce', 'nonce' ) ) {
			die;
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		$this->send_messages( $post_id, get_current_user_id() );
	}

	/**
	 * AJAX server-side handler for sending a message.
	 *
	 * Stores the message in a recent messages file.
	 *
	 * Clears out cache of any messages older than 10 seconds.
	 */
	public function ajax_send_message_handler() {

		// Check the nonce.
		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( 'ca_chat_nonce', 'nonce' ) ) {
			die;
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$message = isset( $_POST['chat_message'] ) ? sanitize_text_field( wp_unslash( $_POST['chat_message'] ) ) : '';

		// It's getting the post object.
		$post = get_post( $post_id );

		// Get post author display name.
		$user = get_user_by( 'id', $post->post_author );

		// Check if the chat is enabled.
		if ( false === $this->chat_is_enabled( $post ) ) {
			// Send non published post message.
			$this->send_non_published_post_message( $user, $post );
		}

		if ( true === $this->chat_is_enabled( $post ) ) {
			if ( $post_id && $message ) {
				$this->save_message( $post_id, get_current_user_id(), $message );
				$this->send_messages( $post_id, get_current_user_id() );
			}
		}
	}

	/**
	 * `chat_is_enabled` checks if the chat is enabled for a given post
	 *
	 * @param object $post The ID of the post you want to check.
	 */
	public function chat_is_enabled( $post ) {
		$ca_chat_enabled = 'yes' === get_post_meta( $post->ID, 'ca_chat_enabled', true ) ? 'yes' : 'no';

		if ( 'publish' === $post->post_status && 'streaming' === $post->post_type && 'yes' === $ca_chat_enabled ) {
			return true;
		}

		return false;
	}

	/**
	 * It sends non published post message
	 *
	 * @param object $user The user object.
	 * @param object $post The post object.
	 */
	public function send_non_published_post_message( $user, $post ) {
		$messages = array(
			'id'             => 0,
			'time'           => time(),
			'is_post_author' => true,
			'sender'         => absint( $post->post_author ),
			'lesson_author'  => absint( $post->post_author ),
			'contents'       => 'El chat aún no está disponible, por favor intenta mas tarde.',
			'message_time'   => $this->get_current_time(),
			'author_avatar'  => crypto_academy_get_user_avatar_html( $post->post_author, 40 ),
			'author_name'    => $user->display_name,
			'is_private'     => true,
		);

		wp_send_json_error( $messages );
	}

	/**
	 * It saves the message in the recent messages file and the daily log file
	 *
	 * @param int    $post_id The ID of the post that the message is being sent to.
	 * @param int    $user_id The ID of the user who sent the message.
	 * @param object $content The message content.
	 */
	public function save_message( $post_id, $user_id, $content ) {
		$user = get_userdata( $user_id );

		$content = esc_attr( $content );
		// Save the message in recent messages file.

		if ( '' === $content ) {
			return;
		}

		$log_filename    = $this->get_log_filename( $post_id );
		$contents        = $this->parse_messages_log_file( $log_filename );
		$messages        = json_decode( $contents );
		$last_message_id = 0; // Helps determine the new message's ID.

		/* It's getting the values of the messages array. */
		$messages_values = array_values( $messages );

		/* It's getting the last message ID. */
		if ( ! empty( $messages_values ) ) {
			$last_message_id = end( $messages_values )->id;
		}

		/* It's getting the last message ID and adding 1 to it. */
		$new_message_id = $last_message_id + 1;

		/* It's getting the post object. */
		$post = get_post( $post_id );

		$messages[] = array(
			'id'             => $new_message_id,
			'time'           => time(),
			'is_post_author' => absint( $post->post_author ) === $user->ID ? true : false,
			'sender'         => $user->ID,
			'lesson_author'  => absint( $post->post_author ),
			'contents'       => $content,
			'message_time'   => $this->get_current_time(),
			'author_avatar'  => crypto_academy_get_user_avatar_html( $user->ID, 40 ),
			'author_name'    => $user->display_name,
		);

		$this->maybe_create_chatroom_log_file( $post_id, $post );

		$this->write_log_file( $log_filename, wp_json_encode( $messages ) );
	}


	// Send messages to the front end.
	/**
	 * It sends the messages to the front end
	 *
	 * @param int $post_id The ID of the post that the message is being sent to.
	 */
	public function send_messages( $post_id, $user_id ) {

		$log_filename = $this->get_log_filename( $post_id );
		$contents     = $this->parse_messages_log_file( $log_filename );
		$messages     = json_decode( $contents, true );
		$post         = get_post( $post_id );

		/* It's looping through the messages and removing any messages that are older than an hour. */

		if ( is_array( $messages ) ) {
			foreach ( $messages as $key => $message ) {
				if ( time() - $message['time'] > 3600 ) {
					// unset( $messages[ $key ] );
				} else {
					// $messages[ $key ]->message_time = $this->get_current_time();
				}
			}
		}

		if ( is_array( $messages ) && ! empty( $messages ) ) {

			foreach ( $messages as $key => $message ) {

				$hide_message = true;
				$is_sender    = $user_id === $message['sender'] ? true : false;

				if ( $is_sender ) {
					$hide_message = false;
				}

				if ( absint( $post->post_author ) === $user_id ) {
					$hide_message = false;
				}

				if ( $message['is_post_author'] ) {
					$hide_message = false;
				}

				if ( true === $hide_message ) {
					unset( $messages[ $key ] );
				}
			}

			$messages = array_values( $messages );
			wp_send_json_success( $messages );
		}
	}

	// Get current time.
	/**
	 * It gets the current time
	 *
	 * @return string $time The current time.
	 */
	public function get_current_time() {
		$time = gmdate( 'H:i', time() );
		return $time;
	}

	// Get current date.
	/**
	 * It gets the current date
	 *
	 * @return string $date The current date.
	 */
	public function get_current_date() {
		$date = gmdate( 'm-d-y', time() );
		return $date;
	}

	/**
	 * It takes a string of text, finds any URLs in it, and converts them to HTML links
	 *
	 * @param string $text The text to be converted.
	 *
	 * @return string $text the text with the links.
	 */
	public function convert_url_to_link( $text ) {
		$text = preg_replace( '/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $text );
		return $text;
	}

	/**
	 * It outputs the chat form
	 *
	 * @return Nothing.
	 */
	public function ca_the_chat_form() {
		global $post;
		if ( 'streaming' !== $post->post_type ) {
			return false;
		}

		$user_avatar   = function_exists( 'crypto_academy_get_user_avatar_html' ) ? crypto_academy_get_user_avatar_html( get_current_user_id(), 40 ) : '';
		$chat_icon_svg = function_exists( 'crypto_academy_get_icon_svg' ) ? crypto_academy_get_icon_svg( 'ui', 'ca-chat', 18 ) : '';
		$send_icon_svg = function_exists( 'crypto_academy_get_icon_svg' ) ? crypto_academy_get_icon_svg( 'ui', 'ca-send', 40 ) : '';

		printf(
			'<div class="chat-wrapper" id="chat_container">
				<h2 class="chat-title row align-items-center">%s&nbspCHAT EN VIVO</h2>
				<div class="chat" id="chat">
					<div class="chat-content chat-container" id="chat_content"></div>
					<form class="chat-form" id="chat_form">
						<textarea class="chat-text-entry" name="chat_message" id="chat_message" placeholder="%s" cols="30" rows="1"></textarea>
						%s
						<button id="submit_message" class="submit-message" type="submit" area-label="%s">%s</button>
					</form>
				</div>
			<!-- <span class="hide-chat" id="hide_chat"><span class="arrow">&lsaquo;</span>%s</span> -->
			</div>',
			$chat_icon_svg, // phpcs:ignore
			esc_html__( 'Escribe tu mensaje aquí...', 'crypto-academy' ),
			$user_avatar, // phpcs:ignore
			esc_html__( 'Enviar el mensaje', 'crypto-academy' ),
			$send_icon_svg, // phpcs:ignore
			esc_html__( 'Enviar el mensaje', 'crypto-academy' )
		);
	}

	/**
	 * It gets the chatter folder path
	 *
	 * @return string $dir The chatter folder path.
	 */
	public function get_chatter_folder_path() {
		$upload_dir = wp_upload_dir();
		$dir        = $upload_dir['basedir'] . '/chatter/';
		return $dir;
	}
}
