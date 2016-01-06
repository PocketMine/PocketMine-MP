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
use pocketmine\Player;

class TripwireHook extends Flowable implements RedstonePowerSource, Attaching{
	protected $id = self::TRIPWIRE_HOOK;
	protected $triggedUntil = 0;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getPowerLevel(){
		return ($this->triggedUntil > $this->getLevel()->getServer()->getTick()) ? 16 : 0;
	}

	public function getPoweringSides(){
		return [$this->getAttachSide()];
	}

	public function getAttachSide(){
		switch($this->meta & 3){
			case 0:
				return self::SIDE_NORTH;
			case 1:
				return self::SIDE_EAST;
			case 2:
				return self::SIDE_SOUTH;
			case 3:
				return self::SIDE_WEST;
		}
		assert(false, "Meta is not an integer");
		return 0;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($face === self::SIDE_SOUTH){
			$this->meta = 0;
		}elseif($face === self::SIDE_WEST){
			$this->meta = 1;
		}elseif($face === self::SIDE_NORTH){
			$this->meta = 2;
		}elseif($face === self::SIDE_EAST){
			$this->meta = 3;
		}else{
			return false;
		}
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}

	public function canAttachTo(Block $block){
		return !$block->isTransparent();
	}

	public function trigger($ticks = 10){
		$this->triggedUntil = max($this->triggedUntil, $this->getLevel()->getServer()->getTick() + $ticks);
		$this->getLevel()->updateAround($this, Level::BLOCK_UPDATE_REDSTONE);
		$this->getLevel()->updateAround($this->getSide($this->getAttachSide()), Level::BLOCK_UPDATE_REDSTONE);
		$this->getLevel()->scheduleUpdate($this, $ticks);
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			if($this->triggedUntil > $this->getLevel()->getServer()->getTick()){ // or should I use >= here?
				$this->getLevel()->updateAround($this, Level::BLOCK_UPDATE_REDSTONE);
				$this->getLevel()->updateAround($this->getSide($this->getAttachSide()), Level::BLOCK_UPDATE_REDSTONE);
			}
		}
	}

	public function isStronglyPowering(Block $block){
		return $block->equals(Vector3::getSide($this->getAttachSide()));
	}
}
