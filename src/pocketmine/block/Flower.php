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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class Flower extends Flowable{
	const META_POPPY = 0;
	const META_BLUE_ORCHID = 1;
	const META_ALLIUM = 2;
	const META_AZURE_BLUET = 3;
	const META_RED_TULIP = 4;
	const META_ORANGE_TULIP = 5;
	const META_WHITE_TULIP = 6;
	const META_PINK_TULIP = 7;
	const META_OXEYE_DAISY = 8;

	protected $id = self::RED_FLOWER;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		static $names = [
			self::META_POPPY => "Poppy",
			self::META_BLUE_ORCHID => "Blue Orchid",
			self::META_ALLIUM => "Allium",
			self::META_AZURE_BLUET => "Azure Bluet",
			self::META_RED_TULIP => "Red Tulip",
			self::META_ORANGE_TULIP => "Orange Tulip",
			self::META_WHITE_TULIP => "White Tulip",
			self::META_PINK_TULIP => "Pink Tulip",
			self::META_OXEYE_DAISY => "Oxeye Daisy",
			9 => "Unknown Flower",
		];
		return $names[$this->meta & 0x09];
	}


	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$down = $this->getSide(0);
		if($down->getId() === 2 or $down->getId() === 3 or $down->getId() === 60){
			$this->getLevel()->setBlock($block, $this, true, true);

			return true;
		}

		return false;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->isTransparent() === true){
				$this->getLevel()->useBreakOn($this);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}
}