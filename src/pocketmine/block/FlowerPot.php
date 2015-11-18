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


class FlowerPot extends Transparent{
	protected $id = self::FLOWER_POT_BLOCK;
	
	public function __construct(){
	}
	
	public function getName(){
		return "Flower Pot";
	}
	
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}
	
	public function getHardness(){
		return 5;
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$this->getLevel()->setBlock($block, $this, true, true);
		$nbt = new Compound("", [
			new String("id", Tile::FLOWER_POT),
			new Int("id", $this->id),
			new Int("data", $this->data),
			new Int("x", $this->x),
			new Int("y", $this->y),
			new Int("z", $this->z)
		]);

		Tile::createTile(Tile::FLOWER_POT, $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);

		return true;
	}
	
	public function onBreak(Item $item){
		$this->getLevel()->setBlock($this, new Air(), true, true);

		return true;
	}
	
	public function getDrops(Item $item){
		if($item->isPickaxe() >= 3){
			return [
				[Item::FLOWER_POT, 0, 1],
			];
		}else{
			return [];
		}
	}
}