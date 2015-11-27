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
use pocketmine\item\Tool;

class LitRedstoneLamp extends Solid{

	protected $id = self::LIT_REDSTONE_LAMP;

	public function __construct(){

	}

	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	public function getName(){
		return "Lit Redstone Lamp";
	}

	public function getHardness(){
		return 0.3;
	}
	
	public function onPlace(){ //onUpdate .... 
		$blockNorth = $this->getSide(2); //Gets the blocks around the lamp
		$blockSouth = $this->getSide(3);
		$blockEast = $this->getSide(5);
		$blockWest = $this->getSide(4);			
		if($blockNorth->getId() === Block::get(Item::REDSTONE_TORCH, 0)){
			$this->getLevel()->setBlock($this, Block::get(Item::REDSTONE_LAMP, 0), true, true);
			return true;
		}elseif($blockSouth->getId() === Block::get(Item::REDSTONE_TORCH, 0)){
			$this->getLevel()->setBlock($this, Block::get(Item::REDSTONE_LAMP, 0), true, true);
			return true;
		}elseif($blockEast->getId() === Block::get(Item::REDSTONE_TORCH, 0)){
			$this->getLevel()->setBlock($this, Block::get(Item::REDSTONE_LAMP, 0), true, true);
			return true;
		}else($blockWest->getId() === Block::get(Item::REDSTONE_TORCH, 0)){
			$this->getLevel()->setBlock($this, Block::get(Item::REDSTONE_LAMP, 0), true, true);
			return true;
		}
		return false;
	}
	
	public function getLightLevel(){
		return 15;
	}

	public function getDrops(Item $item){
		$drops = [];
		$drops[] = [Item::REDSTONE_LAMP, 0, 1];
		return $drops;
	}
}
