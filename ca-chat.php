<?php
/**
 * Plugin Name: CA Chat
 * Plugin URI: https://saidelbakkali.com/
 * Description: Chat for Crypto Master Platform
 * Author: Said El Bakkali
 * Version: 0.1.1
 * Author URI: https://saidelbakkali.com/
 * License: GPLv2 or later
 * Text Domain: ca-chat
 * Domain Path: /languages
 *
 * @package CA Chat
 */

define( 'CA_CHAT_VERSION', '0.1.2' );
define( 'CA_CHAT__MINIMUM_WP_VERSION', '5.5' );
define( 'CA_CHAT__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CA_CHAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Composer autoload.
require_once CA_CHAT__PLUGIN_DIR . 'includes/packages/autoload.php';
require_once CA_CHAT__PLUGIN_DIR . 'includes/classes/ca-chat.php';
require_once CA_CHAT__PLUGIN_DIR . 'includes/classes/ca-chat-metabox.php';
require_once CA_CHAT__PLUGIN_DIR . 'includes/helpers.php';

ca_chat_init();