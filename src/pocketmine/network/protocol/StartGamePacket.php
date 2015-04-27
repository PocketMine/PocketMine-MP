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


use pocketmine\math\Vector3;
use pocketmine\Player;

class StartGamePacket extends DataPacket{
	public static $pool = [];
	public static $next = 0;

	public $seed;
	public $generator; //0 old, 1 infinite, 2 flat
	public $gamemode;
	public $eid;
	public $spawnX;
	public $spawnY;
	public $spawnZ;
	public $x;
	public $y;
	public $z;

	public static function create($entityId, $seed, $generator, $gamemode, Vector3 $pos, Vector3 $spawn){
		$pk = new StartGamePacket();
		$pk->seed = (int) $seed;
		$pk->generator = (int) $generator;
		$pk->gamemode = (int) $gamemode;
		$pk->eid = $entityId;
		$pk->spawnX = floor($spawn->x);
		$pk->spawnY = floor($spawn->y);
		$pk->spawnZ = floor($spawn->z);
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;

		return $pk;
	}

	public function pid(){
		return Info::START_GAME_PACKET;
	}

	public function decode(){

	}

	public function encode(){
		$this->reset();
		$this->putInt($this->seed);
		$this->putInt($this->generator);
		$this->putInt($this->gamemode);
		$this->putLong($this->eid);
		$this->putInt($this->spawnX);
		$this->putInt($this->spawnY);
		$this->putInt($this->spawnZ);
		$this->putFloat($this->x);
		$this->putFloat($this->y);
		$this->putFloat($this->z);
	}

	public function getSendEvent(Player $player){
		return new PlayStatusPacketSendEvent($this, $player);
	}

}
