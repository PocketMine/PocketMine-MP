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
			for($side = self::SIDE_DOWN; $side <= self::SIDE_EAST; $side++){
				$block = $this->getSide($side);
				if($block instanceof RedstoneTransmitter){
					$maxPower = max($maxPower, $block->getPowerLevel() - 1);
				}elseif($block->getPowerType() === Block::POWER_STRONG){
					$maxPower = 0x0F;
					break;
				}else{
					if(!$block->isTransparent()){
						$up = $block->getSide(self::SIDE_UP);
						if($up instanceof RedstoneDust and $this->getSide(self::SIDE_UP)->isTransparent()){
							$maxPower = max($maxPower, $up->getPowerLevel() - 1);
						}
					}else{
						$down = $block->getSide(self::SIDE_DOWN);
						if($down instanceof RedstoneDust){
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
		// TODO: Implement isPowering() method.
		return true;
	}

	public function getDrops(Item $item){
		return [
			[Item::REDSTONE, 0, 1]
		];
	}
}
