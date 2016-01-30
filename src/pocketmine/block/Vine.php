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
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;

class Vine extends Transparent{

	protected $id = self::VINE;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function isSolid(){
		return false;
	}

	public function getName(){
		return "Vines";
	}

	public function getHardness(){
		return 0.2;
	}

	public function canPassThrough(){
		return true;
	}

	public function hasEntityCollision(){
		return true;
	}

	public function onEntityCollide(Entity $entity){
		$entity->resetFallDistance();
	}

	protected function recalculateBoundingBox(){

		$f1 = 1;
		$f2 = 1;
		$f3 = 1;
		$f4 = 0;
		$f5 = 0;
		$f6 = 0;

		$flag = $this->meta > 0;

		if(($this->meta & 0x02) > 0){
			$f4 = max($f4, 0.0625);
			$f1 = 0;
			$f2 = 0;
			$f5 = 1;
			$f3 = 0;
			$f6 = 1;
			$flag = true;
		}

		if(($this->meta & 0x08) > 0){
			$f1 = min($f1, 0.9375);
			$f4 = 1;
			$f2 = 0;
			$f5 = 1;
			$f3 = 0;
			$f6 = 1;
			$flag = true;
		}

		if(($this->meta & 0x01) > 0){
			$f3 = min($f3, 0.9375);
			$f6 = 1;
			$f1 = 0;
			$f4 = 1;
			$f2 = 0;
			$f5 = 1;
			$flag = true;
		}

		if(!$flag and $this->getSide(1)->isSolid()){
			$f2 = min($f2, 0.9375);
			$f5 = 1;
			$f1 = 0;
			$f4 = 1;
			$f3 = 0;
			$f6 = 1;
		}

		return new AxisAlignedBB(
			$this->x + $f1,
			$this->y + $f2,
			$this->z + $f3,
			$this->x + $f4,
			$this->y + $f5,
			$this->z + $f6
		);
	}


	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if(!$target->isTransparent() and $target->isSolid()){
			$faces = [
				0 => 0,
				1 => 0,
				2 => 1,
				3 => 4,
				4 => 8,
				5 => 2,
			];
			if(isset($faces[$face])){
				$this->meta = $faces[$face];
				$this->getLevel()->setBlock($block, $this, true, true);

				return true;
			}
		}

		return false;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$before = $this->meta;
			if($this->meta & 0x01){
				$target = $this->getSide(self::SIDE_SOUTH);
				if(($target->isTransparent() || !$target->isSolid()) && $this->getSide(self::SIDE_UP)->getId() !== self::VINE){
					$this->meta &= ~0x01;
				}
			}
			if($this->meta & 0x02){
				$target = $this->getSide(self::SIDE_NORTH);
				if(($target->isTransparent() || !$target->isSolid()) && $this->getSide(self::SIDE_UP)->getId() !== self::VINE){
					$this->meta &= ~0x02;
				}
			}
			if($this->meta & 0x04){
				$target = $this->getSide(self::SIDE_EAST);
				if(($target->isTransparent() || !$target->isSolid()) && $this->getSide(self::SIDE_UP)->getId() !== self::VINE){
					$this->meta &= ~0x04;
				}
			}
			if($this->meta & 0x08){
				$target = $this->getSide(self::SIDE_WEST);
				if(($target->isTransparent() || !$target->isSolid()) && $this->getSide(self::SIDE_UP)->getId() !== self::VINE){
					$this->meta &= ~0x08;
				}
			}
			
			if($this->meta != $before){
				$this->getLevel()->setBlock($this, $this->meta > 0 ? $this : new Air(), true);
				if($this->meta == 0){
					$players = $this->getLevel()->getChunkPlayers($this->x >> 4, $this->z >> 4);
					$this->getLevel()->addParticle(new DestroyBlockParticle($this->add(0.5), $this), $players);
				}
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function getDrops(Item $item){
		if($item->isShears()){
			return [
				[$this->id, 0, 1],
			];
		}else{
			return [];
		}
	}

	public function getToolType(){
		return Tool::TYPE_AXE;
	}
}