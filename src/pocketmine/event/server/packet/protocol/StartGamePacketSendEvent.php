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

namespace pocketmine\event\server\packet\protocol;

use pocketmine\network\protocol\StartGamePacket;
use pocketmine\Player;

class StartGamePacketSendEvent extends DataPacketEvent{
	public static $handlerList = null;

	/** @var StartGamePacket */
	private $packet;

	public function __construct(StartGamePacket $packet, Player $player){
		parent::__construct($player);
		$this->packet = $packet;
	}

	/**
	 * @return StartGamePacket
	 */
	public function getPacket(){
		return $this->packet;
	}

	/**
	 * @return int
	 */
	public function getEntityId(){
		return $this->packet->eid;
	}

	/**
	 * @param int $entityId
	 */
	public function setEntityId($entityId){
		$this->packet->eid = $entityId;
	}

	/**
	 * @return int
	 */
	public function getSeed(){
		return $this->packet->seed;
	}

	/**
	 * @param int $seed
	 */
	public function setSeed($seed){
		$this->packet->seed = (int) $seed;
	}
}
