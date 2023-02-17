<?php
/**
 * Websocket Chat class file
 */

namespace Websocket_Chat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * Websocket Chat class.
 *
 * @package Websocket Chat
 * @version 0.1.1
 * @since 0.1.0
 * @author Said El Bakkali
 * @license GPLv2 or later
 * @link https://saidelbakkali.com/
 */
class Websocket_Chat implements MessageComponentInterface {
	/**
	 * List of connected clients.
	 *
	 * @var array
	 */
	protected $clients;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->clients = new \SplObjectStorage();
	}

	/**
	 * On open connection.
	 *
	 * @param ConnectionInterface $conn Connection.
	 */
	public function onOpen( ConnectionInterface $conn ) {
		// Store the new connection to send messages to later.
		$this->clients->attach( $conn );

		echo "New connection! ({$conn->resourceId})\n";
	}

	/**
	 * On message.
	 *
	 * @param ConnectionInterface $from Connection.
	 * @param string              $msg Message.
	 */
	public function onMessage( ConnectionInterface $from, $msg ) {
		$numRecv = count( $this->clients ) - 1;
		echo sprintf( 'Connection %d sending message "%s" to %d other connection%s' . "\n", esc_html( $from->resourceId ), wp_kses_post( $msg ), $numRecv, $numRecv == 1 ? '' : 's' );

		foreach ( $this->clients as $client ) {
			if ( $from !== $client ) {
				// The sender is not the receiver, send to each client connected.
				$client->send( $msg );
			}
		}

		$from->send( $msg );

		// $this->clients->detach( $from );
		// $from->close();
	}

	/**
	 * On close connection.
	 *
	 * @param ConnectionInterface $conn Connection.
	 */
	public function onClose( ConnectionInterface $conn ) {
		// The connection is closed, remove it, as we can no longer send it messages.
		$this->clients->detach( $conn );

		echo "Connection {$conn->resourceId} has disconnected\n";
	}

	/**
	 * On error.
	 *
	 * @param ConnectionInterface $conn Connection.
	 * @param \Exception          $e Exception.
	 */
	public function onError( ConnectionInterface $conn, \Exception $e ) {
		echo "An error has occurred: {$e->getMessage()}\n";

		$conn->close();
	}
}
