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

use pocketmine\level\Level;

class RedstoneDust extends Flowable implements RedstoneTransmitter, Attaching{

	protected $id = self::REDSTONE_DUST;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Redstone Dust";
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			for($side = self::SIDE_DOWN; $side <= self::SIDE_EAST; $side++){
				$block = $this->getSide($side);
				if($block instanceof RedstonePowerSource and $block->getPowerLevel() > $this->getPowerLevel()){
					$this->meta = $block->getPowerLevel() - 1;
					$this->level->setBlock($this, $this);
				}elseif($block->getPowerType() === Block::POWER_STRONG and $this->meta !== 0x0F){
					$this->meta = 0x0F;
					$this->level->setBlock($this, $this);
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
		return $block->isTransparent();
	}

	public function isPowering(Block $block){
		// TODO: Implement isPowering() method.
		return true;
	}
}
