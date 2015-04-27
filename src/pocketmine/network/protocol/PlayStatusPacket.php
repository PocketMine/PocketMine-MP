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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


use pocketmine\event\server\packet\protocol\PlayStatusPacketSendEvent;
use pocketmine\Player;

class PlayStatusPacket extends DataPacket{
	
	const LOGIN_SUCCESS = 0;
	const LOGIN_FAILED_CLIENT = 1;
	const LOGIN_FAILED_SERVER = 2;
	const PLAYER_SPAWN = 3;
	
	public static $pool = [];
	public static $next = 0;

	public $status;

	/**
	 * @param int $status
	 *
	 * @return PlayStatusPacket
	 */
	public static function create($status){
		$pk = new PlayStatusPacket();
		$pk->status = (int) $status;
		return $pk;
	}

	public function pid(){
		return Info::PLAY_STATUS_PACKET;
	}

	public function decode(){

	}

	public function encode(){
		$this->reset();
		$this->putInt($this->status);
	}

	public function getSendEvent(Player $player){
		return new PlayStatusPacketSendEvent($this, $player);
	}

}
