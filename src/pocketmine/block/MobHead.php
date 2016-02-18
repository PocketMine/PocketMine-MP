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
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\String;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;

class MobHead extends Solid{
	protected $id = self::MOB_HEAD;
	protected $type;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getHardness(){
		return 1;
	}

	public function getName(){
		return "Mob Head";
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($face !== 0){
			$this->meta = $face;
			if($face === 1){
				$rot = floor(($player->yaw * 16 / 360) + 0.5) & 0x0F;
			}else{
				$rot = $face;
			}
			$this->getLevel()->setBlock($block, $this, true);
			$nbt = new Compound("", [
				new String("id", Tile::SKULL),
				new Byte("SkullType", $item->getDamage()),
				new Byte("Rot",  $rot),
				new Int("x", (int) $this->x),
				new Int("y", (int) $this->y),
				new Int("z", (int) $this->z)
			]);
			if($item->hasCustomName()){
				$nbt->CustomName = new String("CustomName", $item->getCustomName());
			}
			/** @var Spawnable $tile */
			Tile::createTile("Skull", $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
			return true;
		}
		return false;
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		$faces = [
			1 => 0,
			2 => 3,
			3 => 2,
			4 => 5,
			5 => 4,
		];
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide($faces[$this->meta])->getId() === self::AIR){
				$this->getLevel()->useBreakOn($this);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function getDrops(Item $item){
		if($this->meta === 3){
			return [];
		}
		return [
			[Item::MOB_HEAD, $this->type, 1]
		];
	}
}