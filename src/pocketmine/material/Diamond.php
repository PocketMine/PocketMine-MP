<?php
namespace pocketmine\material;

use pocketmine\item\Item;

class Diamond implements Material {

	public function getName(){
		return "Diamond";
	}

	public static function getItems(){
		return [
					Item::get(Item::DIAMOND),
		 			Item::get(Item::DIAMOND_BLOCK),
		 			Item::get(Item::DIAMOND_ORE),
		 			Item::get(Item::DIAMOND_HELMET),
		 			Item::get(Item::DIAMOND_CHESTPLATE),
		 			Item::get(Item::DIAMOND_LEGGINGS),
		 			Item::get(Item::DIAMOND_BOOTS)
		 		];
	}
}