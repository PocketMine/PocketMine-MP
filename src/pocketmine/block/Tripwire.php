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

use pocketmine\entity\Arrow;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;

class Tripwire extends Transparent{
	const FLAG_COLLIDED = 0x1;
	/** @deprecated */
	const FLAG_SUSPENDING = 0x2;
	const FLAG_ATTACHED = 0x4;
	const FLAG_DISARMED = 0x8;

	protected $id = self::TRIPWIRE;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
//			if(($this->meta & self::FLAG_SUSPENDING) > 0 !== $this->getSide(self::SIDE_DOWN)->isTransparent()){
//				$this->meta ^= self::FLAG_SUSPENDING;
//				$changed = true;
//			}
			if(isset($changed)){
				$this->getLevel()->setBlock($this, $this);
				$this->boundingBox = null;
			}
		}elseif($type === Level::BLOCK_UPDATE_SCHEDULED){
			foreach($this->getLevel()->getNearbyEntities($this->getBoundingBox()) as $entity){
				if($this->filterEntity($entity)){
					$this->getLevel()->scheduleUpdate($this, 10);
					$this->generatePulse();
				}
			}
		}
	}

	public function onEntityCollide(Entity $entity){
		if(!$this->filterEntity($entity)){
			return;
		}
		$this->getLevel()->scheduleUpdate($this, 10); // put this in front of generatePulse so that the entity collision check happens before the tripwire hook is checked, prevents needless self-reverting on the server.
		$this->generatePulse();
	}

	public function recalculateBoundingBox(){
		return new AxisAlignedBB($this->x, $this->y, $this->z, $this->x + 1, $this->y + (
//			(($this->meta & self::FLAG_SUSPENDING) > 0) ? 0.21875 :
			0.09375)
			, $this->z + 1);
	}

	public function canPassThrough(){
		return true;
	}

	public function onBreak(Item $item){
		parent::onBreak($item);
		if(!$item->isShears()){
			$this->generatePulse();
		}
	}

	protected function generatePulse($ticks = 10){
		$circuits = $this->getCircuits();
		foreach($circuits as $circuit){
			foreach($circuit as $hook){
				$hook->trigger($ticks);
			}
		}
	}

	/**
	 * Returns an array of circuits.
	 * Each "circuit" is an array of two elements, from one tripwire hook to another
	 *
	 * @return TripwireHook[][]
	 */
	protected function getCircuits(){
		/** @type TripwireHook[] $hooks */
		$hooks = [];
		for($side = self::SIDE_NORTH; $side <= self::SIDE_EAST; $side++){
			for($steps = 1; $steps <= 40; $steps++){
				$next = $this->getSide($side, $steps);
				if($next instanceof Tripwire){
					continue;
				}
				if($next instanceof TripwireHook and $next->getAttachSide() === self::getOppositeSide($side)){
					$hooks[$side] = $next;
				}
				break;
			}
		}
		$output = [];
		if(isset($hooks[self::SIDE_NORTH], $hooks[self::SIDE_SOUTH])
			and $hooks[self::SIDE_NORTH]->distanceSquared($hooks[self::SIDE_SOUTH]) <= 1764
		){
			$output[] = [$hooks[self::SIDE_NORTH], $hooks[self::SIDE_SOUTH]];
		}
		if(isset($hooks[self::SIDE_WEST], $hooks[self::SIDE_EAST])
			and $hooks[self::SIDE_WEST]->distanceSquared($hooks[self::SIDE_EAST]) <= 1764
		){
			$output[] = [$hooks[self::SIDE_WEST], $hooks[self::SIDE_EAST]];
		}
		return $output;
	}

	private function filterEntity(Entity $entity){
		return !($entity instanceof Arrow);
	}
}
