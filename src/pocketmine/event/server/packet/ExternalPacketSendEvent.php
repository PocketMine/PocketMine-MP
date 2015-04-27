<?php

/**
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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\server\packet;

use pocketmine\event\Cancellable;

class ExternalPacketSendEvent extends PacketEvent implements Cancellable{

	private $payload;
	private $address;
	private $port;

	public function __construct($payload, $address, $port){
		$this->payload = (string) $payload;
		$this->address = (string) $address;
		$this->port = (int) $port;
	}

	/**
	 * @return string
	 */
	public function getPayload(){
		return $this->payload;
	}

	/**
	 * @param string $payload
	 */
	public function setPayload($payload){
		$this->payload = (string) $payload;
	}

	/**
	 * @return string
	 */
	public function getAddress(){
		return $this->address;
	}

	/**
	 * @param string $address
	 */
	public function setAddress($address){
		$this->address = (string) $address;
	}

	/**
	 * @return int
	 */
	public function getPort(){
		return $this->port;
	}

	/**
	 * @param int $port
	 */
	public function setPort($port){
		$this->port = (int) $port;
	}
}
