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

namespace pocketmine\entity;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

class PositionAndOrientation {

	public $x;
	public $y;
	public $z;
	public $level;
	public $yaw;
	
	public function __construct(Entity $entity){
		$this->x = $entity->getX();
		$this->y = $entity->getY();
		$this->z = $entity->getZ();
		$this->level = $entity->getLevel();
		$this->yaw = $entity->getYaw();
	}
	
	public function tryNorth($speed) {
		if ($this->tryLevelAboveOrBelow($this->x, $this->y, $this->z + $speed)) {
			$this->yaw = 0;
			return true;
		}
		return false;
	}
	

	
	public function trySouth($speed) {
		if ($this->tryLevelAboveOrBelow($this->x, $this->y, $this->z - $speed)) {
			$this->z = $this->z - $speed;
			$this->yaw = 180;
			return true;
		}
		return false;
	}
	
	
	public function tryEast($speed) {
		if ($this->tryLevelAboveOrBelow($this->x - $speed, $this->y, $this->z)) {
			$this->x = $this->x - $speed;
			$this->yaw = 90;
			return true;
		}
		return false;
	}
	
	public function tryWest($speed) {
		if ($this->tryLevelAboveOrBelow($this->x + $speed, $this->y, $this->z)) {
			$this->x = $this->x + $speed;
			$this->yaw = 270;
			return true;
		}
		return false;
	}
	
	public function tryNorthEast($speed) {
		if ($this->tryLevelAboveOrBelow($this->x - $speed, $this->y, $this->z + $speed)) {
			$this->z = $this->z + $speed;
			$this->x = $this->x - $speed;
			$this->yaw = 45;
			return true;
		} else if ($this->tryNorth($speed)) {
			return true;
		} else if ($this->tryEast($speed)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function tryNorthWest($speed) {
		if ($this->tryLevelAboveOrBelow($this->x + $speed, $this->y, $this->z + $speed)) {
			$this->z = $this->z + $speed;
			$this->x = $this->x + $speed;
			$this->yaw = 315;
		} else if ($this->tryNorth($speed)) {
			return true;
		} else if ($this->tryWest($speed)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function trySouthEast($speed) {
		if ($this->tryLevelAboveOrBelow($this->x - $speed, $this->y, $this->z - $speed)) {
			$this->z = $this->z - $speed;
			$this->x = $this->x - $speed;
			$this->yaw = 135;
		} else if ($this->trySouth($speed)) {
			return true;
		} else if ($this->tryEast($speed)) {
			return true;
		} else {
			return false;
		}	
	}
	
	public function trySouthWest($speed) {
		if ($this->tryLevelAboveOrBelow($this->x + $speed, $this->y, $this->z - $speed)) {
			$this->z = $this->z - $speed;
			$this->x = $this->x + $speed;
			$this->yaw = 225;
		} else if ($this->trySouth($speed)) {
			return true;
		} else if ($this->tryWest($speed)) {
			return true;
		} else {
			return false;
		}
	}
	
	private function checkIfOnBlockButNotInsideBlock($x, $y, $z) {
		$ax = $x;
		$az= $z;
		if ($ax < 0) {
			$ax = $ax - 1;
		}
		if ($az < 0) {
			$az = $az - 1;
		}
		
		if(($this->level->getBlockIdAt($ax, $y - 1, $az) != Block::AIR) &&
				$this->level->getBlockIdAt($ax, $y, $az) == Block::AIR &&
				$this->level->getBlockIdAt($ax, $y+1, $az) == Block::AIR){
			return true;
		} else {
			$id1 = $this->level->getBlockIdAt($ax, $y - 1, $az);
			$id2 = $this->level->getBlockIdAt($ax, $y, $az);
			$id3 = $this->level->getBlockIdAt($ax, $y+1, $az);
			return false;
		}
	}
	
	private function tryLevelAboveOrBelow($x, $y, $z) {
		if ($this->checkIfOnBlockButNotInsideBlock($x, $y, $z)) {
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
			return true;
		} else if ($this->checkIfOnBlockButNotInsideBlock($x, $y-1, $z)) {
			$this->x = $x;
			$this->y = $y-1;
			$this->z = $z;
			return true;
		} else if ($this->checkIfOnBlockButNotInsideBlock($x, $y+1, $z)) {
			$this->x = $x;
			$this->y = $y+1;
			$this->z = $z;
			return true;
		} else {
			return false;
		}
	}
	
	private function printSurroundingBlocks($x, $y, $z) {
		$standingOn = $this->level->getBlockIdAt($x, $y - 1, $z);
		$feetIn = $this->level->getBlockIdAt($x, $y, $z);
		$headIn = $this->level->getBlockIdAt($x, $y+1, $z);
	}
}
