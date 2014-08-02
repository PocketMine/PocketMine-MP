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

/**
 * All the Item classes
 */
namespace PocketMine\Item;
use PocketMine\Block\Block as Block;
use PocketMine\Entity\Entity as Entity;
use PocketMine\Item\Block as ItemBlock;
use PocketMine\Level\Level as Level;
use PocketMine\Recipes\Fuel as Fuel;
use PocketMine\Recipes\Smelt as Smelt;
use PocketMine;

class Item{
	//All Block IDs are here too
	const AIR = 0;
	const STONE = 1;
	const GRASS = 2;
	const DIRT = 3;
	const COBBLESTONE = 4;
	const COBBLE = 4;
	const PLANK = 5;
	const PLANKS = 5;
	const WOODEN_PLANK = 5;
	const WOODEN_PLANKS = 5;
	const SAPLING = 6;
	const SAPLINGS = 6;
	const BEDROCK = 7;
	const WATER = 8;
	const STILL_WATER = 9;
	const LAVA = 10;
	const STILL_LAVA = 11;
	const SAND = 12;
	const GRAVEL = 13;
	const GOLD_ORE = 14;
	const IRON_ORE = 15;
	const COAL_ORE = 16;
	const WOOD = 17;
	const TRUNK = 17;
	const LEAVES = 18;
	const LEAVE = 18;
	const SPONGE = 19;
	const GLASS = 20;
	const LAPIS_ORE = 21;
	const LAPIS_BLOCK = 22;

	const SANDSTONE = 24;

	const BED_BLOCK = 26;


	const COBWEB = 30;
	const TALL_GRASS = 31;
	const BUSH = 32;
	const DEAD_BUSH = 32;
	const WOOL = 35;
	const DANDELION = 37;
	const ROSE = 38;
	const CYAN_FLOWER = 38;
	const BROWN_MUSHROOM = 39;
	const RED_MUSHROOM = 40;
	const GOLD_BLOCK = 41;
	const IRON_BLOCK = 42;
	const DOUBLE_SLAB = 43;
	const DOUBLE_SLABS = 43;
	const SLAB = 44;
	const SLABS = 44;
	const BRICKS = 45;
	const BRICKS_BLOCK = 45;
	const TNT = 46;
	const BOOKSHELF = 47;
	const MOSS_STONE = 48;
	const MOSSY_STONE = 48;
	const OBSIDIAN = 49;
	const TORCH = 50;
	const FIRE = 51;

	const WOOD_STAIRS = 53;
	const WOODEN_STAIRS = 53;
	const OAK_WOOD_STAIRS = 53;
	const OAK_WOODEN_STAIRS = 53;
	const CHEST = 54;

	const DIAMOND_ORE = 56;
	const DIAMOND_BLOCK = 57;
	const CRAFTING_TABLE = 58;
	const WORKBENCH = 58;
	const WHEAT_BLOCK = 59;
	const FARMLAND = 60;
	const FURNACE = 61;
	const BURNING_FURNACE = 62;
	const LIT_FURNACE = 62;
	const SIGN_POST = 63;
	const DOOR_BLOCK = 64;
	const WOODEN_DOOR_BLOCK = 64;
	const WOOD_DOOR_BLOCK = 64;
	const LADDER = 65;

	const COBBLE_STAIRS = 67;
	const COBBLESTONE_STAIRS = 67;
	const WALL_SIGN = 68;

	const IRON_DOOR_BLOCK = 71;

	const REDSTONE_ORE = 73;
	const GLOWING_REDSTONE_ORE = 74;
	const LIT_REDSTONE_ORE = 74;

	const SNOW = 78;
	const SNOW_LAYER = 78;
	const ICE = 79;
	const SNOW_BLOCK = 80;
	const CACTUS = 81;
	const CLAY_BLOCK = 82;
	const REEDS = 83;
	const SUGARCANE_BLOCK = 83;

	const FENCE = 85;
	const PUMPKIN = 86;
	const NETHERRACK = 87;
	const SOUL_SAND = 88;
	const GLOWSTONE = 89;
	const GLOWSTONE_BLOCK = 89;


	const LIT_PUMPKIN = 91;
	const JACK_O_LANTERN = 91;
	const CAKE_BLOCK = 92;

	const TRAPDOOR = 96;

	const STONE_BRICKS = 98;
	const STONE_BRICK = 98;

	const IRON_BAR = 101;
	const IRON_BARS = 101;
	const GLASS_PANE = 102;
	const GLASS_PANEL = 102;
	const MELON_BLOCK = 103;
	const PUMPKIN_STEM = 104;
	const MELON_STEM = 105;

	const FENCE_GATE = 107;
	const BRICK_STAIRS = 108;
	const STONE_BRICK_STAIRS = 109;

	const NETHER_BRICKS = 112;
	const NETHER_BRICK_BLOCK = 112;

	const NETHER_BRICKS_STAIRS = 114;

	const SANDSTONE_STAIRS = 128;

	const SPRUCE_WOOD_STAIRS = 134;
	const SPRUCE_WOODEN_STAIRS = 134;
	const BIRCH_WOOD_STAIRS = 135;
	const BIRCH_WOODEN_STAIRS = 135;
	const JUNGLE_WOOD_STAIRS = 136;
	const JUNGLE_WOODEN_STAIRS = 136;

	const COBBLE_WALL = 139;
	const STONE_WALL = 139;
	const COBBLESTONE_WALL = 139;

	const CARROT_BLOCK = 141;
	const POTATO_BLOCK = 142;

	const QUARTZ_BLOCK = 155;
	const QUARTZ_STAIRS = 156;
	const DOUBLE_WOOD_SLAB = 157;
	const DOUBLE_WOODEN_SLAB = 157;
	const DOUBLE_WOOD_SLABS = 157;
	const DOUBLE_WOODEN_SLABS = 157;
	const WOOD_SLAB = 158;
	const WOODEN_SLAB = 158;
	const WOOD_SLABS = 158;
	const WOODEN_SLABS = 158;

	const HAY_BALE = 170;
	const CARPET = 171;

	const COAL_BLOCK = 173;

	const BEETROOT_BLOCK = 244;
	const STONECUTTER = 245;
	const GLOWING_OBSIDIAN = 246;
	const NETHER_REACTOR = 247;


	//Normal Item IDs

	const IRON_SHOVEL = 256; //Implemented
	const IRON_PICKAXE = 257; //Implemented
	const IRON_AXE = 258; //Implemented
	const FLINT_STEEL = 259;
	const FLINT_AND_STEEL = 259;
	const APPLE = 260; //Implemented
	const BOW = 261;
	const ARROW = 262;
	const COAL = 263; //Implemented
	const DIAMOND = 264; //Implemented
	const IRON_INGOT = 265; //Implemented
	const GOLD_INGOT = 266; //Implemented
	const IRON_SWORD = 267;
	const WOODEN_SWORD = 268; //Implemented
	const WOODEN_SHOVEL = 269; //Implemented
	const WOODEN_PICKAXE = 270; //Implemented
	const WOODEN_AXE = 271; //Implemented
	const STONE_SWORD = 272;
	const STONE_SHOVEL = 273;
	const STONE_PICKAXE = 274;
	const STONE_AXE = 275;
	const DIAMOND_SWORD = 276;
	const DIAMOND_SHOVEL = 277;
	const DIAMOND_PICKAXE = 278;
	const DIAMOND_AXE = 279;
	const STICK = 280; //Implemented
	const STICKS = 280;
	const BOWL = 281; //Implemented
	const MUSHROOM_STEW = 282;
	const GOLD_SWORD = 283;
	const GOLD_SHOVEL = 284;
	const GOLD_PICKAXE = 285;
	const GOLD_AXE = 286;
	const GOLDEN_SWORD = 283;
	const GOLDEN_SHOVEL = 284;
	const GOLDEN_PICKAXE = 285;
	const GOLDEN_AXE = 286;
	const STRING = 287;
	const FEATHER = 288; //Implemented
	const GUNPOWDER = 289;
	const WOODEN_HOE = 290;
	const STONE_HOE = 291;
	const IRON_HOE = 292; //Implemented
	const DIAMOND_HOE = 293;
	const GOLD_HOE = 294;
	const GOLDEN_HOE = 294;
	const SEEDS = 295;
	const WHEAT_SEEDS = 295;
	const WHEAT = 296;
	const BREAD = 297;
	const LEATHER_CAP = 298;
	const LEATHER_TUNIC = 299;
	const LEATHER_PANTS = 300;
	const LEATHER_BOOTS = 301;
	const CHAIN_HELMET = 302;
	const CHAIN_CHESTPLATE = 303;
	const CHAIN_LEGGINGS = 304;
	const CHAIN_BOOTS = 305;
	const IRON_HELMET = 306;
	const IRON_CHESTPLATE = 307;
	const IRON_LEGGINGS = 308;
	const IRON_BOOTS = 309;
	const DIAMOND_HELMET = 310;
	const DIAMOND_CHESTPLATE = 311;
	const DIAMOND_LEGGINGS = 312;
	const DIAMOND_BOOTS = 313;
	const GOLD_HELMET = 314;
	const GOLD_CHESTPLATE = 315;
	const GOLD_LEGGINGS = 316;
	const GOLD_BOOTS = 317;
	const FLINT = 318;
	const RAW_PORKCHOP = 319;
	const COOKED_PORKCHOP = 320;
	const PAINTING = 321;
	const GOLDEN_APPLE = 322;
	const SIGN = 323;
	const WOODEN_DOOR = 324;
	const BUCKET = 325;

	const MINECART = 329;

	const IRON_DOOR = 330;
	const REDSTONE = 331;
	const REDSTONE_DUST = 331;
	const SNOWBALL = 332;

	const LEATHER = 334;

	const BRICK = 336;
	const CLAY = 337;
	const SUGARCANE = 338;
	const SUGAR_CANE = 338;
	const SUGAR_CANES = 338;
	const PAPER = 339;
	const BOOK = 340;
	const SLIMEBALL = 341;

	const EGG = 344;
	const COMPASS = 345;

	const CLOCK = 347;
	const GLOWSTONE_DUST = 348;
	//const RAW_FISH = 349;
	//const COOKED_FISH = 350;
	const DYE = 351;
	const BONE = 352;
	const SUGAR = 353;
	const CAKE = 354;
	const BED = 355;


	//const COOKIE = 357;


	const SHEARS = 359;
	const MELON = 360;
	const MELON_SLICE = 360;
	const PUMPKIN_SEEDS = 361;
	const MELON_SEEDS = 362;
	const RAW_BEEF = 363;
	const STEAK = 364;
	const COOKED_BEEF = 364;

	const RAW_CHICKEN = 365;
	const COOKED_CHICKEN = 366;

	const SPAWN_EGG = 383;

	const CARROT = 391;
	const CARROTS = 391;
	const POTATO = 392;
	const POTATOES = 392; //@shoghicp Why the heck do we need plural redundant Item ID here????
	const BAKED_POTATO = 393;
	const BAKED_POTATOES = 393;

	const PUMPKIN_PIE = 400;

	const NETHER_BRICK = 405;
	const QUARTZ = 406;
	const NETHER_QUARTZ = 406;

	const CAMERA = 456;
	const BEETROOT = 457;
	const BEETROOT_SEEDS = 458;
	const BEETROOT_SEED = 458;
	const BEETROOT_SOUP = 459;


	public static $list = array();
	protected $block;
	protected $id;
	protected $meta;
	public $count;
	protected $maxStackSize = 64;
	protected $durability = 0;
	protected $name;
	public $isActivable = false;

	public static function init(){
		if(count(self::$list) === 0){
			self::$list = array(
				self::SUGARCANE => new Sugarcane(),
				self::WHEAT_SEEDS => new WheatSeeds(),
				self::PUMPKIN_SEEDS => new PumpkinSeeds(),
				self::MELON_SEEDS => new MelonSeeds(),
				self::MUSHROOM_STEW => new MushroomStew(),
				self::BEETROOT_SOUP => new BeetrootSoup(),
				self::CARROT => new Carrot(),
				self::POTATO => new Potato(),
				self::BEETROOT_SEEDS => new BeetrootSeeds(),
				self::SIGN => new Sign(),
				self::WOODEN_DOOR => new WoodenDoor(),
				self::BUCKET => new Bucket(),
				self::IRON_DOOR => new IronDoor(),
				self::CAKE => new Cake(),
				self::BED => new Bed(),
				self::PAINTING => new Painting(),
				self::COAL => new Coal(),
				self::APPLE => new Apple(),
				self::SPAWN_EGG => new SpawnEgg(),
				self::DIAMOND => new Diamond(),
				self::STICK => new Stick(),
				self::BOWL => new Bowl(),
				self::FEATHER => new Feather(),
				self::BRICK => new Brick(),
				self::IRON_INGOT => new IronIngot(),
				self::GOLD_INGOT => new GoldIngot(),
				self::IRON_SHOVEL => new IronShovel(),
				self::IRON_PICKAXE => new IronPickaxe(),
				self::IRON_AXE => new IronAxe(),
				self::IRON_HOE => new IronHoe(),
				self::WOODEN_SWORD => new WoodenSword(),
				self::WOODEN_SHOVEL => new WoodenShovel(),
				self::WOODEN_PICKAXE => new WoodenPickaxe(),
				self::WOODEN_AXE => new WoodenAxe(),
				self::FLINT_STEEL => new FlintSteel(),
			);
			foreach(Block::$list as $id => $class){
				self::$list[$id] = new ItemBlock($class);
			}

		}
	}

	public static function get($id, $meta = 0, $count = 1){
		if(isset(self::$list[$id])){
			$item = clone self::$list[$id];
			$item->setMetadata($meta);
			$item->setCount($count);
		} else{
			$item = new Item($id, $meta, $count);
		}

		return $item;
	}

	public static function fromString($str, $multiple = false){
		if($multiple === true){
			$blocks = array();
			foreach(explode(",", $str) as $b){
				$blocks[] = self::fromString($b, false);
			}

			return $blocks;
		} else{
			$b = explode(":", str_replace(" ", "_", trim($str)));
			if(!isset($b[1])){
				$meta = 0;
			} else{
				$meta = ((int) $b[1]) & 0xFFFF;
			}

			if(defined("PocketMine\\Item\\Item::".strtoupper($b[0]))){
				$item = self::get(constant("PocketMine\\Item\\Item::".strtoupper($b[0])), $meta);
				if($item->getID() === self::AIR and strtoupper($b[0]) !== "AIR"){
					$item = self::get(((int) $b[0]) & 0xFFFF, $meta);
				}
			} else{
				$item = self::get(((int) $b[0]) & 0xFFFF, $meta);
			}

			return $item;
		}
	}

	public function __construct($id, $meta = 0, $count = 1, $name = "Unknown"){
		$this->id = (int) $id;
		$this->meta = (int) $meta;
		$this->count = (int) $count;
		$this->name = $name;
		if(!isset($this->block) and $this->id <= 0xff and isset(Block::$list[$this->id])){
			$this->block = Block::get($this->id, $this->meta);
			$this->name = $this->block->getName();
		}
		if($this->isTool() !== false){
			$this->maxStackSize = 1;
		}
	}


	public function getCount(){
		return $this->count;
	}

	public function setCount($count){
		$this->count = (int) $count;
	}

	final public function getName(){
		return $this->name;
	}

	final public function isPlaceable(){
		return (($this->block instanceof Block) and $this->block->isPlaceable === true);
	}

	public function getBlock(){
		if($this->block instanceof Block){
			return $this->block;
		} else{
			return Block::get(self::AIR);
		}
	}

	final public function getID(){
		return $this->id;
	}

	final public function getMetadata(){
		return $this->meta;
	}

	public function setMetadata($meta){
		$this->meta = $meta & 0xFFFF;
	}

	final public function getMaxStackSize(){
		return $this->maxStackSize;
	}

	final public function getFuelTime(){
		if(!isset(Fuel::$duration[$this->id])){
			return false;
		}
		if($this->id !== self::BUCKET or $this->meta === 10){
			return Fuel::$duration[$this->id];
		}

		return false;
	}

	final public function getSmeltItem(){
		if(!isset(Smelt::$product[$this->id])){
			return false;
		}

		if(isset(Smelt::$product[$this->id][0]) and !is_array(Smelt::$product[$this->id][0])){
			return self::get(Smelt::$product[$this->id][0], Smelt::$product[$this->id][1]);
		}

		if(!isset(Smelt::$product[$this->id][$this->meta])){
			return false;
		}

		return self::get(Smelt::$product[$this->id][$this->meta][0], Smelt::$product[$this->id][$this->meta][1]);

	}

	public function useOn($object, $force = false){
		if($this->isTool() or $force === true){
			if(($object instanceof Entity) and !$this->isSword()){
				$this->meta += 2;
			} else{
				$this->meta++;
			}

			return true;
		} elseif($this->isHoe()){
			if(($object instanceof Block) and ($object->getID() === self::GRASS or $object->getID() === self::DIRT)){
				$this->meta++;
			}
		}

		return false;
	}

	final public function isTool(){
		return ($this->id === self::FLINT_STEEL or $this->id === self::SHEARS or $this->isPickaxe() !== false or $this->isAxe() !== false or $this->isShovel() !== false or $this->isSword() !== false);
	}

	final public function getMaxDurability(){
		if(!$this->isTool() and $this->isHoe() === false and $this->id !== self::BOW){
			return false;
		}

		$levels = array(
			2 => 33,
			1 => 60,
			3 => 132,
			4 => 251,
			5 => 1562,
			self::FLINT_STEEL => 65,
			self::SHEARS => 239,
			self::BOW => 385,
		);

		if(($type = $this->isPickaxe()) === false){
			if(($type = $this->isAxe()) === false){
				if(($type = $this->isSword()) === false){
					if(($type = $this->isShovel()) === false){
						if(($type = $this->isHoe()) === false){
							$type = $this->id;
						}
					}
				}
			}
		}

		return $levels[$type];
	}

	final public function isPickaxe(){ //Returns false or level of the pickaxe
		switch($this->id){
			case self::IRON_PICKAXE:
				return 4;
			case self::WOODEN_PICKAXE:
				return 1;
			case self::STONE_PICKAXE:
				return 3;
			case self::DIAMOND_PICKAXE:
				return 5;
			case self::GOLD_PICKAXE:
				return 2;
			default:
				return false;
		}
	}

	final public function isAxe(){
		switch($this->id){
			case self::IRON_AXE:
				return 4;
			case self::WOODEN_AXE:
				return 1;
			case self::STONE_AXE:
				return 3;
			case self::DIAMOND_AXE:
				return 5;
			case self::GOLD_AXE:
				return 2;
			default:
				return false;
		}
	}

	final public function isSword(){
		switch($this->id){
			case self::IRON_SWORD:
				return 4;
			case self::WOODEN_SWORD:
				return 1;
			case self::STONE_SWORD:
				return 3;
			case self::DIAMOND_SWORD:
				return 5;
			case self::GOLD_SWORD:
				return 2;
			default:
				return false;
		}
	}

	final public function isShovel(){
		switch($this->id){
			case self::IRON_SHOVEL:
				return 4;
			case self::WOODEN_SHOVEL:
				return 1;
			case self::STONE_SHOVEL:
				return 3;
			case self::DIAMOND_SHOVEL:
				return 5;
			case self::GOLD_SHOVEL:
				return 2;
			default:
				return false;
		}
	}

	public function isHoe(){
		switch($this->id){
			case self::IRON_HOE:
			case self::WOODEN_HOE:
			case self::STONE_HOE:
			case self::DIAMOND_HOE:
			case self::GOLD_HOE:
				return true;
			default:
				return false;
		}
	}

	public function isShears(){
		return ($this->id === self::SHEARS);
	}

	final public function __toString(){
		return "Item " . $this->name . " (" . $this->id . ":" . $this->meta . ")";
	}

	public function getDestroySpeed(Block $block, Player $player){
		return 1;
	}

	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		return false;
	}

	public final function equals(Item $item, $checkDamage = false){
		return $this->id === $item->getID() and ($checkDamage === false or $this->getMetadata() === $item->getMetadata());
	}

}
