<?php
namespace pocketmine\material;

use pocketmine\item\Item;

class Iron implements Material {

	public function getName(){
		return "Iron";
	}

	public static function getItems(){
		return [
					Item::get(Item::IRON_INGOT),
		 			Item::get(Item::IRON_BLOCK),
		 			Item::get(Item::IRON_ORE),
		 			Item::get(Item::IRON_HELMET),
		 			Item::get(Item::IRON_CHESTPLATE),
		 			Item::get(Item::IRON_LEGGINGS),
		 			Item::get(Item::IRON_BOOTS),
		 			Item::get(Item::ANVIL),
		 			Item::get(Item::IRON_BARS)
		 		];
	}
}