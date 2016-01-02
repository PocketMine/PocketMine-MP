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
		//print "north \n";
		if ($this->tryLevelAboveOrBelow($this->x, $this->y, $this->z + $speed)) {
			$this->yaw = 0;
			return true;
		}
		return false;
	}
	

	
	public function trySouth($speed) {
		//print "south \n";
		if ($this->tryLevelAboveOrBelow($this->x, $this->y, $this->z - $speed)) {
			$this->z = $this->z - $speed;
			$this->yaw = 180;
			return true;
		}
		return false;
	}
	
	
	public function tryEast($speed) {
		//print "east \n";
		if ($this->tryLevelAboveOrBelow($this->x - $speed, $this->y, $this->z)) {
			$this->x = $this->x - $speed;
			$this->yaw = 90;
			return true;
		}
		return false;
	}
	
	public function tryWest($speed) {
		//print "west \n";
		if ($this->tryLevelAboveOrBelow($this->x + $speed, $this->y, $this->z)) {
			$this->x = $this->x + $speed;
			$this->yaw = 270;
			return true;
		}
		return false;
	}
	
	public function tryNorthEast($speed) {
		//print "northeast \n";
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
		//print "northwest \n";
		if ($this->tryLevelAboveOrBelow($this->x + $speed, $this->y, $this->z + $speed)) {
			//print "Can go northwest \n";
			$this->z = $this->z + $speed;
			$this->x = $this->x + $speed;
			$this->yaw = 315;
		} else if ($this->tryNorth($speed)) {
			//print "Can go north \n";
			return true;
		} else if ($this->tryWest($speed)) {
			//print "Can go west \n";
			return true;
		} else {
			//print "Cant move desired direction \n";
			return false;
		}
	}
	
	public function trySouthEast($speed) {
		//print "southEast \n";
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
		//print "southWest \n";
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
		//print "checkIfOnBlockButNotInsideBlock \n";
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
			//print "Not on block or inside block \n";
			return true;
		} else {
			$id1 = $this->level->getBlockIdAt($ax, $y - 1, $az);
			$id2 = $this->level->getBlockIdAt($ax, $y, $az);
			$id3 = $this->level->getBlockIdAt($ax, $y+1, $az);
			//print "Either wouldn't be on block or would be inside blocks $id1 $id2 $id3\n";
			return false;
		}
	}
	
	private function tryLevelAboveOrBelow($x, $y, $z) {
// 		print "tryLevelAboveOrBelow $x $y $z \n";
		// 		print "Where monster is at: \n";
		// 		$this->printSurroundingBlocks($x, $y, $z);
		// 		print "x-1: \n";
		// 		$this->printSurroundingBlocks($x-1, $y, $z);
		// 		print "x+1: \n";
		// 		$this->printSurroundingBlocks($x+1, $y, $z);
		// 		print "z-1: \n";
		// 		$this->printSurroundingBlocks($x, $y, $z-1);
		// 		print "z+1: \n";
		// 		$this->printSurroundingBlocks($x, $y, $z+1);
	
		//print "tryLevelAboveOrBelow \n";
		if ($this->checkIfOnBlockButNotInsideBlock($x, $y, $z)) {
			//print "Can move at level \n";
			$this->x = $x;
			$this->y = $y;
			$this->z = $z;
			return true;
		} else if ($this->checkIfOnBlockButNotInsideBlock($x, $y-1, $z)) {
			//print "Can move at level -1 \n";
			$this->x = $x;
			$this->y = $y-1;
			$this->z = $z;
			return true;
		} else if ($this->checkIfOnBlockButNotInsideBlock($x, $y+1, $z)) {
			//print "Can move at level +1 \n";
			$this->x = $x;
			$this->y = $y+1;
			$this->z = $z;
			return true;
		} else {
			//print "Cant move in desired direction at any level \n";
			return false;
		}
	}
	
	private function printSurroundingBlocks($x, $y, $z) {
		$standingOn = $this->level->getBlockIdAt($x, $y - 1, $z);
		$feetIn = $this->level->getBlockIdAt($x, $y, $z);
		$headIn = $this->level->getBlockIdAt($x, $y+1, $z);
		print "Standing On: $standingOn, Feet In: $feetIn Head In: $headIn \n";
	}
	
	// 	public function onBlockButNotInsideBlocks() {
	// 		return $this->checkIfOnBlockButNotInsideBlock($this->x, $this->y, $this->z);
	// 	}
	
	
// 	public function onBlock() {
// 		if($this->level->getBlockIdAt($this->x, $this->y - 1, $this->z) != Block::AIR){
// 			return true;
// 		} else {
// 			return false;
// 		}
// 	}
	
// 	public function insideBlocks() {
// 		if($this->level->getBlockIdAt($this->x, $this->y, $this->z) == Block::AIR &&
// 				$level->getBlockIdAt($this->x, $this->y+1, $this->z) == Block::AIR){
// 			return false;
// 		} else {
// 			return true;
// 		}
// 	}
	
// 	public function ableToFallToBlock() {
// 		$oldY = $this->y;
// 		$i = 0;
// 		do {
// 			$this->y = $this->y - 1;
// 			$i = $i +1;
// 		} while ($i < 2 && !$this->checkIfOnBlockButNotInsideBlock());
// 		if ($this->checkIfOnBlockButNotInsideBlock()) {
// 			return true;
// 		} else {
// 			$this->y = $oldY;
// 			return false;
// 		}
// 	}
	
// 	public function ableToJumpToBlock() {
// 		$oldY = $this->y;
// 		$i = 0;
// 		do {
// 			$this->y = $this->y + 1;
// 			$i = $i + 1;
// 		} while ($i < 1 && !$this->checkIfOnBlockButNotInsideBlock());
// 		if ($this->checkIfOnBlockButNotInsideBlock()) {
// 			return true;
// 		} else {
// 			$this->y = $oldY;
// 			return false;
// 		}
// 	}
	
// 	public function getBlock() {
// 		$b = $this->getBlockAtPosition($this->x, $this->y, $this->z);
// 	}
	
// 	private function getBlockAtPosition($x, $y, $z) {
// 		//print "PoistionAndOrientation 12 \n";
// 		$v = new Vector3($x, $y, $z);
// 		print "PoistionAndOrientation 12a \n";
// 		$b = $level->getBlock($v);
// 		print "PoistionAndOrientation 12b \n";
// 		return $b;
// 	}

}
