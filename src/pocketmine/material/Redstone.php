<?php
namespace pocketmine\material;

use pocketmine\item\Item;

class Redstone implements Material {

	public function getName(){
		return "Redstone";
	}

	public function getItems(){
		return [
					Item::get(Item::REDSTONE),
		 			Item::get(Item::REDSTONE_BLOCK),
		 			Item::get(Item::REDSTONE_ORE),
		 			Item::get(Item::GLOWING_REDSTONE_ORE)
		 		]
	}
}