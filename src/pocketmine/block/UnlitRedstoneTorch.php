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

class UnlitRedstoneTorch extends RedstoneTorch{

	protected $id = self::UNLIT_REDSTONE_TORCH;

	public function onUpdate($type){
		parent::onUpdate($type);
		if(($type === Level::BLOCK_UPDATE_REDSTONE or $type === Level::BLOCK_UPDATE_SCHEDULED) and !$this->getSide($this->getAttachSide())->isRedstoneActivated()){
			$this->getLevel()->setBlock($this, new RedstoneTorch($this->getDamage()));
		}
	}

	public function getPowerLevel(){
		return 0;
	}

	public function isStronglyPowering(Block $block){
		return false;
	}
}
