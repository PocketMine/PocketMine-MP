<?php
namespace pocketmine\material;

use pocketmine\item\Item;

class Wood implements Material {

	public function getName(){
		return "Wood";
	}

	public static function getItems(){
		return [
					Item::get(Item::WOOD),
		 			Item::get(Item::WOOD2),
		 			Item::get(Item::WOODEN_PLANK),
		 			Item::get(Item::WOOD_STAIRS),
		 			Item::get(Item::OAK_WOOD_STAIRS),
		 			Item::get(Item::WOOD_DOOR_BLOCK),
		 			Item::get(Item::WOODEN_DOOR),
		 			Item::get(Item::STICK),
		 			Item::get(Item::SPRUCE_WOOD_STAIRS),
		 			Item::get(Item::BIRTCH_WOOD_STAIRS),
		 			Item::get(Item::DOUBLE_WOOD_SLAB),
		 			Item::get(Item::WOOD_SLAB),
		 			Item::get(Item::ACACIA_WOOD_STAIRS),
		 			Item::get(Item::DARK_OAK_WOOD_STAIRS),
		 			Item::get(Item::LOG),
		 			Item::get(Item::LOG2),
		 			Item::get(Item::WOODEN_SWORD),
		 			Item::get(Item::WOODEN_PICKAXE),
		 			Item::get(Item::WOODEN_AXE),
		 			Item::get(Item::WOODEN_SHOVEL),
		 			Item::get(Item::WOODEN_HOE)
		 		]
	}
}