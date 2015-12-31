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
use pocketmine\tile\Chest as TileChest;

class TrappedChest extends Chest implements RedstonePowerSource{

	protected $id = self::TRAPPED_CHEST;

	public function getName(){
		return "Trapped Chest";
	}

	public function getPowerLevel(){
		return $this->isActivated() ? 16 : 0;
	}

	public function isStronglyPowering(Block $block){
		return false;
	}

	public function isActivated(){
		$chest = $this->getTile();
		return count($chest->getInventory()->getViewers()) > 0;
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			if($this->isActivated()){
				$this->getLevel()->scheduleUpdate($this, 20);
			}else{
				$this->getLevel()->updateAround($this, Level::BLOCK_UPDATE_REDSTONE);
			}
		}
	}

	public function onActivate(Item $item, Player $player = null){
		parent::onActivate($item, $player);
		if($this->getPowerLevel() > 0){
			$this->getLevel()->updateAround($this, Level::BLOCK_UPDATE_REDSTONE);
			$this->getLevel()->scheduleUpdate($this, 20);
		}else{
			$this->getLevel()->updateAround($this, Level::BLOCK_UPDATE_REDSTONE);
		}
		return true;
	}
}