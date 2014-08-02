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

namespace PocketMine\Block;

use PocketMine;
use PocketMine\Item\Item as Item;

class Dandelion extends Flowable{
	public function __construct(){
		parent::__construct(self::DANDELION, 0, "Dandelion");
		$this->hardness = 0;
	}

	public function place(Item $item, PocketMine\Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$down = $this->getSide(0);
		if($down->getID() === 2 or $down->getID() === 3 or $down->getID() === 60){
			$this->level->setBlock($block, $this, true, false, true);

			return true;
		}

		return false;
	}

	public function onUpdate($type){
		if($type === BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->isTransparent === true){ //Replace with common break method
				//TODO
				//ServerAPI::request()->api->entity->drop($this, Item::get($this->id));
				$this->level->setBlock($this, new Air(), false, false, true);

				return BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}
}