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
use pocketmine\Player;

class Torch extends Flowable implements Attaching{

	protected $id = self::TORCH;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getLightLevel(){
		return 15;
	}

	public function getName(){
		return "Torch";
	}

	public function getAttachSide(){
		$faces = [
			1 => 4,
			2 => 5,
			3 => 2,
			4 => 3,
			5 => 0,
			6 => 0,
			0 => 0,
		];

		return $faces[$this->getDamage()];
	}

	public function canAttachTo(Block $block){
		return !$block->isTransparent() or ($this->meta === 0 and ($block->getId() === self::FENCE or $block->getId() === self::COBBLE_WALL));
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$below = $this->getSide(0);

		if($target->isTransparent() === false and $face !== 0){
			$faces = [
				1 => 5,
				2 => 4,
				3 => 3,
				4 => 2,
				5 => 1,
			];
			$this->meta = $faces[$face];
			$this->getLevel()->setBlock($block, $this, true, true);

			return true;
		}elseif($below->isTransparent() === false or $below->getId() === self::FENCE or $below->getId() === self::COBBLE_WALL){
			$this->meta = 0;
			$this->getLevel()->setBlock($block, $this, true, true);

			return true;
		}

		return false;
	}

	public function getDrops(Item $item){
		return [
			[$this->id, 0, 1],
		];
	}
}
