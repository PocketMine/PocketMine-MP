<?php
namespace pocketmine\material;

use pocketmine\item\Item;

class Gold implements Material {

	public function getName(){
		return "Gold";
	}

	public function getItems(){
		return [
					Item::get(Item::GOLD_INGOT),
		 			Item::get(Item::GOLD_BLOCK),
		 			Item::get(Item::GOLD_ORE),
		 			Item::get(Item::GOLD_HELMET),
		 			Item::get(Item::GOLD_CHESTPLATE),
		 			Item::get(Item::GOLD_LEGGINGS),
		 			Item::get(Item::GOLD_BOOTS),
		 			Item::get(Item::GOLDEN_APPLE),
		 			Item::get(Item::GOLD_NUGGET)
		 		];
	}
}