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

use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\Player;

class PlayStatusPacketSendEvent extends DataPacketEvent{
	public static $handlerList = null;

	/** @var PlayStatusPacket */
	private $packet;

	public function __construct(PlayStatusPacket $packet, Player $player){
		parent::__construct($player);
		$this->packet = $packet;
	}

	/**
	 * @return PlayStatusPacket
	 */
	public function getPacket(){
		return $this->packet;
	}

	/**
	 * @return int
	 */
	public function getStatus(){
		return $this->packet->status;
	}

	/**
	 * @param int $status
	 */
	public function setStatus($status){
		$this->packet->status = (int) $status;
	}
}
