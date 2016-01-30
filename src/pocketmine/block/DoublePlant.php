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
use pocketmine\math\Vector3;

class DoublePlant extends Flowable{

	const TYPE_SUN_FLOWER = 0;
	const TYPE_LILAC = 1;
	const TYPE_DOUBLE_TALLGRASS = 2;
	const TYPE_LARGE_FERN = 3;
	const TYPE_ROSE_BUSH = 4;
	const TYPE_PEONY = 5;

	protected $id = self::DOUBLE_PLANT;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function canBeReplaced(){
		return true;
	}

	public function getName(){
		static $names = [
			0 => "Sunflower",
			1 => "Lilac",
			2 => "Double Tallgrass",
			3 => "Large Fern",
			4 => "Rose Bush",
			5 => "Peony"
		];
		return $names[$this->meta & 0x07];
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$down = $this->getSide(Vector3::SIDE_DOWN);
		$up = $this->getSide(Vector3::SIDE_UP);
		if($up->getId() === Block::AIR && ($down->getId() === Block::GRASS or $down->getId() === Block::DIRT or $down->getId() === Block::FARMLAND)){
			$this->getLevel()->setBlock($this, $this, true, true);
			$this->getLevel()->setBlock($up, Block::get($this->id, $this->meta | 0x08), true, true); //Top
			return true;
		}
		return false;
	}

	public function onBreak(Item $item){
		if(($this->getDamage() & 0x08) === 0x08){
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				$this->getLevel()->setBlock($down, new Air(), true);
			}
		}else{
			$up = $this->getSide(Vector3::SIDE_UP);
			if($up->getId() === $this->getId()){
				$this->getLevel()->setBlock($up, new Air(), true);
			}
		}
		$this->getLevel()->setBlock($this, new Air(), true);

		return true;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() !== $this->getId() && $down->isTransparent()){
				$this->getLevel()->setBlock($this, new Air(), false);
				if($this->getSide(Vector3::SIDE_UP)->getId() === $this->getId()){ //Replace with common break method
					$this->getLevel()->setBlock($this->getSide(Vector3::SIDE_UP), new Air(), false);
				}
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}
		return false;
	}

	public function getDrops(Item $item){
		$meta = $this->meta & 0x07;
		if(($meta !== self::TYPE_DOUBLE_TALLGRASS && $meta !== self::TYPE_LARGE_FERN) || $item->isShears()){
			return [
				[$this->id, $meta, 1]
			];
		}elseif(mt_rand(0, 15) === 0){
			return [
				[Item::WHEAT_SEEDS, 0, 1]
			];
		}else{
			return [];
		}
	}
}