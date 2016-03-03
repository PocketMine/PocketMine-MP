<?php
namespace pocketmine\material;

use pocketmine\item\Item;

class Emerald implements Material {

	public function getName(){
		return "Emerald";
	}

	public static function getItems(){
		return [
					Item::get(Item::EMERALD),
		 			Item::get(Item::EMERALD_BLOCK),
		 			Item::get(Item::EMERALD_ORE)//So no villager trading = Emerald useless!
		 		]
	}
}