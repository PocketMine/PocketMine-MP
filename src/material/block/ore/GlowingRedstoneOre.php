<?php

/*

           -
         /   \
      /         \
   /   PocketMine  \
/          MP         \
|\     @shoghicp     /|
|.   \           /   .|
| ..     \   /     .. |
|    ..    |    ..    |
|       .. | ..       |
\          |          /
   \       |       /
      \    |    /
         \ | /

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.


*/

class GlowingRedstoneOreBlock extends SolidBlock{
	public function __construct(){
		parent::__construct(GLOWING_REDSTONE_ORE, 0, "Glowing Redstone Ore");
	}

	public function onUpdate($type){
		if($type === BLOCK_UPDATE_SCHEDULED or $type === BLOCK_UPDATE_RANDOM){
			$this->level->setBlock($this, BlockAPI::get(REDSTONE_ORE, $this->meta), false);			
			return BLOCK_UPDATE_WEAK;
		}else{
			$this->level->scheduleBlockUpdate(new Position($this->x, $this->y, $this->z, $this->level), Utils::getRandomUpdateTicks(), BLOCK_UPDATE_RANDOM);
		}
		return false;
	}
	

	public function getBreakTime(Item $item, Player $player){
		if(($player->gamemode & 0x01) === 0x01){
			return 0.20;
		}		
		switch($item->isPickaxe()){
			case 5:
				return 0.6;
			case 4:
				return 0.75;
			default:
				return 15;
		}
	}
	
	public function getDrops(Item $item, Player $player){
		if($item->isPickaxe() >= 4){
			return array(
				//array(331, 4, mt_rand(4, 5)),
			);
		}else{
			return array();
		}
	}
	
}