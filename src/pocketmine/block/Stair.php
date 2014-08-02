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

class Stair extends Transparent{

	public function __construct($id, $meta = 0, $name = "Unknown"){
		parent::__construct($id, $meta, $name);
		if(($this->meta & 0x04) === 0x04){
			$this->isFullBlock = true;
		} else{
			$this->isFullBlock = false;
		}
		$this->hardness = 30;
	}

	public function place(Item $item, PocketMine\Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$faces = array(
			0 => 0,
			1 => 2,
			2 => 1,
			3 => 3,
		);
		$this->meta = $faces[$player->getDirection()] & 0x03;
		if(($fy > 0.5 and $face !== 1) or $face === 0){
			$this->meta |= 0x04; //Upside-down stairs
		}
		$this->level->setBlock($block, $this, true, false, true);

		return true;
	}

	public function getDrops(Item $item, PocketMine\Player $player){
		if($item->isPickaxe() >= 1){
			return array(
				array($this->id, 0, 1),
			);
		} else{
			return array();
		}
	}
}