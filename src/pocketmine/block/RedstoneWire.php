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
			// downward delivery occurs when there is an adjacent transparent block (but it appears that TNT isn't transparent?)
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
//				foreach($this->getPoweringSides() as $block){
//					$this->getLevel()->scheduleUpdateAround($block, 2);
//				}
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
		return !$block->isTransparent() or (
			$block instanceof Glowstone
			or ($block instanceof Slab and ($block->getDamage() & 8))
			or ($block instanceof Stair and ($block->getDamage() & 4))
		);
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

		if($block->y == $this->y){
			if($block->x == $this->x){
				if($block->z == $this->z - 1 or $block->z == $this->z + 1){
					$opponents = [self::SIDE_WEST, self::SIDE_EAST];
				}
			}
			if($block->z == $this->z){
				if($block->x == $this->x - 1 or $block->x == $this->x + 1){
					$opponents = [self::SIDE_NORTH, self::SIDE_SOUTH];
				}
			}
		}
		if(!isset($opponents)){
			return false; // not a horizontally adjacent block
		}

		foreach($opponents as $side){
			$sideBlock = $this->getSide($side);
			if($sideBlock instanceof RedstoneConductor){
				$sideConnected = true; // condition 3. a)
				break;
			}
			if($this->canAttachTo($sideBlock)){
				if($sideBlock->getSide(self::SIDE_UP) instanceof RedstoneWire){
					$sideConnected = true; // upward delivery possible
					break;
				}
				if($sideBlock instanceof Slab and $sideBlock->getSide(self::SIDE_DOWN) instanceof RedstoneWire){
					if(!$this->getSide($this->getAttachSide())->isTransparent()){
						$sideConnected = true;// downward delivery possible
						break;
					}
				}
			}else{
				if($sideBlock->getSide(self::SIDE_DOWN) instanceof RedstoneWire){
					if(!$this->getSide($this->getAttachSide())->isTransparent()){
						$sideConnected = true; // downward delivery possible
						break;
					}
				}
			}
		}

		return !isset($sideConnected);
	}

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
		return array_keys($output);
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
		foreach($this->getPoweringSides() as $side){
			$this->getLevel()->scheduleUpdateAround($this->getSide($side), 2);
		}

		return $output;
	}

	public function getDrops(Item $item){
		return [
			[Item::REDSTONE, 0, 1]
		];
	}
}
