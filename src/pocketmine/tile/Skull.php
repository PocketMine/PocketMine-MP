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

use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class Skull extends Spawnable{
	const TYPE_SKELETON = 0;
	const TYPE_WITHER = 1;
	const TYPE_ZOMBIE = 2;
	const TYPE_HUMAN = 3;
	const TYPE_CREEPER = 4;

	public function __construct(FullChunk $chunk, CompoundTag $nbt){
		if(!isset($nbt->SkullType)){
			$nbt->SkullType = new ByteTag("SkullType", 0);
		}
		if(!isset($nbt->Rot)){
			$nbt->Rot = new ByteTag("Rot", 0);
		}
		parent::__construct($chunk, $nbt);
	}

	public function setType($type){
		if($type >= 0 && $type <= 4){
			$this->namedtag->SkullType = new ByteTag("SkullType", $type);
			$this->spawnToAll();

			if($this->chunk){
				$this->chunk->setChanged();
				$this->level->clearChunkCache($this->chunk->getX(), $this->chunk->getZ());
			}
			return true;
		}
		return false;
	}

	public function getType(){
		return $this->namedtag["SkullType"];
	}

	public function getSpawnCompound(){
		return new CompoundTag("", [
			new StringTag("id", Tile::SKULL),
			$this->namedtag->SkullType,
			$this->namedtag->Rot,
			new IntTag("x", (int) $this->x),
			new IntTag("y", (int) $this->y),
			new IntTag("z", (int) $this->z)
		]);
	}
}