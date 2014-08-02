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

namespace PocketMine\Item;

use PocketMine\Block\Air as Air;
use PocketMine\Block\Block as Block;
use PocketMine\Block\Lava as Lava;
use PocketMine\Block\Liquid as Liquid;
use PocketMine\Block\Water as Water;
use PocketMine\Level\Level as Level;
use PocketMine;

class Bucket extends Item{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::BUCKET, $meta, $count, "Bucket");
		$this->isActivable = true;
		$this->maxStackSize = 1;
	}

	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		if($this->meta === AIR){
			if($target instanceof Liquid){
				$level->setBlock($target, new Air(), true, false, true);
				if(($player->gamemode & 0x01) === 0){
					$this->meta = ($target instanceof Water) ? WATER : LAVA;
				}

				return true;
			}
		} elseif($this->meta === WATER){
			//Support Make Non-Support Water to Support Water
			if($block->getID() === self::AIR || ($block instanceof Water && ($block->getMetadata() & 0x07) != 0x00)){
				$water = new Water();
				$level->setBlock($block, $water, true, false, true);
				$water->place(clone $this, $player, $block, $target, $face, $fx, $fy, $fz);
				if(($player->gamemode & 0x01) === 0){
					$this->meta = 0;
				}

				return true;
			}
		} elseif($this->meta === LAVA){
			if($block->getID() === self::AIR){
				$level->setBlock($block, new Lava(), true, false, true);
				if(($player->gamemode & 0x01) === 0){
					$this->meta = 0;
				}

				return true;
			}
		}

		return false;
	}
}