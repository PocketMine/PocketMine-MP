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

namespace PocketMine\Entity;

use PocketMine\Player;
use PocketMine;

abstract class RealHuman extends Human{

	protected function initEntity(){
		$this->level->players[$this->CID] = $this;
		parent::initEntity();
	}

	public function close(){
		unset($this->level->players[$this->CID]);
		parent::close();
	}

	public function spawnTo(Player $player){
		if($this->spawned === true){
			parent::spawnTo($player);
		}
	}

	public function despawnFrom(Player $player){
		if($this->spawned === true){
			parent::despawnFrom($player);
		}
	}
}