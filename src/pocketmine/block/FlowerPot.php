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
use pocketmine\math\Vector3;
use pocketmine\Player;

class FlowerPot extends Transparent{
	protected $id = self::FLOWER_POT_BLOCK;

	public function __construct(){
	}
	
	public function getName(){
		return "Flower Pot";
	}

	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	public function getHardness(){
		return 5;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$this->getLevel()->setBlock($block, $this, true, true);
		$nbt = new Compound("", [new String("id", Tile::FLOWER_POT),new Int("id", $this->id),new Int("data", $this->data),new Int("x", $this->x),new Int("y", $this->y),new Int("z", $this->z)]);
		
		Tile::createTile(Tile::FLOWER_POT, $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
		
		return true;
	}

	public function onBreak(Item $item){
		$this->getLevel()->setBlock($this, new Air(), true, true);
		return true;
	}

	public function getDrops(Item $item){
		return [[Item::FLOWER_POT,0,1],$this->getContents()];
	}

	public function getContents(){
		$content = Item::AIR;
		$contentdamage = 0;
		switch($this->getDamage()){
			case 1:
				$content = Item::ROSE;
			case 2:
				$content = Item::DANDELION;
			case 3:
				$content = Item::SAPLING;
			case 4:
				$content = Item::SAPLING;
				$contentdamage = 1;
			case 5:
				$content = Item::SAPLING;
				$contentdamage = 2;
			case 6:
				$content = Item::SAPLING;
				$contentdamage = 3;
			case 7:
				$content = Item::RED_MUSHROOM;
			case 8:
				$content = Item::BROWN_MUSHROOM;
			case 9:
				$content = Item::CACTUS;
			case 10:
				$content = Item::DEAD_BUSH;
			case 11:
				$content = Item::TALL_GRASS;
				$contentdamage = 2;
			case 12:
				$content = Item::SAPLING;
				$contentdamage = 4;
			case 13:
				{
					$content = Item::SAPLING;
					$contentdamage = 5;
				}
			default:
				$content = Item::AIR;
		}
		return Item::get($content, $contentdamage, 1);
	}

	public function onActivate(Item $item, Player $player = null){
		if($item->getId() === Item::SAPLING || $item->getId() === Item::BROWN_MUSHROOM || $item->getId() === Item::RED_MUSHROOM || $item->getId() === Item::ROSE || $item->getId() === Item::DEAD_BUSH || $item->getId() === Item::DANDELION || $item->getId() === Item::TALL_GRASS || $item->getId() === Item::CACTUS){
			$item->useOn($this);
			$meta = 0;
			switch($item->getId()){
				case ITEM::ROSE:
					{
						$meta = 1;
					}
				case Item::DANDELION:
					{
						$meta = 2;
					}
				case Item::RED_MUSHROOM:
					{
						$meta = 7;
					}
				case Item::BROWN_MUSHROOM:
					{
						$meta = 8;
					}
				case Item::CACTUS:
					{
						$meta = 9;
					}
				case Item::DEAD_BUSH:
					{
						$meta = 10;
					}
				case Item::SAPLING:
					{
						$species = $item->getDamage();
						/*
						 * GENERIC(0x00),
						 * REDWOOD(0x01),
						 * BIRCH(0x02),
						 * JUNGLE(0x03),
						 * ACACIA(0x04),
						 * DARK_OAK(0x05),
						 */
						if($species == 0x00){
							$meta = 3;
						}
						elseif($species == 0x01){
							$meta = 4;
						}
						elseif($species == 0x02){
							$meta = 5;
						}
						elseif($species == 0x03){
							$meta = 6;
						}
						elseif($species == 0x04){
							$meta = 12;
						}
						else{
							$meta = 13;
						}
					}
				case Item::TALL_GRASS:
					{
						$species = $item->getDamage();
						
						if($species == 0x02){
							$meta = 11;
						}
					}
			}
			$this->setDamage($meta);
			$this->getLevel()->setBlock($this, $this, true);
			return true;
		}
		return false;
	}
}