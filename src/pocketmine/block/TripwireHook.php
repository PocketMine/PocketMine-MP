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
use pocketmine\math\Vector3;

class TripwireHook extends Flowable{
	protected $id = self::TRIPWIRE_HOOK;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getHardness(){
		return 0;
	}

	public function isSolid(){
		return false;
	}

	public function getName(){
		return "Tripwire Hook";
	}

	public function getBoundingBox(){
		return null;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($face !== 0 && $face !== 1){
			$faces = [
				3 => 3,
				2 => 4,
				4 => 2,
				5 => 1,
			];
			$this->meta = $faces[$face];
			if($this->getSide(Vector3::getOppositeSide($face))->getId() === Block::TRIPWIRE){
				$this->meta & 0x01;
			}
			$this->getLevel()->setBlock($block, Block::get(Block::TRIPWIRE_HOOK, $this->meta), true);
			return true;
		}
		
		return false;
	}

	/*public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$extrabitset = (($this->meta & 0x01) === 0x01);
			if($extrabitset) $this->meta & ~0x01;
			if($this->getSide($this->meta)->isTransparent() === true){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
			elseif($extrabitset){
				$this->meta & 0x01;
			}
		}
		return false;
	}*/

	public function getDrops(Item $item){
		return [
			[Item::TRIPWIRE_HOOK, 0, 1],
		];
	}
	
}
