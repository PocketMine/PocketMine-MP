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
 * All Block classes are in here
 */
namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\entity\Villager;
use pocketmine\entity\Zombie;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\Player;
use pocketmine\plugin\Plugin;


class Block extends Position implements Metadatable{
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
	const LOG = 17;
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
	const POPPY = 38;
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
	const MONSTER_SPAWNER = 52;
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
	const FENCE_OAK = 85;
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
	const VINE = 106;
	const VINES = 106;
	const FENCE_GATE = 107;
	const BRICK_STAIRS = 108;
	const STONE_BRICK_STAIRS = 109;
	const MYCELIUM = 110;

	const NETHER_BRICKS = 112;
	const NETHER_BRICK_BLOCK = 112;

	const NETHER_BRICKS_STAIRS = 114;

	const END_PORTAL_FRAME = 120;
	const END_STONE = 121;

	const SANDSTONE_STAIRS = 128;
	const EMERALD_ORE = 129;

	const EMERALD_BLOCK = 133;
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
	const STAINED_CLAY = 159;
	const STAINED_HARDENED_CLAY = 159;

	const LEAVES2 = 161;
	const LEAVE2 = 161;
	const WOOD2 = 162;
	const TRUNK2 = 162;
	const LOG2 = 162;
	const ACACIA_WOOD_STAIRS = 163;
	const ACACIA_WOODEN_STAIRS = 163;
	const DARK_OAK_WOOD_STAIRS = 164;
	const DARK_OAK_WOODEN_STAIRS = 164;

	const HAY_BALE = 170;
	const CARPET = 171;
	const HARDENED_CLAY = 172;
	const COAL_BLOCK = 173;

	const DOUBLE_PLANT = 175;

	const FENCE_GATE_SPRUCE = 183;
	const FENCE_GATE_BIRCH = 184;
	const FENCE_GATE_JUNGLE = 185;
	const FENCE_GATE_DARK_OAK = 186;
	const FENCE_GATE_ACACIA = 187;
	const FENCE_SPRUCE = 188;
	const FENCE_BIRCH = 189;
	const FENCE_JUNGLE = 190;
	const FENCE_DARK_OAK = 191;
	const FENCE_ACACIA = 192;

	const PODZOL = 243;
	const BEETROOT_BLOCK = 244;
	const STONECUTTER = 245;
	const GLOWING_OBSIDIAN = 246;
	const NETHER_REACTOR = 247;

	public static $creative = [
		//Building
		[Item::COBBLESTONE, 0],
		[Item::STONE_BRICKS, 0],
		[Item::STONE_BRICKS, 1],
		[Item::STONE_BRICKS, 2],
		[Item::STONE_BRICKS, 3],
		[Item::MOSS_STONE, 0],
		[Item::WOODEN_PLANKS, 0],
		[Item::WOODEN_PLANKS, 1],
		[Item::WOODEN_PLANKS, 2],
		[Item::WOODEN_PLANKS, 3],
		[Item::WOODEN_PLANKS, 4],
		[Item::WOODEN_PLANKS, 5],
		[Item::BRICKS, 0],

		[Item::STONE, 0],
		[Item::STONE, 1],
		[Item::STONE, 2],
		[Item::STONE, 3],
		[Item::STONE, 4],
		[Item::STONE, 5],
		[Item::STONE, 6],
		[Item::DIRT, 0],
		[Item::PODZOL, 0],
		[Item::GRASS, 0],
		[Item::MYCELIUM, 0],
		[Item::CLAY_BLOCK, 0],
		[Item::HARDENED_CLAY, 0],
		[Item::STAINED_CLAY, 0],
		[Item::STAINED_CLAY, 7],
		[Item::STAINED_CLAY, 6],
		[Item::STAINED_CLAY, 5],
		[Item::STAINED_CLAY, 4],
		[Item::STAINED_CLAY, 3],
		[Item::STAINED_CLAY, 2],
		[Item::STAINED_CLAY, 1],
		[Item::STAINED_CLAY, 15],
		[Item::STAINED_CLAY, 14],
		[Item::STAINED_CLAY, 13],
		[Item::STAINED_CLAY, 12],
		[Item::STAINED_CLAY, 11],
		[Item::STAINED_CLAY, 10],
		[Item::STAINED_CLAY, 9],
		[Item::STAINED_CLAY, 8],
		[Item::SANDSTONE, 0],
		[Item::SANDSTONE, 1],
		[Item::SANDSTONE, 2],
		[Item::SAND, 0],
		[Item::SAND, 1],
		[Item::GRAVEL, 0],
		[Item::TRUNK, 0],
		[Item::TRUNK, 1],
		[Item::TRUNK, 2],
		[Item::TRUNK, 3],
		[Item::TRUNK2, 0],
		[Item::TRUNK2, 1],
		[Item::NETHER_BRICKS, 0],
		[Item::NETHERRACK, 0],
		[Item::BEDROCK, 0],
		[Item::COBBLESTONE_STAIRS, 0],
		[Item::OAK_WOODEN_STAIRS, 0],
		[Item::SPRUCE_WOODEN_STAIRS, 0],
		[Item::BIRCH_WOODEN_STAIRS, 0],
		[Item::JUNGLE_WOODEN_STAIRS, 0],
		[Item::ACACIA_WOODEN_STAIRS, 0],
		[Item::DARK_OAK_WOODEN_STAIRS, 0],
		[Item::BRICK_STAIRS, 0],
		[Item::SANDSTONE_STAIRS, 0],
		[Item::STONE_BRICK_STAIRS, 0],
		[Item::NETHER_BRICKS_STAIRS, 0],
		[Item::QUARTZ_STAIRS, 0],
		[Item::SLAB, 0],
		[Item::SLAB, 1],
		[Item::WOODEN_SLAB, 0],
		[Item::WOODEN_SLAB, 1],
		[Item::WOODEN_SLAB, 2],
		[Item::WOODEN_SLAB, 3],
		[Item::WOODEN_SLAB, 4],
		[Item::WOODEN_SLAB, 5],
		[Item::SLAB, 3],
		[Item::SLAB, 4],
		[Item::SLAB, 5],
		[Item::SLAB, 6],
		[Item::QUARTZ_BLOCK, 0],
		[Item::QUARTZ_BLOCK, 1],
		[Item::QUARTZ_BLOCK, 2],
		[Item::COAL_ORE, 0],
		[Item::IRON_ORE, 0],
		[Item::GOLD_ORE, 0],
		[Item::DIAMOND_ORE, 0],
		[Item::LAPIS_ORE, 0],
		[Item::REDSTONE_ORE, 0],
		[Item::EMERALD_ORE, 0],
		[Item::OBSIDIAN, 0],
		[Item::ICE, 0],
		[Item::SNOW_BLOCK, 0],
		[Item::END_STONE, 0],

		//Decoration
		[Item::COBBLESTONE_WALL, 0],
		[Item::COBBLESTONE_WALL, 1],
		//TODO: Lilly Pad
		[Item::GOLD_BLOCK, 0],
		[Item::IRON_BLOCK, 0],
		[Item::DIAMOND_BLOCK, 0],
		[Item::LAPIS_BLOCK, 0],
		[Item::COAL_BLOCK, 0],
		[Item::EMERALD_BLOCK, 0],
		[Item::SNOW_LAYER, 0],
		[Item::GLASS, 0],
		[Item::GLOWSTONE_BLOCK, 0],
		[Item::VINES, 0],
		[Item::NETHER_REACTOR, 0],
		[Item::LADDER, 0],
		[Item::SPONGE, 0],
		[Item::GLASS_PANE, 0],
		[Item::WOODEN_DOOR, 0],
		[Item::TRAPDOOR, 0],
		[Item::FENCE, 0],
		[Item::FENCE_SPRUCE, 0],
		[Item::FENCE_BIRCH, 0],
		[Item::FENCE_DARK_OAK, 0],
		[Item::FENCE_JUNGLE, 0],
		[Item::FENCE_GATE, 0],
		[Item::FENCE_GATE_BIRCH, 0],
		[Item::FENCE_GATE_SPRUCE, 0],
		[Item::FENCE_GATE_DARK_OAK, 0],
		[Item::FENCE_GATE_JUNGLE, 0],
		[Item::FENCE_GATE_ACACIA, 0],
		[Item::IRON_BARS, 0],
		[Item::BED, 0],
		[Item::BOOKSHELF, 0],
		[Item::PAINTING, 0],
		[Item::WORKBENCH, 0],
		[Item::STONECUTTER, 0],
		[Item::CHEST, 0],
		[Item::FURNACE, 0],
		[Item::END_PORTAL, 0],
		[Item::DANDELION, 0],
		[Item::POPPY, 0],
		//TODO: blue orchid
		//TODO: Allium
		//TODO: Azure Bluet
		//TODO: Red Tulip
		//TODO: Orange Tulip
		//TODO: White Tulip
		//TODO: Pink Tulip
		//TODO: Oxeye Daisy
		//TODO: Lilac
		//TODO: Double Tallgrass
		//TODO: Large Fern
		//TODO: Rose Bush
		//TODO: Peony
		[Item::BROWN_MUSHROOM, 0],
		[Item::RED_MUSHROOM, 0],
		//TODO: Mushroom block (brown, cover)
		//TODO: Mushroom block (red, cover)
		//TODO: Mushroom block (brown, stem)
		//TODO: Mushroom block (red, stem)
		[Item::CACTUS, 0],
		[Item::MELON_BLOCK, 0],
		[Item::PUMPKIN, 0],
		[Item::LIT_PUMPKIN, 0],
		[Item::COBWEB, 0],
		[Item::HAY_BALE, 0],
		[Item::TALL_GRASS, 1],
		[Item::TALL_GRASS, 2],
		[Item::DEAD_BUSH, 0],
		[Item::SAPLING, 0],
		[Item::SAPLING, 1],
		[Item::SAPLING, 2],
		[Item::SAPLING, 3],
		[Item::SAPLING, 4],
		[Item::SAPLING, 5],
		[Item::LEAVES, 0],
		[Item::LEAVES, 1],
		[Item::LEAVES, 2],
		[Item::LEAVES, 3],
		[Item::LEAVES, 3],
		[Item::LEAVES2, 0],
		[Item::LEAVES2, 1],
		[Item::CAKE, 0],
		[Item::SIGN, 0],
		[Item::MONSTER_SPAWNER, 0],
		[Item::WOOL, 0],
		[Item::WOOL, 7],
		[Item::WOOL, 6],
		[Item::WOOL, 5],
		[Item::WOOL, 4],
		[Item::WOOL, 3],
		[Item::WOOL, 2],
		[Item::WOOL, 1],
		[Item::WOOL, 15],
		[Item::WOOL, 14],
		[Item::WOOL, 13],
		[Item::WOOL, 12],
		[Item::WOOL, 11],
		[Item::WOOL, 10],
		[Item::WOOL, 9],
		[Item::WOOL, 8],
		[Item::CARPET, 0],
		[Item::CARPET, 7],
		[Item::CARPET, 6],
		[Item::CARPET, 5],
		[Item::CARPET, 4],
		[Item::CARPET, 3],
		[Item::CARPET, 2],
		[Item::CARPET, 1],
		[Item::CARPET, 15],
		[Item::CARPET, 14],
		[Item::CARPET, 13],
		[Item::CARPET, 12],
		[Item::CARPET, 11],
		[Item::CARPET, 10],
		[Item::CARPET, 9],
		[Item::CARPET, 8],

		//Tools
		//TODO [Item::RAILS, 0],
		//TODO [Item::POWERED_RAILS, 0],
		[Item::TORCH, 0],
		[Item::BUCKET, 0],
		[Item::BUCKET, 8],
		[Item::BUCKET, 10],
		[Item::TNT, 0],
		[Item::IRON_HOE, 0],
		[Item::IRON_SWORD, 0],
		[Item::BOW, 0],
		[Item::SHEARS, 0],
		[Item::FLINT_AND_STEEL, 0],
		[Item::CLOCK, 0],
		[Item::COMPASS, 0],
		[Item::MINECART, 0],
		[Item::SPAWN_EGG, Villager::NETWORK_ID],
		//[Item::SPAWN_EGG, 10], //Chicken
		//[Item::SPAWN_EGG, 11], //Cow
		//[Item::SPAWN_EGG, 12], //Pig
		//[Item::SPAWN_EGG, 13], //Sheep
		//TODO: Wolf
		//TODO: Mooshroom
		//TODO: Creeper
		//TODO: Enderman
		//TODO: Silverfish
		//TODO: Skeleton
		//TODO: Slime
		[Item::SPAWN_EGG, Zombie::NETWORK_ID],
		//TODO: PigZombie
		//TODO: Replace with Entity constants


		//Seeds
		[Item::SUGARCANE, 0],
		[Item::WHEAT, 0],
		[Item::SEEDS, 0],
		[Item::MELON_SEEDS, 0],
		[Item::PUMPKIN_SEEDS, 0],
		[Item::CARROT, 0],
		[Item::POTATO, 0],
		[Item::BEETROOT_SEEDS, 0],
		[Item::EGG, 0],
		[Item::DYE, 0],
		[Item::DYE, 7],
		[Item::DYE, 6],
		[Item::DYE, 5],
		[Item::DYE, 4],
		[Item::DYE, 3],
		[Item::DYE, 2],
		[Item::DYE, 1],
		[Item::DYE, 15],
		[Item::DYE, 14],
		[Item::DYE, 13],
		[Item::DYE, 12],
		[Item::DYE, 11],
		[Item::DYE, 10],
		[Item::DYE, 9],
		[Item::DYE, 8],

	];

	/** @var \SplFixedArray */
	public static $list = null;
	/** @var \SplFixedArray */
	public static $fullList = null;

	/** @var \SplFixedArray */
	public static $light = null;
	/** @var \SplFixedArray */
	public static $lightFilter = null;
	/** @var \SplFixedArray */
	public static $solid = null;
	/** @var \SplFixedArray */
	public static $hardness = null;
	/** @var \SplFixedArray */
	public static $transparent = null;

	protected $id;
	protected $meta = 0;

	/** @var AxisAlignedBB */
	protected $boundingBox = null;

	/**
	 * Backwards-compatibility with old way to define block properties
	 *
	 * @deprecated
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get($key){
		static $map = [
			"hardness" => "getHardness",
			"lightLevel" => "getLightLevel",
			"frictionFactor" => "getFrictionFactor",
			"name" => "getName",
			"isPlaceable" => "canBePlaced",
			"isReplaceable" => "canBeReplaced",
			"isTransparent" => "isTransparent",
			"isSolid" => "isSolid",
			"isFlowable" => "canBeFlowedInto",
			"isActivable" => "canBeActivated",
			"hasEntityCollision" => "hasEntityCollision"
		];
		return isset($map[$key]) ? $this->{$map[$key]}() : null;
	}

	public static function init(){
		if(self::$list === null){
			self::$list = new \SplFixedArray(256);
			self::$fullList = new \SplFixedArray(4096);
			self::$light = new \SplFixedArray(256);
			self::$lightFilter = new \SplFixedArray(256);
			self::$solid = new \SplFixedArray(256);
			self::$hardness = new \SplFixedArray(256);
			self::$transparent = new \SplFixedArray(256);
			self::$list[self::AIR] = Air::class;
			self::$list[self::STONE] = Stone::class;
			self::$list[self::GRASS] = Grass::class;
			self::$list[self::DIRT] = Dirt::class;
			self::$list[self::COBBLESTONE] = Cobblestone::class;
			self::$list[self::PLANKS] = Planks::class;
			self::$list[self::SAPLING] = Sapling::class;
			self::$list[self::BEDROCK] = Bedrock::class;
			self::$list[self::WATER] = Water::class;
			self::$list[self::STILL_WATER] = StillWater::class;
			self::$list[self::LAVA] = Lava::class;
			self::$list[self::STILL_LAVA] = StillLava::class;
			self::$list[self::SAND] = Sand::class;
			self::$list[self::GRAVEL] = Gravel::class;
			self::$list[self::GOLD_ORE] = GoldOre::class;
			self::$list[self::IRON_ORE] = IronOre::class;
			self::$list[self::COAL_ORE] = CoalOre::class;
			self::$list[self::WOOD] = Wood::class;
			self::$list[self::LEAVES] = Leaves::class;
			self::$list[self::SPONGE] = Sponge::class;
			self::$list[self::GLASS] = Glass::class;
			self::$list[self::LAPIS_ORE] = LapisOre::class;
			self::$list[self::LAPIS_BLOCK] = Lapis::class;
			self::$list[self::SANDSTONE] = Sandstone::class;
			self::$list[self::BED_BLOCK] = Bed::class;
			self::$list[self::COBWEB] = Cobweb::class;
			self::$list[self::TALL_GRASS] = TallGrass::class;
			self::$list[self::DEAD_BUSH] = DeadBush::class;
			self::$list[self::WOOL] = Wool::class;
			self::$list[self::DANDELION] = Dandelion::class;
			self::$list[self::POPPY] = Poppy::class;
			self::$list[self::BROWN_MUSHROOM] = BrownMushroom::class;
			self::$list[self::RED_MUSHROOM] = RedMushroom::class;
			self::$list[self::GOLD_BLOCK] = Gold::class;
			self::$list[self::IRON_BLOCK] = Iron::class;
			self::$list[self::DOUBLE_SLAB] = DoubleSlab::class;
			self::$list[self::SLAB] = Slab::class;
			self::$list[self::BRICKS_BLOCK] = Bricks::class;
			self::$list[self::TNT] = TNT::class;
			self::$list[self::BOOKSHELF] = Bookshelf::class;
			self::$list[self::MOSS_STONE] = MossStone::class;
			self::$list[self::OBSIDIAN] = Obsidian::class;
			self::$list[self::TORCH] = Torch::class;
			self::$list[self::FIRE] = Fire::class;
			self::$list[self::MONSTER_SPAWNER] = MonsterSpawner::class;
			self::$list[self::WOOD_STAIRS] = WoodStairs::class;
			self::$list[self::CHEST] = Chest::class;

			self::$list[self::DIAMOND_ORE] = DiamondOre::class;
			self::$list[self::DIAMOND_BLOCK] = Diamond::class;
			self::$list[self::WORKBENCH] = Workbench::class;
			self::$list[self::WHEAT_BLOCK] = Wheat::class;
			self::$list[self::FARMLAND] = Farmland::class;
			self::$list[self::FURNACE] = Furnace::class;
			self::$list[self::BURNING_FURNACE] = BurningFurnace::class;
			self::$list[self::SIGN_POST] = SignPost::class;
			self::$list[self::WOOD_DOOR_BLOCK] = WoodDoor::class;
			self::$list[self::LADDER] = Ladder::class;

			self::$list[self::COBBLESTONE_STAIRS] = CobblestoneStairs::class;
			self::$list[self::WALL_SIGN] = WallSign::class;

			self::$list[self::IRON_DOOR_BLOCK] = IronDoor::class;
			self::$list[self::REDSTONE_ORE] = RedstoneOre::class;
			self::$list[self::GLOWING_REDSTONE_ORE] = GlowingRedstoneOre::class;

			self::$list[self::SNOW_LAYER] = SnowLayer::class;
			self::$list[self::ICE] = Ice::class;
			self::$list[self::SNOW_BLOCK] = Snow::class;
			self::$list[self::CACTUS] = Cactus::class;
			self::$list[self::CLAY_BLOCK] = Clay::class;
			self::$list[self::SUGARCANE_BLOCK] = Sugarcane::class;

			self::$list[self::FENCE] = Fence::class;
			self::$list[self::PUMPKIN] = Pumpkin::class;
			self::$list[self::NETHERRACK] = Netherrack::class;
			self::$list[self::SOUL_SAND] = SoulSand::class;
			self::$list[self::GLOWSTONE_BLOCK] = Glowstone::class;

			self::$list[self::LIT_PUMPKIN] = LitPumpkin::class;
			self::$list[self::CAKE_BLOCK] = Cake::class;

			self::$list[self::TRAPDOOR] = Trapdoor::class;

			self::$list[self::STONE_BRICKS] = StoneBricks::class;

			self::$list[self::IRON_BARS] = IronBars::class;
			self::$list[self::GLASS_PANE] = GlassPane::class;
			self::$list[self::MELON_BLOCK] = Melon::class;
			self::$list[self::PUMPKIN_STEM] = PumpkinStem::class;
			self::$list[self::MELON_STEM] = MelonStem::class;
			self::$list[self::VINE] = Vine::class;
			self::$list[self::FENCE_GATE] = FenceGate::class;
			self::$list[self::BRICK_STAIRS] = BrickStairs::class;
			self::$list[self::STONE_BRICK_STAIRS] = StoneBrickStairs::class;

			self::$list[self::MYCELIUM] = Mycelium::class;
			self::$list[self::NETHER_BRICKS] = NetherBrick::class;

			self::$list[self::NETHER_BRICKS_STAIRS] = NetherBrickStairs::class;

			self::$list[self::END_PORTAL_FRAME] = EndPortalFrame::class;
			self::$list[self::END_STONE] = EndStone::class;
			self::$list[self::SANDSTONE_STAIRS] = SandstoneStairs::class;
			self::$list[self::EMERALD_ORE] = EmeraldOre::class;

			self::$list[self::EMERALD_BLOCK] = Emerald::class;
			self::$list[self::SPRUCE_WOOD_STAIRS] = SpruceWoodStairs::class;
			self::$list[self::BIRCH_WOOD_STAIRS] = BirchWoodStairs::class;
			self::$list[self::JUNGLE_WOOD_STAIRS] = JungleWoodStairs::class;
			self::$list[self::STONE_WALL] = StoneWall::class;

			self::$list[self::CARROT_BLOCK] = Carrot::class;
			self::$list[self::POTATO_BLOCK] = Potato::class;

			self::$list[self::QUARTZ_BLOCK] = Quartz::class;
			self::$list[self::QUARTZ_STAIRS] = QuartzStairs::class;
			self::$list[self::DOUBLE_WOOD_SLAB] = DoubleWoodSlab::class;
			self::$list[self::WOOD_SLAB] = WoodSlab::class;
			self::$list[self::STAINED_CLAY] = StainedClay::class;

			self::$list[self::LEAVES2] = Leaves2::class;
			self::$list[self::WOOD2] = Wood2::class;
			self::$list[self::ACACIA_WOOD_STAIRS] = AcaciaWoodStairs::class;
			self::$list[self::DARK_OAK_WOOD_STAIRS] = DarkOakWoodStairs::class;

			self::$list[self::HAY_BALE] = HayBale::class;
			self::$list[self::CARPET] = Carpet::class;
			self::$list[self::HARDENED_CLAY] = HardenedClay::class;
			self::$list[self::COAL_BLOCK] = Coal::class;

			self::$list[self::DOUBLE_PLANT] = DoublePlant::class;

			self::$list[self::FENCE_GATE_SPRUCE] = FenceGateSpruce::class;
			self::$list[self::FENCE_GATE_BIRCH] = FenceGateBirch::class;
			self::$list[self::FENCE_GATE_JUNGLE] = FenceGateJungle::class;
			self::$list[self::FENCE_GATE_DARK_OAK] = FenceGateDarkOak::class;
			self::$list[self::FENCE_GATE_ACACIA] = FenceGateAcacia::class;
			self::$list[self::FENCE_SPRUCE] = FenceSpruce::class;
			self::$list[self::FENCE_BIRCH] = FenceBirch::class;
			self::$list[self::FENCE_DARK_OAK] = FenceDarkOak::class;
			self::$list[self::FENCE_JUNGLE] = FenceJungle::class;
			self::$list[self::FENCE_ACACIA] = FenceAcacia::class;

			self::$list[self::PODZOL] = Podzol::class;
			self::$list[self::BEETROOT_BLOCK] = Beetroot::class;
			self::$list[self::STONECUTTER] = Stonecutter::class;
			self::$list[self::GLOWING_OBSIDIAN] = GlowingObsidian::class;
			self::$list[self::NETHER_REACTOR] = NetherReactor::class;

			foreach(self::$list as $id => $class){
				if($class !== null){
					/** @var Block $block */
					$block = new $class();

					for($data = 0; $data < 16; ++$data){
						self::$fullList[($id << 4) | $data] = new $class($data);
					}

					self::$solid[$id] = $block->isSolid();
					self::$transparent[$id] = $block->isTransparent();
					self::$hardness[$id] = $block->getHardness();
					self::$light[$id] = $block->getLightLevel();

					if($block->isSolid()){
						if($block->isTransparent()){
							if($block instanceof Liquid or $block instanceof Ice){
								self::$lightFilter[$id] = 2;
							}else{
								self::$lightFilter[$id] = 1;
							}
						}else{
							self::$lightFilter[$id] = 15;
						}
					}else{
						self::$lightFilter[$id] = 1;
					}
				}else{
					self::$lightFilter[$id] = 1;
					for($data = 0; $data < 16; ++$data){
						self::$fullList[($id << 4) | $data] = new Block($id, $data);
					}
				}
			}
		}
	}

	/**
	 * @param int      $id
	 * @param int      $meta
	 * @param Position $pos
	 *
	 * @return Block
	 */
	public static function get($id, $meta = 0, Position $pos = null){
		try{
			$block = self::$list[$id];
			if($block !== null){
				$block = new $block($meta);
			}else{
				$block = new Block($id, $meta);
			}
		}catch(\RuntimeException $e){
			$block = new Block($id, $meta);
		}

		if($pos !== null){
			$block->x = $pos->x;
			$block->y = $pos->y;
			$block->z = $pos->z;
			$block->level = $pos->level;
		}

		return $block;
	}

	/**
	 * @param int $id
	 * @param int $meta
	 */
	public function __construct($id, $meta = 0){
		$this->id = (int) $id;
		$this->meta = (int) $meta;
	}

	/**
	 * Places the Block, using block space and block target, and side. Returns if the block has been placed.
	 *
	 * @param Item   $item
	 * @param Block  $block
	 * @param Block  $target
	 * @param int    $face
	 * @param float  $fx
	 * @param float  $fy
	 * @param float  $fz
	 * @param Player $player = null
	 *
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		return $this->getLevel()->setBlock($this, $this, true, true);
	}

	/**
	 * Returns if the item can be broken with an specific Item
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function isBreakable(Item $item){
		return true;
	}

	/**
	 * Do the actions needed so the block is broken with the Item
	 *
	 * @param Item $item
	 *
	 * @return mixed
	 */
	public function onBreak(Item $item){
		return $this->getLevel()->setBlock($this, new Air(), true, true);
	}

	/**
	 * Fires a block update on the Block
	 *
	 * @param int $type
	 *
	 * @return void
	 */
	public function onUpdate($type){

	}

	/**
	 * Do actions when activated by Item. Returns if it has done anything
	 *
	 * @param Item   $item
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		return false;
	}

	/**
	 * @return int
	 */
	public function getHardness(){
		return 10;
	}

	/**
	 * @return float
	 */
	public function getFrictionFactor(){
		return 0.6;
	}

	/**
	 * @return int 0-15
	 */
	public function getLightLevel(){
		return 0;
	}

	/**
	 * AKA: Block->isPlaceable
	 *
	 * @return bool
	 */
	public function canBePlaced(){
		return true;
	}

	/**
	 * AKA: Block->canBeReplaced()
	 *
	 * @return bool
	 */
	public function canBeReplaced(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function isTransparent(){
		return false;
	}

	public function isSolid(){
		return true;
	}

	/**
	 * AKA: Block->isFlowable
	 *
	 * @return bool
	 */
	public function canBeFlowedInto(){
		return false;
	}

	/**
	 * AKA: Block->isActivable
	 *
	 * @return bool
	 */
	public function canBeActivated(){
		return false;
	}

	public function hasEntityCollision(){
		return false;
	}

	/**
	 * @return string
	 */
	public function getName(){
		return "Unknown";
	}

	/**
	 * @return int
	 */
	final public function getId(){
		return $this->id;
	}
	
	final public function getMeta(){
		return $this->meta;
	}

	public function addVelocityToEntity(Entity $entity, Vector3 $vector){

	}

	/**
	 * @return int
	 */
	final public function getDamage(){
		return $this->meta;
	}

	/**
	 * @param int $meta
	 */
	final public function setDamage($meta){
		$this->meta = $meta & 0x0f;
	}

	/**
	 * Sets the block position to a new Position object
	 *
	 * @param Position $v
	 */
	final public function position(Position $v){
		$this->x = (int) $v->x;
		$this->y = (int) $v->y;
		$this->z = (int) $v->z;
		$this->level = $v->level;
		$this->boundingBox = null;
	}

	/**
	 * Returns an array of Item objects to be dropped
	 *
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item){
		if(!isset(self::$list[$this->id])){ //Unknown blocks
			return [];
		}else{
			return [
				[$this->id, $this->meta, 1],
			];
		}
	}

	/**
	 * Returns the seconds that this block takes to be broken using an specific Item
	 *
	 * @param Item $item
	 *
	 * @return float
	 */
	public function getBreakTime(Item $item){
		return 0.20;
	}

	/**
	 * Returns the Block on the side $side, works like Vector3::side()
	 *
	 * @param int $side
	 * @param int $step
	 *
	 * @return Block
	 */
	public function getSide($side, $step = 1){
		if($this->isValid()){
			return $this->getLevel()->getBlock(Vector3::getSide($side, $step));
		}

		return Block::get(Item::AIR, 0, Position::fromObject(Vector3::getSide($side, $step)));
	}

	/**
	 * @return string
	 */
	public function __toString(){
		return "Block[" . $this->getName() . "] (" . $this->id . ":" . $this->meta . ")";
	}

	/**
	 * Checks for collision against an AxisAlignedBB
	 *
	 * @param AxisAlignedBB $bb
	 * @param Block[]       $list
	 */
	public function collidesWithBB(AxisAlignedBB $bb, &$list = []){
		$bb2 = $this->getBoundingBox();
		if($bb2 !== null and $bb2->intersectsWith($bb)){
			$list[] = $bb2;
		}
	}

	/**
	 * @param Entity $entity
	 */
	public function onEntityCollide(Entity $entity){

	}

	/**
	 * @return AxisAlignedBB
	 */
	public function getBoundingBox(){
		if($this->boundingBox !== null){
			return $this->boundingBox;
		}else{
			return ($this->boundingBox = $this->recalculateBoundingBox());
		}
	}

	/**
	 * @return AxisAlignedBB
	 */
	protected function recalculateBoundingBox(){
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 1,
			$this->z + 1
		);
	}

	public function calculateIntercept(Vector3 $pos1, Vector3 $pos2){
		$bb = $this->getBoundingBox();
		if($bb === null){
			return null;
		}

		$v1 = $pos1->getIntermediateWithXValue($pos2, $bb->minX);
		$v2 = $pos1->getIntermediateWithXValue($pos2, $bb->maxX);
		$v3 = $pos1->getIntermediateWithYValue($pos2, $bb->minY);
		$v4 = $pos1->getIntermediateWithYValue($pos2, $bb->maxY);
		$v5 = $pos1->getIntermediateWithZValue($pos2, $bb->minZ);
		$v6 = $pos1->getIntermediateWithZValue($pos2, $bb->maxZ);

		if($v1 !== null and !$bb->isVectorInYZ($v1)){
			$v1 = null;
		}

		if($v2 !== null and !$bb->isVectorInYZ($v2)){
			$v2 = null;
		}

		if($v3 !== null and !$bb->isVectorInXZ($v3)){
			$v3 = null;
		}

		if($v4 !== null and !$bb->isVectorInXZ($v4)){
			$v4 = null;
		}

		if($v5 !== null and !$bb->isVectorInXY($v5)){
			$v5 = null;
		}

		if($v6 !== null and !$bb->isVectorInXY($v6)){
			$v6 = null;
		}

		$vector = $v1;

		if($v2 !== null and ($vector === null or $pos1->distanceSquared($v2) < $pos1->distanceSquared($vector))){
			$vector = $v2;
		}

		if($v3 !== null and ($vector === null or $pos1->distanceSquared($v3) < $pos1->distanceSquared($vector))){
			$vector = $v3;
		}

		if($v4 !== null and ($vector === null or $pos1->distanceSquared($v4) < $pos1->distanceSquared($vector))){
			$vector = $v4;
		}

		if($v5 !== null and ($vector === null or $pos1->distanceSquared($v5) < $pos1->distanceSquared($vector))){
			$vector = $v5;
		}

		if($v6 !== null and ($vector === null or $pos1->distanceSquared($v6) < $pos1->distanceSquared($vector))){
			$vector = $v6;
		}

		if($vector === null){
			return null;
		}

		$f = -1;

		if($vector === $v1){
			$f = 4;
		}elseif($vector === $v2){
			$f = 5;
		}elseif($vector === $v3){
			$f = 0;
		}elseif($vector === $v4){
			$f = 1;
		}elseif($vector === $v5){
			$f = 2;
		}elseif($vector === $v6){
			$f = 3;
		}

		return MovingObjectPosition::fromBlock($this->x, $this->y, $this->z, $f, $vector->add($this->x, $this->y, $this->z));
	}

	public function setMetadata($metadataKey, MetadataValue $metadataValue){
		if($this->getLevel() instanceof Level){
			$this->getLevel()->getBlockMetadata()->setMetadata($this, $metadataKey, $metadataValue);
		}
	}

	public function getMetadata($metadataKey){
		if($this->getLevel() instanceof Level){
			$this->getLevel()->getBlockMetadata()->getMetadata($this, $metadataKey);
		}
	}

	public function hasMetadata($metadataKey){
		if($this->getLevel() instanceof Level){
			$this->getLevel()->getBlockMetadata()->hasMetadata($this, $metadataKey);
		}
	}

	public function removeMetadata($metadataKey, Plugin $plugin){
		if($this->getLevel() instanceof Level){
			$this->getLevel()->getBlockMetadata()->removeMetadata($this, $metadataKey, $plugin);
		}
	}
}
