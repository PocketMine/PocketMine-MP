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

class TrappedChest extends Chest implements RedstonePowerSource{

	protected $id = self::TRAPPED_CHEST;
	protected $lastPower = 0;

	public function getName(){
		return "Trapped Chest";
	}

	public function getPowerLevel(){
		return $this->lastPower;
	}

	public function isStronglyPowering(Block $block){
		return $this->subtract(0, 1)->equals($block) and !$block->isTransparent();
	}

	public function countViewers(){
		$chest = $this->getTile();
		return count($chest->getInventory()->getViewers());
	}

	public function recalculatePower(){
		$new = max(min($this->countViewers(), 15), 0);
		if($this->lastPower !== $new){
			$this->lastPower = $new;
			$this->getLevel()->updateAround($this, Level::BLOCK_UPDATE_REDSTONE);
		}
	}

	public function getPoweringSides(){
		return [self::SIDE_DOWN];
	}
}
