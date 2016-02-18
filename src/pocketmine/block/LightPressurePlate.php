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

use pocketmine\entity\Entity;
use pocketmine\level\Level;

class LightPressurePlate extends StonePressurePlate{
	protected $id = self::WEIGHTED_PRESSURE_PLATE_LIGHT;
	protected $denominator = 1;

	public function onEntityCollide(Entity $entity){
		if($this->meta === 0){
			$this->onUpdate(Level::BLOCK_UPDATE_SCHEDULED);
		}
	}

	public function getPowerLevel(){
		return $this->meta;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			$level = $this->getLevel();
			$meta = 0;
			/** @type Entity $entity */
			foreach(array_merge($level->getChunk($this->x >> 4, $this->x >> 4)->getEntities(),
				$level->getChunk(($this->x >> 4) + 1, $this->x >> 4)->getEntities(),
				$level->getChunk(($this->x >> 4) - 1, $this->x >> 4)->getEntities(),
				$level->getChunk($this->x >> 4, ($this->x >> 4) + 1)->getEntities(),
				$level->getChunk(($this->x >> 4) + 1, ($this->x >> 4) + 1)->getEntities(),
				$level->getChunk(($this->x >> 4) - 1, ($this->x >> 4) + 1)->getEntities(),
				$level->getChunk($this->x >> 4, ($this->x >> 4) - 1)->getEntities(),
				$level->getChunk(($this->x >> 4) + 1, ($this->x >> 4) - 1)->getEntities(),
				$level->getChunk(($this->x >> 4) - 1, ($this->x >> 4) - 1)->getEntities()) as $entity){
				if($entity->getBoundingBox()->intersectsWith($this->getBoundingBox())){
					$meta++;
				}
			}
			$meta = (int) min($meta / $this->denominator, 15);
			if($meta !== $this->meta){
				if(($this->meta === 0) !== ($meta === 0)){ // !== faster than xor by 5% - does this make any difference at all?
					$this->boundingBox = null;
				}
				$this->meta = $meta;
			}
			if($meta !== 0){
				$this->getLevel()->scheduleUpdate($this, 10);
			}
		}
	}

	public function getName(){
		return "Weighted Pressure Plate (Light)";
	}
}
