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

namespace pocketmine\tile;

use pocketmine\level\format\Chunk;
use pocketmine\nbt\tag\Compound;
use pocketmine\Player;

abstract class Spawnable extends Tile{
	public abstract function spawnTo(Player $player);

	public function __construct(Chunk $chunk, Compound $nbt){
		parent::__construct($chunk, $nbt);
		$this->spawnToAll();
	}

	public function spawnToAll(){
		foreach($this->getLevel()->getPlayers() as $player){
			if($player->spawned === true){
				$this->spawnTo($player);
			}
		}
	}
}