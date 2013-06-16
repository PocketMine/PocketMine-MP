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

class LeavesBlock extends TransparentBlock{
	const OAK = 0;
	const SPRUCE = 1;
	const BIRCH = 2;
	public function __construct($meta = 0){
		parent::__construct(LEAVES, $meta, "Leaves");
		$names = array(
			LeavesBlock::OAK => "Oak Leaves",
			LeavesBlock::SPRUCE => "Spruce Leaves",
			LeavesBlock::BIRCH => "Birch Leaves",
			3 => "",
		);
		$this->name = $names[$this->meta & 0x03];
	}
	
	private function findLog(Block $pos, array $visited, $distance){
		$index = $pos->x.".".$pos->y.".".$pos->z;
		if(isset($visited[$index])){
			return false;
		}
		if($pos->getID() === WOOD){
			return true;
		}elseif($pos->getID() === LEAVES and $distance < 5){
			$visited[$index] = true;
			$down = $pos->getSide(0)->getID();
			if($down === WOOD or $down == LEAVES){
				return true;
			}
			for($side = 2; $side <= 5; ++$side){
				if($this->findLog($pos->getSide($side), $visited, $distance + 1) === true){
					return true;
				}
			}
		}

		return false;
	}
	
	public function onUpdate($type){
		if($type === BLOCK_UPDATE_NORMAL){
			if(($this->meta & 0b00001100) === 0){
				$this->meta |= 0x08;
				$this->level->setBlock($this, $this, false);
				return BLOCK_UPDATE_RANDOM;
			}
		}elseif($type === BLOCK_UPDATE_RANDOM){
			if(($this->meta & 0b00001100) === 0x08){
				$this->meta &= 0x03;
				$visited = array();
				if($this->findLog($this, $visited, 0) === true){
					$this->level->setBlock($this, $this, false);
				}else{
					$this->level->setBlock($this, new AirBlock(), false);
					if(mt_rand(1,20) === 1){ //Saplings
						ServerAPI::request()->api->entity->drop($this, BlockAPI::getItem(SAPLING, $this->meta & 0x03, 1));
					}
					if(($this->meta & 0x03) === LeavesBlock::OAK and mt_rand(1,200) === 1){ //Apples
						ServerAPI::request()->api->entity->drop($this, BlockAPI::getItem(APPLE, 0, 1));
					}
					return BLOCK_UPDATE_NORMAL;
				}
			}
		}
		return false;
	}
	
	public function place(Item $item, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		$this->meta |= 0x04;
		$this->level->setBlock($this, $this);
	}
	
	public function getDrops(Item $item, Player $player){
		$drops = array();
		if(mt_rand(1,20) === 1){ //Saplings
			$drops[] = array(SAPLING, $this->meta & 0x03, 1);
		}
		if(($this->meta & 0x03) === LeavesBlock::OAK and mt_rand(1,200) === 1){ //Apples
			$drops[] = array(APPLE, 0, 1);
		}
		return $drops;
	}
}