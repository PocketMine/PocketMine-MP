<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\level\generator;

use pocketmine\level\format\SimpleChunk;
use pocketmine\level\Level;
use pocketmine\Server;
use pocketmine\utils\Binary;

class ExternalGenerationRequestManager extends GenerationRequestManager {

	protected $socket;
	/** @var Server */
	protected $server;
	/** @var GenerationThread */
	protected $generationThread;

	/**
	 * @param Server $server
	 */
	public function __construct(Server $server){
		$this->server = $server;
		$this->socket = $this->makeExternalClient();
	}

	private function makeExternalClient() {
		if (($sock = socket_create(AF_UNIX, SOCK_STREAM, 0)) === false) {
		    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
		}
		$addr = "/tmp/pocketmine_external_generator_" . getmypid();
		socket_bind($sock, $addr);
		socket_listen($sock);
		// start the external server
		$cmdline = $this->server->getConfigString("external-generation-cmd", "hhvm -v Eval.Jit=true");
		exec($cmdline . " " . \pocketmine\PATH . "src/pocketmine/level/generator/ExternalGeneratorEntryPoint.php " . $addr .
			" > /tmp/pmexternal 2>&1 &");
		// wait for a connection

		$clientSock = socket_accept($sock);
		socket_set_nonblock($clientSock);
		@socket_set_option($clientSock, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 2);
		@socket_set_option($clientSock, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 2);

		socket_close($sock);
		return $clientSock;
	}

	public function shutdown(){
		$buffer = chr(GenerationManager::PACKET_SHUTDOWN);
		@socket_write($this->socket, Binary::writeInt(strlen($buffer)) . $buffer);
	}


}
