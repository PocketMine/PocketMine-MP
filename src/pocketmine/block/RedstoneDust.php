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

class RedstoneDust extends Flowable implements RedstoneConnector, Attaching{

	protected $id = self::REDSTONE_DUST;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Redstone Dust";
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$maxPower = 0;
			for($side = 0; $side <= 5; $side++){
				$block = $this->getSide($side);
				if($block instanceof RedstoneConductor){
					$maxPower = max($maxPower, $block->getPowerLevel() - 1); // pass current from adjacent redstone conductor with voltage decrease
				}elseif($block->getPowerType() === Block::POWER_STRONG){
					$maxPower = 0x0F; // [wire] [block] [attached power source]
					break;
				}else{ // check for XY/ZY-diagonal current sources
					if(!$block->isTransparent()){ // check for possible downward delivery
						$up = $block->getSide(self::SIDE_UP);
						if($up instanceof RedstoneDust and $this->getSide(self::SIDE_UP)->isTransparent()){ // not blocked by opaque block like "tripping the knight's leg" in Chinese Chess
							$maxPower = max($maxPower, $up->getPowerLevel() - 1);
						}
					}else{ // if the adjacent block is transparent, i.e. upward delivery is possible
						$down = $block->getSide(self::SIDE_DOWN);
						if($down instanceof RedstoneDust){ // upward delivery
							$maxPower = max($maxPower, $down->getPowerLevel() - 1);
						}
					}
				}
			}
			if($maxPower !== $this->meta){
				$this->meta = $maxPower;
				$this->getLevel()->setBlock($this, $this);
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
		if($block->x - $this->x === 1){
			$blockSide = self::SIDE_EAST;
		}elseif($this->x - $block->x === 1){
			$blockSide = self::SIDE_WEST;
		}elseif($block->z - $this->z === 1){
			$blockSide = self::SIDE_SOUTH;
		}elseif($this->z - $block->z === 1){
			$blockSide = self::SIDE_NORTH;
		}else{
			return false;
		}
		for($i = 2; $i <= 5; $i++){
			$side = $this->getSide($i);
			if($side instanceof RedstoneConductor){
				if(($side & 4) !== ($blockSide & 4)){
					return false;
				}
			}
		}

		return true;
	}

	public function getDrops(Item $item){
		return [
			[Item::REDSTONE, 0, 1]
		];
	}
}
