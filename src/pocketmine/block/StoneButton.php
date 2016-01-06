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
use pocketmine\Player;

class StoneButton extends Lever{
	protected $id = self::STONE_BUTTON;

	public function getName(){
		return "Stone Button";
	}

	public function getAttachSide(){
		return self::getOppositeSide($this->meta & 7);
	}

	public function onActivate(Item $item, Player $player = null){
		if($this->isActivated()){
			return true;
		}
		$this->meta |= 0x08;
		$this->getLevel()->setBlock($this, $this);
		$this->getLevel()->scheduleUpdate($this, $this->getDelay());
		return true;
	}

	protected function getDelay(){
		return 20;
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			$this->meta &= 0x07;
			$this->getLevel()->setBlock($this, $this);
			$this->getLevel()->updateAround($this->getSide($this->getAttachSide()), Level::BLOCK_UPDATE_REDSTONE);
		}
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($target->isTransparent()){
			return false;
		}
		$meta = $face;
		$this->meta = $meta;
		$this->getLevel()->setBlock($this, $this);
		return true;
	}

	public function onBreak(Item $item){
		$ret = parent::onBreak($item);
		$this->getLevel()->updateAround($this->getSide($this->getAttachSide()));
		return $ret;
	}
}
