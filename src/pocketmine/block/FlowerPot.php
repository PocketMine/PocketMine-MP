<?php

namespace pocketmine\block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
class FlowerPot extends Solid{
	protected $id = self::FLOWER_POT;
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
