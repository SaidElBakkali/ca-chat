<?php
/**
 * Plugin functions.
 *
 * @package CA Chat
 */

/**
 * It creates a new instance of the CA_Chat class
 */
function ca_chat_init() {
	new CA_Chat_Metabox();
	new CA_Chat();
}


/**
 * It returns the chat enabled status
 *
 * @param int $post_id The post ID.
 *
 * @return bool
 */
function ca_chat_is_enabled( $post_id ) {
	return 'yes' === get_post_meta( $post_id, 'ca_chat_enabled', true );
}

/**
 * It returns the chat template
 *
 * @param string $template_name The template name.
 * @param array  $args The template arguments.
 *
 * @return string
 */
function ca_chat_get_template( $template_name, $args = array() ) {
	$located = '';

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	}

	$located = locate_template( array( "ca-chat/{$template_name}", $template_name ) );

	if ( ! $located && file_exists( CA_CHAT__PLUGIN_DIR . "templates/{$template_name}" ) ) {
		$located = CA_CHAT__PLUGIN_DIR . "templates/{$template_name}";
	}

	if ( $located ) {
		ob_start();
		include $located;
		return ob_get_clean();
	}

	return '';
}
