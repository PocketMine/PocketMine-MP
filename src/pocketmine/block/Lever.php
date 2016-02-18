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
use pocketmine\math\Vector3;
use pocketmine\Player;

class Lever extends Flowable implements RedstonePowerSource, Attaching{
	protected $id = self::LEVER;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Lever";
	}

	public function getPowerLevel(){
		return $this->isActivated() ? 16 : 0;
	}

	public function isStronglyPowering(Block $block){
		if(!$this->isActivated()){
			return false;
		}
		return $block->equals(Vector3::getSide($this->getAttachSide()));
	}

	public function isActivated(){
		return (bool) ($this->meta & 0x08);
	}

	public function getAttachSide(){
		$faces = [
			0 => self::SIDE_UP,
			1 => self::SIDE_WEST,
			2 => self::SIDE_EAST,
			3 => self::SIDE_NORTH,
			4 => self::SIDE_SOUTH,
			5 => self::SIDE_DOWN,
			6 => self::SIDE_DOWN,
			7 => self::SIDE_UP
		];
		return $faces[(int) ($this->meta & 0x07)];
	}

	public function canAttachTo(Block $block){
		return !$block->isTransparent();
	}

	public function onActivate(Item $item, Player $player = null){
		$this->meta ^= 0x08;
		$this->getLevel()->setBlock($this, $this);
		return true;
	}

	public function canBeActivated(){
		return true;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($target->isTransparent()){
			return false;
		}
		$chooseMeta = [
			self::SIDE_DOWN => 0,
			self::SIDE_EAST => 1,
			self::SIDE_WEST => 2,
			self::SIDE_SOUTH => 3,
			self::SIDE_NORTH => 4,
			self::SIDE_UP => 5
		];
		$meta = $chooseMeta[$face];
		$this->processSide($meta, $player);
		$this->meta = $meta;
		$this->getLevel()->setBlock($this, $this);
		return true;
	}

	protected function processSide(&$meta, Player $player = null){
		if($player !== null and ($meta === 0 or $meta === 5)){
			$rotation = ($player->yaw - 90) % 360;
			if($rotation < 0){
				$rotation += 360.0;
			}
			$isVertical = false;
			if((135 <= $rotation and $rotation < 225) or (0 <= $rotation and $rotation < 45) or (315 <= $rotation and $rotation < 360)){
				$isVertical = true;
			}
			if($isVertical){
				$meta = ($meta === 0 ? 7 : 6);
			}
		}
	}

	public function getPoweringSides(){
		return [$this->getAttachSide()];
	}
}
