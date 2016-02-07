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

use pocketmine\item\Tool;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class RedstoneLamp extends Solid implements Redstone,RedstoneConsumer{

	protected $id = self::REDSTONE_LAMP;

	public function __construct(){

	}
	
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}
	
	public function onRedstoneUpdate($type, $power){
		if($type == Level::REDSTONE_UPDATE_BLOCK_UNCHARGE){
			return;
		}
		$isC=$this->isCharged();
		if($isC){
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_BLOCK_CHARGE,1);
			$this->id = 124;
			$this->getLevel()->setBlock($this, $this, true, false);
			return;
		}
		if($type == Level::REDSTONE_UPDATE_BLOCK_CHARGE or $this->isActivitedByRedstone()){
			$this->id = 124;
			$this->getLevel()->setBlock($this, $this, true, false);
			return;
		}
	}

	public function getName(){
		return "Redstone Lamp";
	}

	public function getHardness(){
		return 0.3;
	}
}
