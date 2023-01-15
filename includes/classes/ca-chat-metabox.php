<?php
/**
 * Create a new metabox for the chat.
 *
 * @package CA Chat
 */

/**
 * Class CA_Chat_Metabox
 */
class CA_Chat_Metabox {

	/**
	 * CA_Chat_Metabox constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	/**
	 * Add the metabox.
	 */
	public function add_meta_box() {
		add_meta_box(
			'ca_chat_metabox',
			__( 'Opciones del Chat', 'ca-chat' ),
			array( $this, 'render_meta_box_content' ),
			array( 'streaming' ),
			'side',
			'high'
		);
	}

	/**
	 * Render the metabox content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'ca_chat_metabox', 'ca_chat_metabox_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$ca_chat_enabled = 'yes' === get_post_meta( $post->ID, 'ca_chat_enabled', true ) ? 'yes' : 'no';

		// Display the form, using the current value.
		?>
		<p>
			<label for="ca_chat_enabled">
				<input type="checkbox" id="ca_chat_enabled" name="ca_chat_enabled" value="yes" <?php checked( $ca_chat_enabled, 'yes' ); ?>>
				<?php esc_html_e( 'Enable chat', 'ca-chat' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 *
	 * @return int
	 */
	public function save( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['ca_chat_metabox_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['ca_chat_metabox_nonce']; // phpcs:ignore

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'ca_chat_metabox' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted,
		// so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}

		$is_streaming = isset( $_POST['post_type'] ) && in_array( $_POST['post_type'], array( 'streaming' ), true );

		if ( $is_streaming ) {

			/* OK, its safe for us to save the data now. */

			// Sanitize the user input.
			$ca_chat_enabled = isset( $_POST['ca_chat_enabled'] ) ? 'yes' : 'no';

			// Update the meta field.
			update_post_meta( $post_id, 'ca_chat_enabled', $ca_chat_enabled );
		}
	}
}
