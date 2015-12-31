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

class RedstoneWire extends Flowable implements RedstoneConnector, Attaching{

	protected $id = self::REDSTONE_WIRE;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Redstone Dust";
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if($type === Level::BLOCK_UPDATE_NORMAL or $type === Level::BLOCK_UPDATE_REDSTONE or $type === Level::BLOCK_UPDATE_SCHEDULED){
			$maxPower = 0;
			for($side = 0; $side <= 5; $side++){
				$block = $this->getSide($side);
				if($block instanceof RedstoneConductor){
					$maxPower = max($maxPower, $block->getPowerLevel() - 1); // Pass decreased power from adjacent conductor
				}elseif($block->getChargeType() === Block::CHARGE_STRONG){
					$maxPower = 0x0F; // When: [wire] [block] [attached power source]
					break;
				}else{ // Checks for XY/ZY-diagonal current sources
					if(!$block->isTransparent()){ // check for possible downward delivery
						$up = $block->getSide(self::SIDE_UP);
						if($up instanceof RedstoneWire and $this->getSide(self::SIDE_UP)->isTransparent()){ // not blocked by opaque block like "tripping the knight's leg" in Chinese Chess
							$maxPower = max($maxPower, $up->getPowerLevel() - 1);
						}
					}else{ // if the adjacent block is transparent, i.e. upward delivery is possible
						$down = $block->getSide(self::SIDE_DOWN);
						if($down instanceof RedstoneWire){ // upward delivery
							$maxPower = max($maxPower, $down->getPowerLevel() - 1);
						}
					}
				}
			}
			if($maxPower !== $this->meta){
				$this->meta = $maxPower;
				$this->getLevel()->setBlock($this, $this);
				foreach($this->getPoweringSides() as $block){
					$this->getLevel()->scheduleUpdateAround($block, 2);
				}
			}
		}
	}

	public function getPowerLevel(){
		return $this->meta;
	}

	public function getAttachSide(){
		return self::SIDE_DOWN;
	}

	public function canAttachTo(Block $block){
		return !$block->isTransparent() or $block instanceof Slab;
	}

	public function isPowering(Block $block){
		// a block is weakly charged by an adjacent (including vertically) powered redstone wire when one or more of the above is true:
		// 1. the block is directly below the redstone wire (the redstone wire is attached to it)
		// 2. the block itself is a redstone conductor
		// 3. a)    no redstone conductors form a horizontal right-angle with the block and the redstone wire
		//          (there is no block-wire-conductor horizontal right-angle)
		//          AND
		// 3. b)    no upward or downward delivery on the two sides (similar to 3)

		if($this->x == $block->x and $this->z == $block->z and $block->y + 1 == $this->y){
			return true; // condition 1
		}

		if($block instanceof RedstoneConductor){
			return true; // condition 2
		}

		$nsConducted = false;
		$weConducted = false;
		$blockSide = Vector3::SIDE_DOWN; // dummy value
		for($i = self::SIDE_NORTH; $i <= self::SIDE_EAST; $i++){
			$side = $this->getSide($i);
			if($side instanceof RedstoneConductor){
				if($i & 0x04){
					$weConducted = true;
				}else{
					$nsConducted = true;
				}
			}
			if($side->equals($block)){
				$blockSide = $i;
			}
		}

		// TODO fix condition 4

		// condition 3
		if($blockSide === self::SIDE_EAST or $blockSide === self::SIDE_WEST){ // block is at west/east
			return !$nsConducted; // if there are no conductors at north/south side
		}elseif($blockSide === self::SIDE_NORTH or $blockSide === self::SIDE_SOUTH){ // block is at north/west
			return !$weConducted; // if there are no conductors at west/east side
		}

		return false; // block is above or more than one block away
	}

	/**
	 * @return Block[]
	 */
	public function getPoweringSides(){
		$output = [self::SIDE_DOWN => $this->getSide(self::SIDE_DOWN)];
		$nsDone = false;
		$weDone = false;
		for($side = 2; $side <= 5; $side++){
			$block = $this->getSide($side);
			if($block instanceof RedstoneConductor){
				// redstone conductor blocks are not included
				if(($side & 4) === 0){
					$nsDone = true;
				}else{
					$weDone = true;
				}
			}
		}
		if(!$nsDone){
			$output[self::SIDE_EAST] = $this->getSide(self::SIDE_EAST);
			$output[self::SIDE_WEST] = $this->getSide(self::SIDE_WEST);
		}
		if(!$weDone){
			$output[self::SIDE_NORTH] = $this->getSide(self::SIDE_NORTH);
			$output[self::SIDE_SOUTH] = $this->getSide(self::SIDE_SOUTH);
		}
		return array_values($output);
	}

	public function onBreak(Item $item){
		$output = parent::onBreak($item);
		for($i = 2; $i <= 5; $i++){
			$side = Vector3::getSide($i);
			if(($block = $this->getLevel()->getBlock($side->add(0, 1))) instanceof RedstoneConnector){
				$block->onUpdate(Level::BLOCK_UPDATE_REDSTONE);
			}
			if(($block = $this->getLevel()->getBlock($side->subtract(0, 1))) instanceof RedstoneConnector){
				$block->onUpdate(Level::BLOCK_UPDATE_REDSTONE);
			}
		}
		foreach($this->getPoweringSides() as $block){
			$this->getLevel()->scheduleUpdateAround($block, 2);
		}

		return $output;
	}

	public function getDrops(Item $item){
		return [
			[Item::REDSTONE, 0, 1]
		];
	}
}
