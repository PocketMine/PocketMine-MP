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

class SaplingBlock extends FlowableBlock{
	const OAK = 0;
	const SPRUCE = 1;
	const BIRCH = 2;
	const BURN_TIME = 5;
	
	public function __construct($meta = Sapling::OAK){
		parent::__construct(SAPLING, $meta, "Sapling");
		$this->isActivable = true;
		$names = array(
			0 => "Oak Sapling",
			1 => "Spruce Sapling",
			2 => "Birch Sapling",
		);
		$this->name = $names[$this->meta & 0x03];
	}
	
	public function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$down = $this->getSide(0);
		if($down->getID() === GRASS or $down->getID() === DIRT or $down->getID() === FARMLAND){
			$this->level->setBlock($block, $this);
			$this->level->scheduleBlockUpdate(new Position($this, 0, 0, $this->level), Utils::getRandomUpdateTicks(), BLOCK_UPDATE_RANDOM);
			return true;
		}
		return false;
	}
	
	public function onActivate(Item $item, Player $player){
		if($item->getID() === DYE and $item->getMetadata() === 0x0F){ //Bonemeal
			TreeObject::growTree($this->level, $this, new Random(), $this->meta & 0x03);
			if(($player->gamemode & 0x01) === 0){
				$item->count--;
			}
			return true;
		}
		return false;
	}
	public function onUpdate($type){
		if($type === BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->isTransparent === true){ //Replace wit common break method
				ServerAPI::request()->api->entity->drop($this, BlockAPI::getItem($this->id));
				$this->level->setBlock($this, new AirBlock(), false);
				return BLOCK_UPDATE_NORMAL;
			}
		}elseif($type === BLOCK_UPDATE_RANDOM){ //Growth
			if(mt_rand(1,7) === 1){
				if(($this->meta & 0x08) === 0x08){
					TreeObject::growTree($this->level, $this, new Random(), $this->meta & 0x03);
				}else{
					$this->meta |= 0x08;
					$this->level->setBlock($this, $this);
					return BLOCK_UPDATE_RANDOM;
				}
			}else{
				return BLOCK_UPDATE_RANDOM;
			}
		}
		return false;
	}
	
	public function getDrops(Item $item, Player $player){
		return array(
			array($this->id, $this->meta & 0x03, 1),
		);
	}
}