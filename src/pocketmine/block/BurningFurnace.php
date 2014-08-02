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

use PocketMine\Item\Item as Item;
use PocketMine\NBT\Tag\Compound as Compound;
use PocketMine\NBT\Tag\Enum as Enum;
use PocketMine\NBT\Tag\Int as Int;
use PocketMine\NBT\Tag\String as String;
use PocketMine\Tile\Furnace as Furnace;
use PocketMine\Tile\Tile as Tile;
use PocketMine;

class BurningFurnace extends Solid{
	public function __construct($meta = 0){
		parent::__construct(self::BURNING_FURNACE, $meta, "Burning Furnace");
		$this->isActivable = true;
		$this->hardness = 17.5;
	}

	public function place(Item $item, PocketMine\Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$faces = array(
			0 => 4,
			1 => 2,
			2 => 5,
			3 => 3,
		);
		$this->meta = $faces[$player->getDirection()];
		$this->level->setBlock($block, $this, true, false, true);
		$nbt = new Compound(false, array(
			"Items" => new Enum("Items", array()),
			"id" => new String("id", Tile::FURNACE),
			"x" => new Int("x", $this->x),
			"y" => new Int("y", $this->y),
			"z" => new Int("z", $this->z)
		));
		$nbt->Items->setTagType(NBT\Tag_Compound);
		$furnace = new Furnace($this->level, $nbt);

		return true;
	}

	public function onBreak(Item $item, PocketMine\Player $player){
		$this->level->setBlock($this, new Air(), true, true, true);

		return true;
	}

	public function onActivate(Item $item, PocketMine\Player $player){

		$t = $this->level->getTile($this);
		$furnace = false;
		if($t instanceof Furnace){
			$furnace = $t;
		} else{
			$nbt = new Compound(false, array(
				"Items" => new Enum("Items", array()),
				"id" => new String("id", Tile::FURNACE),
				"x" => new Int("x", $this->x),
				"y" => new Int("y", $this->y),
				"z" => new Int("z", $this->z)
			));
			$nbt->Items->setTagType(NBT\Tag_Compound);
			$furnace = new Furnace($this->level, $nbt);
		}

		if(($player->gamemode & 0x01) === 0x01){
			return true;
		}

		$furnace->openInventory($player);

		return true;
	}

	public function getBreakTime(Item $item, PocketMine\Player $player){
		if(($player->gamemode & 0x01) === 0x01){
			return 0.20;
		}
		switch($item->isPickaxe()){
			case 5:
				return 0.7;
			case 4:
				return 0.9;
			case 3:
				return 1.35;
			case 2:
				return 0.45;
			case 1:
				return 2.65;
			default:
				return 17.5;
		}
	}

	public function getDrops(Item $item, PocketMine\Player $player){
		$drops = array();
		if($item->isPickaxe() >= 1){
			$drops[] = array(FURNACE, 0, 1);
		}
		$t = $this->level->getTile($this);
		if($t instanceof Furnace){
			for($s = 0; $s < Furnace::SLOTS; ++$s){
				$slot = $t->getSlot($s);
				if($slot->getID() > AIR and $slot->getCount() > 0){
					$drops[] = array($slot->getID(), $slot->getMetadata(), $slot->getCount());
				}
			}
		}

		return $drops;
	}
}