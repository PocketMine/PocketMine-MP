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

use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;

class StonePressurePlate extends Transparent implements RedstonePowerSource, Attaching{
	protected $id = self::STONE_PRESSURE_PLATE;
	protected $lastCollide = 0;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getAttachSide(){
		return self::SIDE_DOWN;
	}

	public function canAttachTo(Block $block){
		return !$block->isTransparent() or (
			($block instanceof Slab and ($block->getDamage() & 8))
			or $block instanceof Fence
			or ($block instanceof Stair and ($block->getDamage() & 4))
			or $block instanceof NetherBrickFence
		);
	}

	public function getPowerLevel(){
		return (($this->meta & 8) > 0) ? 16 : 0;
	}

	public function isStronglyPowering(Block $block){
		return (($this->meta & 8) > 0) and $this->subtract(0, 1, 0)->equals($block);
	}

	public function onEntityCollide(Entity $entity){
		if($this->acceptsEntity($entity) and $this->getBoundingBox()->intersectsWith($entity->getBoundingBox())){
			$this->lastCollide = $this->getLevel()->getServer()->getTick();
			if(!($this->meta & 8)){
				$this->meta |= 8;
				$this->boundingBox = null;
				$this->getLevel()->setBlock($this, $this);
				$this->getLevel()->scheduleUpdate($this, 20);
			}
		}
	}

	protected function acceptsEntity(Entity $entity){
		return $entity instanceof Creature;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			if($this->getLevel()->getServer()->getTick() - $this->lastCollide < 10){
				$this->getLevel()->scheduleUpdate($this, 10);
			}
			$this->meta ^= 8;
			$this->boundingBox = null;
			$this->getLevel()->setBlock($this, $this);
		}
	}

	public function getName(){
		return "Stone Pressure Plate";
	}

	public function hasEntityCollision(){
		return true;
	}

	public function recalculateBoundingBox(){
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + (($this->meta & 8) ? 0.03125 : 0.0625),
			$this->z + 1
		);
	}

	public function canPassThrough(){
		return true;
	}

	public function getPoweringSides(){
		return [self::SIDE_DOWN];
	}
}
