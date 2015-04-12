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
 * All Level related classes are here, like Generators, Populators, Noise, ...
 */
namespace pocketmine\level;

use pocketmine\block\Air;
use pocketmine\block\Beetroot;
use pocketmine\block\Block;
use pocketmine\block\BrownMushroom;
use pocketmine\block\Cactus;
use pocketmine\block\Carrot;
use pocketmine\block\Farmland;
use pocketmine\block\Grass;
use pocketmine\block\Ice;
use pocketmine\block\Leaves;
use pocketmine\block\Leaves2;
use pocketmine\block\MelonStem;
use pocketmine\block\Mycelium;
use pocketmine\block\Potato;
use pocketmine\block\PumpkinStem;
use pocketmine\block\RedMushroom;
use pocketmine\block\Sapling;
use pocketmine\block\SnowLayer;
use pocketmine\block\Sugarcane;
use pocketmine\block\Wheat;
use pocketmine\entity\Arrow;
use pocketmine\entity\Entity;
use pocketmine\entity\Item as DroppedItem;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\level\ChunkUnloadEvent;
use pocketmine\event\level\LevelSaveEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\level\SpawnChangeEvent;
use pocketmine\event\LevelTimings;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Timings;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\FullChunk;
use pocketmine\level\format\generic\BaseLevelProvider;
use pocketmine\level\format\generic\EmptyChunkSection;
use pocketmine\level\format\LevelProvider;
use pocketmine\level\generator\GenerationTask;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\PopulationTask;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\metadata\BlockMetadataStore;
use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\Short;
use pocketmine\nbt\tag\String;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\utils\Cache;
use pocketmine\utils\LevelException;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Random;
use pocketmine\utils\ReversePriorityQueue;
use pocketmine\utils\TextFormat;
use pocketmine\level\particle\Particle;
use pocketmine\level\sound\Sound;
use pocketmine\entity\Effect;
use pocketmine\level\particle\DestroyBlockParticle;

#include <rules/Level.h>

class Level implements ChunkManager, Metadatable{

	private static $levelIdCounter = 1;
	public static $COMPRESSION_LEVEL = 8;


	const BLOCK_UPDATE_NORMAL = 1;
	const BLOCK_UPDATE_RANDOM = 2;
	const BLOCK_UPDATE_SCHEDULED = 3;
	const BLOCK_UPDATE_WEAK = 4;
	const BLOCK_UPDATE_TOUCH = 5;

	const TIME_DAY = 0;
	const TIME_SUNSET = 12000;
	const TIME_NIGHT = 14000;
	const TIME_SUNRISE = 23000;

	const TIME_FULL = 24000;

	/** @var Tile[] */
	private $tiles = [];

	/** @var Player[] */
	private $players = [];

	/** @var Entity[] */
	private $entities = [];

	/** @var Entity[] */
	public $updateEntities = [];
	/** @var Tile[] */
	public $updateTiles = [];

	private $blockCache = [];

	/** @var Server */
	private $server;

	/** @var int */
	private $levelId;

	/** @var LevelProvider */
	private $provider;

	/** @var Player[][] */
	private $usedChunks = [];

	/** @var FullChunk[]|Chunk[] */
	private $unloadQueue;

	private $time;
	public $stopTime;

	private $folderName;

	/** @var FullChunk[]|Chunk[] */
	private $chunks = [];

	/** @var Vector3[][] */
	private $changedBlocks = [];

	/** @var ReversePriorityQueue */
	private $updateQueue;
	private $updateQueueIndex = [];

	/** @var Player[][] */
	private $chunkSendQueue = [];
	private $chunkSendTasks = [];

	private $chunkPopulationQueue = [];
	private $chunkPopulationLock = [];
	private $chunkGenerationQueue = [];
	private $chunkGenerationQueueSize = 8;
	private $chunkPopulationQueueSize = 2;

	private $autoSave = true;

	/** @var BlockMetadataStore */
	private $blockMetadata;

	private $useSections;
	private $blockOrder;

	/** @var Position */
	private $temporalPosition;
	/** @var Vector3 */
	private $temporalVector;

	/** @var \SplFixedArray */
	private $blockStates;

	private $chunkTickRadius;
	private $chunkTickList = [];
	private $chunksPerTick;
	private $clearChunksOnTick;
	private $randomTickBlocks = [
		Block::GRASS => Grass::class,
		Block::SAPLING => Sapling::class,
		Block::LEAVES => Leaves::class,
		Block::WHEAT_BLOCK => Wheat::class,
		Block::FARMLAND => Farmland::class,
		Block::SNOW_LAYER => SnowLayer::class,
		Block::ICE => Ice::class,
		Block::CACTUS => Cactus::class,
		Block::SUGARCANE_BLOCK => Sugarcane::class,
		Block::RED_MUSHROOM => RedMushroom::class,
		Block::BROWN_MUSHROOM => BrownMushroom::class,
		Block::PUMPKIN_STEM => PumpkinStem::class,
		Block::MELON_STEM => MelonStem::class,
		//Block::VINE => true,
		Block::MYCELIUM => Mycelium::class,
		//Block::COCOA_BLOCK => true,
		Block::CARROT_BLOCK => Carrot::class,
		Block::POTATO_BLOCK => Potato::class,
		Block::LEAVES2 => Leaves2::class,

		Block::BEETROOT_BLOCK => Beetroot::class,
	];

	/** @var LevelTimings */
	public $timings;

	private $tickRate;
	public $tickRateCounter = 0;

	/** @var Generator */
	private $generator;
	/** @var Generator */
	private $generatorInstance;

	/**
	 * Returns the chunk unique hash/key
	 *
	 * @param int $x
	 * @param int $z
	 *
	 * @return string
	 */
	public static function chunkHash($x, $z){
		return PHP_INT_SIZE === 8 ? (($x & 0xFFFFFFFF) << 32) | ($z & 0xFFFFFFFF) : $x . ":" . $z;
	}

	public static function blockHash($x, $y, $z){
		return PHP_INT_SIZE === 8 ? (($x & 0xFFFFFFF) << 35) | (($y & 0x7f) << 28) | ($z & 0xFFFFFFF) : $x . ":" . $y .":". $z;
	}

	public static function getBlockXYZ($hash, &$x, &$y, &$z){
		if(PHP_INT_SIZE === 8){
			$x = ($hash >> 35) << 36 >> 36;
			$y = (($hash >> 28) & 0x7f);// << 57 >> 57; //it's always positive
			$z = ($hash & 0xFFFFFFF) << 36 >> 36;
		}else{
			$hash = explode(":", $hash);
			$x = (int) $hash[0];
			$y = (int) $hash[1];
			$z = (int) $hash[2];
		}
	}

	public static function getXZ($hash, &$x, &$z){
		if(PHP_INT_SIZE === 8){
			$x = ($hash >> 32) << 32 >> 32;
			$z = ($hash & 0xFFFFFFFF) << 32 >> 32;
		}else{
			$hash = explode(":", $hash);
			$x = (int) $hash[0];
			$z = (int) $hash[1];
		}
	}

	/**
	 * Init the default level data
	 *
	 * @param Server $server
	 * @param string $name
	 * @param string $path
	 * @param string $provider Class that extends LevelProvider
	 *
	 * @throws \Exception
	 */
	public function __construct(Server $server, $name, $path, $provider){
		$this->blockStates = Block::$fullList;
		$this->levelId = static::$levelIdCounter++;
		$this->blockMetadata = new BlockMetadataStore($this);
		$this->server = $server;
		$this->autoSave = $server->getAutoSave();

		/** @var LevelProvider $provider */

		if(is_subclass_of($provider, LevelProvider::class, true)){
			$this->provider = new $provider($this, $path);
		}else{
			throw new LevelException("Provider is not a subclass of LevelProvider");
		}
		$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.level.preparing", [$this->provider->getName()]));
		$this->generator = Generator::getGenerator($this->provider->getGenerator());

		$this->blockOrder = $provider::getProviderOrder();
		$this->useSections = $provider::usesChunkSection();

		$this->folderName = $name;
		$this->updateQueue = new ReversePriorityQueue();
		$this->updateQueue->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
		$this->time = (int) $this->provider->getTime();

		$this->chunkTickRadius = min($this->server->getViewDistance(), max(1, (int) $this->server->getProperty("chunk-ticking.tick-radius", 4)));
		$this->chunksPerTick = (int) $this->server->getProperty("chunk-ticking.per-tick", 40);
		$this->chunkGenerationQueueSize = (int) $this->server->getProperty("chunk-generation.queue-size", 8);
		$this->chunkPopulationQueueSize = (int) $this->server->getProperty("chunk-generation.population-queue-size", 2);
		$this->chunkTickList = [];
		$this->clearChunksOnTick = (bool) $this->server->getProperty("chunk-ticking.clear-tick-list", false);

		$this->timings = new LevelTimings($this);
		$this->temporalPosition = new Position(0, 0, 0, $this);
		$this->temporalVector = new Vector3(0, 0, 0);
		$this->tickRate = 1;
	}

	public function getTickRate(){
		return $this->tickRate;
	}

	public function setTickRate($tickRate){
		$this->tickRate = (int) $tickRate;
	}

	public function initLevel(){
		$generator = $this->generator;
		$this->generatorInstance = new $generator($this->provider->getGeneratorOptions());
		$this->generatorInstance->init($this, new Random($this->getSeed()));
	}

	/**
	 * @return BlockMetadataStore
	 */
	public function getBlockMetadata(){
		return $this->blockMetadata;
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @return LevelProvider
	 */
	final public function getProvider(){
		return $this->provider;
	}

	/**
	 * Returns the unique level identifier
	 *
	 * @return int
	 */
	final public function getId(){
		return $this->levelId;
	}

	public function close(){

		if($this->getAutoSave()){
			$this->save();
		}

		foreach($this->chunks as $chunk){
			$this->unloadChunk($chunk->getX(), $chunk->getZ(), false);
		}

		$this->provider->close();
		$this->provider = null;
		$this->blockMetadata = null;
		$this->blockCache = [];
		$this->temporalPosition = null;
	}
	
	public function addSound(Sound $sound, array $players = null){
		$pk = $sound->encode();

		if($players === null){
			$players = $this->getUsingChunk($sound->x >> 4, $sound->z >> 4);
		}

		if($pk !== null){
			if(!is_array($pk)){
				Server::broadcastPacket($players, $pk);
			}else{
				$this->server->batchPackets($players, $pk);
			}
		}
	}
	
	public function addParticle(Particle $particle, array $players = null){
		$pk = $particle->encode();

		if($players === null){
			$players = $this->getUsingChunk($particle->x >> 4, $particle->z >> 4);
		}

		if($pk !== null){
			if(!is_array($pk)){
				Server::broadcastPacket($players, $pk);
			}else{
				$this->server->batchPackets($players, $pk);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function getAutoSave(){
		return $this->autoSave === true;
	}

	/**
	 * @param bool $value
	 */
	public function setAutoSave($value){
		$this->autoSave = $value;
	}

	/**
	 * Unloads the current level from memory safely
	 *
	 * @param bool $force default false, force unload of default level
	 *
	 * @return bool
	 */
	public function unload($force = false){

		$ev = new LevelUnloadEvent($this);

		if($this === $this->server->getDefaultLevel() and $force !== true){
			$ev->setCancelled(true);
		}

		$this->server->getPluginManager()->callEvent($ev);

		if(!$force and $ev->isCancelled()){
			return false;
		}

		$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.level.unloading", [$this->getName()]));
		$defaultLevel = $this->server->getDefaultLevel();
		foreach($this->getPlayers() as $player){
			if($this === $defaultLevel or $defaultLevel === null){
				$player->close($player->getLeaveMessage(), "Forced default level unload");
			}elseif($defaultLevel instanceof Level){
				$player->teleport($this->server->getDefaultLevel()->getSafeSpawn());
			}
		}

		if($this === $defaultLevel){
			$this->server->setDefaultLevel(null);
		}

		$this->close();

		return true;
	}

	/**
	 * Gets the chunks being used by players
	 *
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Player[]
	 */
	public function getUsingChunk($X, $Z){
		return isset($this->usedChunks[$index = Level::chunkHash($X, $Z)]) ? $this->usedChunks[$index] : [];
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param int    $X
	 * @param int    $Z
	 * @param Player $player
	 */
	public function useChunk($X, $Z, Player $player){
		$index = Level::chunkHash($X, $Z);
		$this->loadChunk($X, $Z);
		$this->usedChunks[$index][$player->getId()] = $player;
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param int    $X
	 * @param int    $Z
	 * @param Player $player
	 */
	public function freeChunk($X, $Z, Player $player){
		unset($this->usedChunks[$index = Level::chunkHash($X, $Z)][$player->getId()]);
		$this->unloadChunkRequest($X, $Z, true);
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function checkTime(){
		if($this->stopTime == true){
			return;
		}else{
			$this->time += 1.25;
		}
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function sendTime(){
		$pk = new SetTimePacket();
		$pk->time = (int) $this->time;
		$pk->started = $this->stopTime == false;

		Server::broadcastPacket($this->players, $pk);
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param int $currentTick
	 *
	 * @return bool
	 */
	public function doTick($currentTick){

		$this->timings->doTick->startTiming();

		$this->checkTime();

		if(($currentTick % 200) === 0){
			$this->sendTime();
		}

		$this->unloadChunks();

		$X = null;
		$Z = null;

		//Do block updates
		$this->timings->doTickPending->startTiming();
		while($this->updateQueue->count() > 0 and $this->updateQueue->current()["priority"] <= $currentTick){
			$block = $this->getBlock($this->updateQueue->extract()["data"]);
			unset($this->updateQueueIndex[Level::blockHash($block->x, $block->y, $block->z)]);
			$block->onUpdate(self::BLOCK_UPDATE_SCHEDULED);
		}
		$this->timings->doTickPending->stopTiming();

		$this->timings->entityTick->startTiming();
		//Update entities that need update
		Timings::$tickEntityTimer->startTiming();
		foreach($this->updateEntities as $id => $entity){
			if($entity->closed or !$entity->onUpdate($currentTick)){
				unset($this->updateEntities[$id]);
			}
		}
		Timings::$tickEntityTimer->stopTiming();
		$this->timings->entityTick->stopTiming();

		$this->timings->tileEntityTick->startTiming();
		//Update tiles that need update
		if(count($this->updateTiles) > 0){
			//Timings::$tickTileEntityTimer->startTiming();
			foreach($this->updateTiles as $id => $tile){
				if($tile->onUpdate() !== true){
					unset($this->updateTiles[$id]);
				}
			}
			//Timings::$tickTileEntityTimer->stopTiming();
		}
		$this->timings->tileEntityTick->stopTiming();

		$this->timings->doTickTiles->startTiming();
		$this->tickChunks();
		$this->timings->doTickTiles->stopTiming();

		if(count($this->changedBlocks) > 0){
			if(count($this->players) > 0){
				foreach($this->changedBlocks as $index => $blocks){
					Level::getXZ($index, $X, $Z);
					if(count($blocks) > 512){
						foreach($this->getUsingChunk($X, $Z) as $p){
							$p->unloadChunk($X, $Z);
						}
					}else{
						$this->sendBlocks($this->getUsingChunk($X, $Z), $blocks, UpdateBlockPacket::FLAG_ALL);
					}
				}
			}

			$this->changedBlocks = [];

		}

		$this->processChunkRequest();

		$this->timings->doTick->stopTiming();
	}

	/**
	 * @param Player[] $target
	 * @param Block[]  $blocks
	 * @param int      $flags
	 */
	public function sendBlocks(array $target, array $blocks, $flags = UpdateBlockPacket::FLAG_NONE){
		$pk = new UpdateBlockPacket();
		foreach($blocks as $b){
			if($b === null){
				continue;
			}
            if($b instanceof Block){
                $pk->records[] = [$b->x, $b->z, $b->y, $b->getId(), $b->getDamage(), $flags];
            }else{
                $fullBlock = $this->getFullBlock($b->x, $b->y, $b->z);
                $pk->records[] = [$b->x, $b->z, $b->y, $fullBlock >> 4, $fullBlock & 0xf, $flags];
            }
		}
		Server::broadcastPacket($target, $pk);
	}

	public function clearCache(){
		$this->blockCache = [];
	}

	private function tickChunks(){
		if($this->chunksPerTick <= 0 or count($this->players) === 0){
			$this->chunkTickList = [];
			return;
		}

		$chunksPerPlayer = min(200, max(1, (int) ((($this->chunksPerTick - count($this->players)) / count($this->players)) + 0.5)));
		$randRange = 3 + $chunksPerPlayer / 30;
		$randRange = $randRange > $this->chunkTickRadius ? $this->chunkTickRadius : $randRange;

		foreach($this->players as $player){
			$x = $player->x >> 4;
			$z = $player->z >> 4;

			$index = Level::chunkHash($x, $z);
			$existingPlayers = max(0, isset($this->chunkTickList[$index]) ? $this->chunkTickList[$index] : 0);
			$this->chunkTickList[$index] = $existingPlayers + 1;
			for($chunk = 0; $chunk < $chunksPerPlayer; ++$chunk){
				$dx = mt_rand(-$randRange, $randRange);
				$dz = mt_rand(-$randRange, $randRange);
				$hash = Level::chunkHash($dx + $x, $dz + $z);
				if(!isset($this->chunkTickList[$hash]) and isset($this->chunks[$hash])){
					$this->chunkTickList[$hash] = -1;
				}
			}
		}

		$blockTest = 0;

		foreach($this->chunkTickList as $index => $players){
			Level::getXZ($index, $chunkX, $chunkZ);



			if(!isset($this->chunks[$index]) or ($chunk = $this->getChunk($chunkX, $chunkZ, false)) === null){
				unset($this->chunkTickList[$index]);
				continue;
			}elseif($players <= 0){
				unset($this->chunkTickList[$index]);
			}




			foreach($chunk->getEntities() as $entity){
				$entity->scheduleUpdate();
			}


			if($this->useSections){
				foreach($chunk->getSections() as $section){
					if(!($section instanceof EmptyChunkSection)){
						$Y = $section->getY();
						$k = mt_rand(0, 0x7fffffff);
						for($i = 0; $i < 3; ++$i, $k >>= 10){
							$x = $k & 0x0f;
							$y = ($k >> 8) & 0x0f;
							$z = ($k >> 16) & 0x0f;

							$blockId = $section->getBlockId($x, $y, $z);
							if(isset($this->randomTickBlocks[$blockId])){
								$class = $this->randomTickBlocks[$blockId];
								/** @var Block $block */
								$block = new $class($section->getBlockData($x, $y, $z));
								$block->x = $chunkX * 16 + $x;
								$block->y = ($Y << 4) + $y;
								$block->z = $chunkZ * 16 + $z;
								$block->level = $this;
								$block->onUpdate(self::BLOCK_UPDATE_RANDOM);
							}
						}
					}
				}
			}else{
				for($Y = 0; $Y < 8 and ($Y < 3 or $blockTest !== 0); ++$Y){
					$blockTest = 0;
					$k = mt_rand(0, 0x7fffffff);
					for($i = 0; $i < 3; ++$i, $k >>= 10){
						$x = $k & 0x0f;
						$y = ($k >> 8) & 0x0f;
						$z = ($k >> 16) & 0x0f;

						$blockTest |= $blockId = $chunk->getBlockId($x, $y + ($Y << 4), $z);
						if(isset($this->randomTickBlocks[$blockId])){
							$class = $this->randomTickBlocks[$blockId];
							/** @var Block $block */
							$block = new $class($chunk->getBlockData($x, $y + ($Y << 4), $z));
							$block->x = $chunkX * 16 + $x;
							$block->y = ($Y << 4) + $y;
							$block->z = $chunkZ * 16 + $z;
							$block->level = $this;
							$block->onUpdate(self::BLOCK_UPDATE_RANDOM);
						}
					}
				}
			}
		}

		if($this->clearChunksOnTick){
			$this->chunkTickList = [];
		}
	}

	public function __debugInfo(){
		return [];
	}

	/**
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function save($force = false){

		if($this->getAutoSave() === false and $force === false){
			return false;
		}

		$this->server->getPluginManager()->callEvent(new LevelSaveEvent($this));

		$this->provider->setTime((int) $this->time);
		$this->saveChunks();
		if($this->provider instanceof BaseLevelProvider){
			$this->provider->saveLevelData();
		}

		return true;
	}

	public function saveChunks(){
		foreach($this->chunks as $chunk){
			if($chunk->hasChanged()){
				$this->provider->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
				$this->provider->saveChunk($chunk->getX(), $chunk->getZ());
				$chunk->setChanged(false);
			}
		}
	}

	/**
	 * @param Vector3 $pos
	 */
	public function updateAround(Vector3 $pos){
		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x - 1, $pos->y, $pos->z))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x + 1, $pos->y, $pos->z))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x, $pos->y - 1, $pos->z))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x, $pos->y + 1, $pos->z))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x, $pos->y, $pos->z - 1))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x, $pos->y, $pos->z + 1))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}
	}

	/**
	 * @param Vector3 $pos
	 * @param int     $delay
	 */
	public function scheduleUpdate(Vector3 $pos, $delay){
		if(isset($this->updateQueueIndex[$index = Level::blockHash($pos->x, $pos->y, $pos->z)]) and $this->updateQueueIndex[$index] <= $delay){
			return;
		}
		$this->updateQueueIndex[$index] = $delay;
		$this->updateQueue->insert(new Vector3((int) $pos->x, (int) $pos->y, (int) $pos->z), (int) $delay + $this->server->getTick());
	}

	/**
	 * @param AxisAlignedBB $bb
	 *
	 * @return Block[]
	 */
	public function getCollisionBlocks(AxisAlignedBB $bb){
		$minX = Math::floorFloat($bb->minX);
		$minY = Math::floorFloat($bb->minY);
		$minZ = Math::floorFloat($bb->minZ);
		$maxX = Math::ceilFloat($bb->maxX);
		$maxY = Math::ceilFloat($bb->maxY);
		$maxZ = Math::ceilFloat($bb->maxZ);

		$collides = [];

		$v = $this->temporalVector;

		for($v->z = $minZ; $v->z <= $maxZ; ++$v->z){
			for($v->x = $minX; $v->x <= $maxX; ++$v->x){
				for($v->y = $minY - 1; $v->y <= $maxY; ++$v->y){
					$block = $this->getBlock($v);
					if($block->getId() !== 0){
						$block->collidesWithBB($bb, $collides);
					}
				}
			}
		}

		return $collides;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return bool
	 */
	public function isFullBlock(Vector3 $pos){
		if($pos instanceof Block){
			$bb = $pos->getBoundingBox();
		}else{
			$bb = $this->getBlock($pos)->getBoundingBox();
		}

		return $bb !== null and $bb->getAverageEdgeLength() >= 1;
	}

	/**
	 * @param Entity        $entity
	 * @param AxisAlignedBB $bb
	 * @param boolean       $entities
	 *
	 * @return AxisAlignedBB[]
	 */
	public function getCollisionCubes(Entity $entity, AxisAlignedBB $bb, $entities = true){
		$minX = Math::floorFloat($bb->minX);
		$minY = Math::floorFloat($bb->minY);
		$minZ = Math::floorFloat($bb->minZ);
		$maxX = Math::ceilFloat($bb->maxX);
		$maxY = Math::ceilFloat($bb->maxY);
		$maxZ = Math::ceilFloat($bb->maxZ);

		$collides = [];
		$v = $this->temporalVector;

		for($v->z = $minZ; $v->z <= $maxZ; ++$v->z){
			for($v->x = $minX; $v->x <= $maxX; ++$v->x){
				for($v->y = $minY - 1; $v->y < $maxY; ++$v->y){
					$block = $this->getBlock($v);
					if($block->getId() !== 0){
						$block->collidesWithBB($bb, $collides);
					}
				}
			}
		}

		if($entities){
			foreach($this->getCollidingEntities($bb->grow(0.25, 0.25, 0.25), $entity) as $ent){
				$collides[] = clone $ent->boundingBox;
			}
		}

		return $collides;
	}

	/*
	public function rayTraceBlocks(Vector3 $pos1, Vector3 $pos2, $flag = false, $flag1 = false, $flag2 = false){
		if(!is_nan($pos1->x) and !is_nan($pos1->y) and !is_nan($pos1->z)){
			if(!is_nan($pos2->x) and !is_nan($pos2->y) and !is_nan($pos2->z)){
				$x1 = (int) $pos1->x;
				$y1 = (int) $pos1->y;
				$z1 = (int) $pos1->z;
				$x2 = (int) $pos2->x;
				$y2 = (int) $pos2->y;
				$z2 = (int) $pos2->z;

				$block = $this->getBlock(Vector3::createVector($x1, $y1, $z1));

				if(!$flag1 or $block->getBoundingBox() !== null){
					$ob = $block->calculateIntercept($pos1, $pos2);
					if($ob !== null){
						return $ob;
					}
				}

				$movingObjectPosition = null;

				$k = 200;

				while($k-- >= 0){
					if(is_nan($pos1->x) or is_nan($pos1->y) or is_nan($pos1->z)){
						return null;
					}

					if($x1 === $x2 and $y1 === $y2 and $z1 === $z2){
						return $flag2 ? $movingObjectPosition : null;
					}

					$flag3 = true;
					$flag4 = true;
					$flag5 = true;

					$i = 999;
					$j = 999;
					$k = 999;

					if($x1 > $x2){
						$i = $x2 + 1;
					}elseif($x1 < $x2){
						$i = $x2;
					}else{
						$flag3 = false;
					}

					if($y1 > $y2){
						$j = $y2 + 1;
					}elseif($y1 < $y2){
						$j = $y2;
					}else{
						$flag4 = false;
					}

					if($z1 > $z2){
						$k = $z2 + 1;
					}elseif($z1 < $z2){
						$k = $z2;
					}else{
						$flag5 = false;
					}

					//TODO
				}
			}
		}
	}
	*/

	public function getFullLight(Vector3 $pos){
		$chunk = $this->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$level = 0;
		if($chunk instanceof FullChunk){
			$level = $chunk->getBlockSkyLight($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f);
			//TODO: decrease light level by time of day
			if($level < 15){
				$level = max($chunk->getBlockLight($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f));
			}
		}

		return $level;
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 *
	 * @return int bitmap, (id << 4) | data
	 */
	public function getFullBlock($x, $y, $z){
		return $this->getChunk($x >> 4, $z >> 4, false)->getFullBlock($x & 0x0f, $y & 0x7f, $z & 0x0f);
	}

	/**
	 * Gets the Block object on the Vector3 location
	 *
	 * @param Vector3 $pos
	 * @param boolean $cached
	 *
	 * @return Block
	 */
	public function getBlock(Vector3 $pos, $cached = true){
		$index = Level::blockHash($pos->x, $pos->y, $pos->z);
		if($cached === true and isset($this->blockCache[$index])){
			return $this->blockCache[$index];
		}elseif($pos->y >= 0 and $pos->y < 128 and isset($this->chunks[$chunkIndex = Level::chunkHash($pos->x >> 4, $pos->z >> 4)])){
			$fullState = $this->chunks[$chunkIndex]->getFullBlock($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f);
		}else{
			$fullState = 0;
		}

		$block = clone $this->blockStates[$fullState & 0xfff];

		$block->x = $pos->x;
		$block->y = $pos->y;
		$block->z = $pos->z;
		$block->level = $this;

		return $this->blockCache[$index] = $block;
	}

	public function updateAllLight(Vector3 $pos){
		$this->updateBlockSkyLight($pos->x, $pos->y, $pos->z);
		$this->updateBlockLight($pos->x, $pos->y, $pos->z);
	}

	public function updateBlockSkyLight($x, $y, $z){
		//TODO
	}

	public function updateBlockLight($x, $y, $z){
		$lightPropagationQueue = new \SplQueue();
		$lightRemovalQueue = new \SplQueue();
		$visited = [];
		$removalVisited = [];

		$oldLevel = $this->getBlockLightAt($x, $y, $z);
		$newLevel = (int) Block::$light[$this->getBlockIdAt($x, $y, $z)];

		if($oldLevel !== $newLevel){
			$this->setBlockLightAt($x, $y, $z, $newLevel);

			if($newLevel < $oldLevel){
				$removalVisited[Level::blockHash($x, $y, $z)] = true;
				$lightRemovalQueue->enqueue([new Vector3($x, $y, $z), $oldLevel]);
			}else{
				$visited[Level::blockHash($x, $y, $z)] = true;
				$lightPropagationQueue->enqueue(new Vector3($x, $y, $z));
			}
		}

		while(!$lightRemovalQueue->isEmpty()){
			/** @var Vector3 $node */
			$val = $lightRemovalQueue->dequeue();
			$node = $val[0];
			$lightLevel = $val[1];

			$this->computeRemoveBlockLight($node->x - 1, $node->y, $node->z, $lightLevel, $lightRemovalQueue, $lightPropagationQueue, $removalVisited, $visited);
			$this->computeRemoveBlockLight($node->x + 1, $node->y, $node->z, $lightLevel, $lightRemovalQueue, $lightPropagationQueue, $removalVisited, $visited);
			$this->computeRemoveBlockLight($node->x, $node->y - 1, $node->z, $lightLevel, $lightRemovalQueue, $lightPropagationQueue, $removalVisited, $visited);
			$this->computeRemoveBlockLight($node->x, $node->y + 1, $node->z, $lightLevel, $lightRemovalQueue, $lightPropagationQueue, $removalVisited, $visited);
			$this->computeRemoveBlockLight($node->x, $node->y, $node->z - 1, $lightLevel, $lightRemovalQueue, $lightPropagationQueue, $removalVisited, $visited);
			$this->computeRemoveBlockLight($node->x, $node->y, $node->z + 1, $lightLevel, $lightRemovalQueue, $lightPropagationQueue, $removalVisited, $visited);
		}

		while(!$lightPropagationQueue->isEmpty()){
			/** @var Vector3 $node */
			$node = $lightPropagationQueue->dequeue();

			$lightLevel = $this->getBlockLightAt($node->x, $node->y, $node->z) - (int) Block::$lightFilter[$this->getBlockIdAt($node->x, $node->y, $node->z)];

			if($lightLevel >= 1){
				$this->computeSpreadBlockLight($node->x - 1, $node->y, $node->z, $lightLevel, $lightPropagationQueue, $visited);
				$this->computeSpreadBlockLight($node->x + 1, $node->y, $node->z, $lightLevel, $lightPropagationQueue, $visited);
				$this->computeSpreadBlockLight($node->x, $node->y - 1, $node->z, $lightLevel, $lightPropagationQueue, $visited);
				$this->computeSpreadBlockLight($node->x, $node->y + 1, $node->z, $lightLevel, $lightPropagationQueue, $visited);
				$this->computeSpreadBlockLight($node->x, $node->y, $node->z - 1, $lightLevel, $lightPropagationQueue, $visited);
				$this->computeSpreadBlockLight($node->x, $node->y, $node->z + 1, $lightLevel, $lightPropagationQueue, $visited);
			}
		}
	}

	private function computeRemoveBlockLight($x, $y, $z, $currentLight, \SplQueue $queue, \SplQueue $spreadQueue, array &$visited, array &$spreadVisited){
		$current = $this->getBlockLightAt($x, $y, $z);

		if($current !== 0 and $current < $currentLight){
			$this->setBlockLightAt($x, $y, $z, 0);

			if(!isset($visited[$index = Level::blockHash($x, $y, $z)])){
				$visited[$index] = true;
				if($current > 1){
					$queue->enqueue([new Vector3($x, $y, $z), $current]);
				}
			}
		}elseif($current >= $currentLight){
			if(!isset($spreadVisited[$index = Level::blockHash($x, $y, $z)])){
				$spreadVisited[$index] = true;
				$spreadQueue->enqueue(new Vector3($x, $y, $z));
			}
		}
	}

	private function computeSpreadBlockLight($x, $y, $z, $currentLight, \SplQueue $queue, array &$visited){
		$current = $this->getBlockLightAt($x, $y, $z);

		if($current < $currentLight){
			$this->setBlockLightAt($x, $y, $z, $currentLight);

			if(!isset($visited[$index = Level::blockHash($x, $y, $z)])){
				$visited[$index] = true;
				if($currentLight > 1){
					$queue->enqueue(new Vector3($x, $y, $z));
				}
			}
		}
	}

	/**
	 * Sets on Vector3 the data from a Block object,
	 * does block updates and puts the changes to the send queue.
	 *
	 * If $direct is true, it'll send changes directly to players. if false, it'll be queued
	 * and the best way to send queued changes will be done in the next tick.
	 * This way big changes can be sent on a single chunk update packet instead of thousands of packets.
	 *
	 * If $update is true, it'll get the neighbour blocks (6 sides) and update them.
	 * If you are doing big changes, you might want to set this to false, then update manually.
	 *
	 * @param Vector3 $pos
	 * @param Block   $block
	 * @param bool    $direct @deprecated
	 * @param bool    $update
	 *
	 * @return bool Whether the block has been updated or not
	 */
	public function setBlock(Vector3 $pos, Block $block, $direct = false, $update = true){
		if($pos->y < 0 or $pos->y >= 128){
			return false;
		}

		unset($this->blockCache[$index = Level::blockHash($pos->x, $pos->y, $pos->z)]);

		if($this->getChunk($pos->x >> 4, $pos->z >> 4, true)->setBlock($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f, $block->getId(), $block->getDamage())){
			if(!($pos instanceof Position)){
				$pos = $this->temporalPosition->setComponents($pos->x, $pos->y, $pos->z);
			}
			$block->position($pos);
			$index = Level::chunkHash($pos->x >> 4, $pos->z >> 4);

			if($direct === true){
				$this->sendBlocks($this->getUsingChunk($pos->x >> 4, $pos->z >> 4), [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
			}else{
				if(!($pos instanceof Position)){
					$pos = $this->temporalPosition->setComponents($pos->x, $pos->y, $pos->z);
				}
				$block->position($pos);
				if(!isset($this->changedBlocks[$index])){
					$this->changedBlocks[$index] = [];
				}

				$this->changedBlocks[$index][Level::blockHash($block->x, $block->y, $block->z)] = clone $block;
			}

			if($update === true){
				$this->updateAllLight($block);

				$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($block));
				if(!$ev->isCancelled()){
					$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
					foreach($this->getNearbyEntities(new AxisAlignedBB($block->x - 1, $block->y - 1, $block->z - 1, $block->x + 2, $block->y + 2, $block->z + 2)) as $entity){
						$entity->scheduleUpdate();
					}
				}

				$this->updateAround($pos);
			}

			return true;
		}

		return false;
	}

	/**
	 * @param Vector3 $source
	 * @param Item    $item
	 * @param Vector3 $motion
	 * @param int     $delay
	 */
	public function dropItem(Vector3 $source, Item $item, Vector3 $motion = null, $delay = 10){
		$motion = $motion === null ? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1) : $motion;
		if($item->getId() > 0 and $item->getCount() > 0){
			$itemEntity = Entity::createEntity("Item", $this->getChunk($source->getX() >> 4, $source->getZ() >> 4), new Compound("", [
				"Pos" => new Enum("Pos", [
					new Double("", $source->getX()),
					new Double("", $source->getY()),
					new Double("", $source->getZ())
				]),

				"Motion" => new Enum("Motion", [
					new Double("", $motion->x),
					new Double("", $motion->y),
					new Double("", $motion->z)
				]),
				"Rotation" => new Enum("Rotation", [
					new Float("", lcg_value() * 360),
					new Float("", 0)
				]),
				"Health" => new Short("Health", 5),
				"Item" => new Compound("Item", [
					"id" => new Short("id", $item->getId()),
					"Damage" => new Short("Damage", $item->getDamage()),
					"Count" => new Byte("Count", $item->getCount())
				]),
				"PickupDelay" => new Short("PickupDelay", $delay)
			]));

			$itemEntity->spawnToAll();
		}
	}

	/**
	 * Tries to break a block using a item, including Player time checks if available
	 * It'll try to lower the durability if Item is a tool, and set it to Air if broken.
	 *
	 * @param Vector3 $vector
	 * @param Item    &$item (if null, can break anything)
	 * @param Player  $player
	 *
	 * @return boolean
	 */
	public function useBreakOn(Vector3 $vector, Item &$item = null, Player $player = null){
		$target = $this->getBlock($vector);
		//TODO: Adventure mode checks

		if($item === null){
			$item = Item::get(Item::AIR, 0, 0);
		}

		if($player instanceof Player){
			$ev = new BlockBreakEvent($player, $target, $item, ($player->getGamemode() & 0x01) === 1 ? true : false);

			if($player->isSurvival() and $item instanceof Item and !$target->isBreakable($item)){
				$ev->setCancelled();
			}

			if(!$player->isOp() and ($distance = $this->server->getSpawnRadius()) > -1){
				$t = new Vector2($target->x, $target->z);
				$s = new Vector2($this->getSpawnLocation()->x, $this->getSpawnLocation()->z);
				if(count($this->server->getOps()->getAll()) > 0 and $t->distance($s) <= $distance){ //set it to cancelled so plugins can bypass this
					$ev->setCancelled();
				}
			}
			$this->server->getPluginManager()->callEvent($ev);
			if($ev->isCancelled()){
				return false;
			}

			$breakTime = $player->isCreative() ? 0.15 : $target->getBreakTime($item);
			if($player->hasEffect(Effect::SWIFTNESS)){
				$breakTime *= pow(0.80, $player->getEffect(Effect::SWIFTNESS)->getAmplifier() + 1);
			}

			if(!$ev->getInstaBreak() and ($player->lastBreak + $breakTime) >= microtime(true)){
				return false;
			}

			$player->lastBreak = PHP_INT_MAX;
		}elseif($item instanceof Item and !$target->isBreakable($item)){
			return false;
		}

		$level = $target->getLevel();

		if($level instanceof Level){
			$above = $level->getBlock(new Vector3($target->x, $target->y + 1, $target->z));
			if($above instanceof Block){
				if($above->getId() === Item::FIRE){
					$level->setBlock($above, new Air(), true);
				}
			}
		}
		$drops = $target->getDrops($item); //Fixes tile entities being deleted before getting drops

		$players = $this->getUsingChunk($target->x >> 4, $target->z >> 4);
		if($player !== null){
			unset($players[$player->getId()]);
		}
		$this->addParticle(new DestroyBlockParticle($target->add(0.5, 0.5, 0.5), $target), $players);
		
		$target->onBreak($item);
		
		$tile = $this->getTile($target);
		if($tile instanceof Tile){
			if($tile instanceof InventoryHolder){
				if($tile instanceof Chest){
					$tile->unpair();
				}

				foreach($tile->getInventory()->getContents() as $chestItem){
					$this->dropItem($target, $chestItem);
				}
			}

			$tile->close();
		}

		if($item instanceof Item){
			$item->useOn($target);
			if($item->isTool() and $item->getDamage() >= $item->getMaxDurability()){
				$item = Item::get(Item::AIR, 0, 0);
			}
		}

		if(!($player instanceof Player) or $player->isSurvival()){
			foreach($drops as $drop){
				if($drop[2] > 0){
					$this->dropItem($vector->add(0.5, 0.5, 0.5), Item::get(...$drop));
				}
			}
		}

		return true;
	}

	/**
	 * Uses a item on a position and face, placing it or activating the block
	 *
	 * @param Vector3 $vector
	 * @param Item    $item
	 * @param int     $face
	 * @param float   $fx     default 0.0
	 * @param float   $fy     default 0.0
	 * @param float   $fz     default 0.0
	 * @param Player  $player default null
	 *
	 * @return boolean
	 */
	public function useItemOn(Vector3 $vector, Item &$item, $face, $fx = 0.0, $fy = 0.0, $fz = 0.0, Player $player = null){
		$target = $this->getBlock($vector);
		$block = $target->getSide($face);

		if($block->y > 127 or $block->y < 0){
			return false;
		}

		if($target->getId() === Item::AIR){
			return false;
		}

		if($player instanceof Player){
			$ev = new PlayerInteractEvent($player, $item, $target, $face, $target->getId() === 0 ? PlayerInteractEvent::RIGHT_CLICK_AIR : PlayerInteractEvent::RIGHT_CLICK_BLOCK);
			if(!$player->isOp() and ($distance = $this->server->getSpawnRadius()) > -1){
				$t = new Vector2($target->x, $target->z);
				$s = new Vector2($this->getSpawnLocation()->x, $this->getSpawnLocation()->z);
				if(count($this->server->getOps()->getAll()) > 0 and $t->distance($s) <= $distance){ //set it to cancelled so plugins can bypass this
					$ev->setCancelled();
				}
			}
			$this->server->getPluginManager()->callEvent($ev);
			if(!$ev->isCancelled()){
				$target->onUpdate(self::BLOCK_UPDATE_TOUCH);
				if($target->canBeActivated() === true and $target->onActivate($item, $player) === true){
					return true;
				}

				if($item->canBeActivated() and $item->onActivate($this, $player, $block, $target, $face, $fx, $fy, $fz)){
					if($item->getCount() <= 0){
						$item = Item::get(Item::AIR, 0, 0);

						return true;
					}
				}
			}else{
				return false;
			}
		}elseif($target->canBeActivated() === true and $target->onActivate($item, $player) === true){
			return true;
		}

		if($item->isPlaceable()){
			$hand = $item->getBlock();
			$hand->position($block);
		}elseif($block->getId() === Item::FIRE){
			$this->setBlock($block, new Air(), true);

			return false;
		}else{
			return false;
		}

		if(!($block->canBeReplaced() === true or ($hand->getId() === Item::SLAB and $block->getId() === Item::SLAB))){
			return false;
		}

		if($target->canBeReplaced() === true){
			$block = $target;
			$hand->position($block);
			//$face = -1;
		}

		if($hand->isSolid() === true and $hand->getBoundingBox() !== null){
			$entities = $this->getCollidingEntities($hand->getBoundingBox());
			$realCount = 0;
			foreach($entities as $e){
				if($e instanceof Arrow or $e instanceof DroppedItem){
					continue;
				}
				++$realCount;
			}

			if($realCount > 0){
				return false; //Entity in block
			}
		}


		if($player instanceof Player){
			$ev = new BlockPlaceEvent($player, $hand, $block, $target, $item);
			if(!$player->isOp() and ($distance = $this->server->getSpawnRadius()) > -1){
				$t = new Vector2($target->x, $target->z);
				$s = new Vector2($this->getSpawnLocation()->x, $this->getSpawnLocation()->z);
				if(count($this->server->getOps()->getAll()) > 0 and $t->distance($s) <= $distance){ //set it to cancelled so plugins can bypass this
					$ev->setCancelled();
				}
			}
			$this->server->getPluginManager()->callEvent($ev);
			if($ev->isCancelled()){
				return false;
			}
		}

		if($hand->place($item, $block, $target, $face, $fx, $fy, $fz, $player) === false){
			return false;
		}

		if($hand->getId() === Item::SIGN_POST or $hand->getId() === Item::WALL_SIGN){
			$tile = Tile::createTile("Sign", $this->getChunk($block->x >> 4, $block->z >> 4), new Compound(false, [
				"id" => new String("id", Tile::SIGN),
				"x" => new Int("x", $block->x),
				"y" => new Int("y", $block->y),
				"z" => new Int("z", $block->z),
				"Text1" => new String("Text1", ""),
				"Text2" => new String("Text2", ""),
				"Text3" => new String("Text3", ""),
				"Text4" => new String("Text4", "")
			]));
			if($player instanceof Player){
				$tile->namedtag->Creator = new String("Creator", $player->getName());
			}
		}
		$item->setCount($item->getCount() - 1);
		if($item->getCount() <= 0){
			$item = Item::get(Item::AIR, 0, 0);
		}

		return true;
	}

	/**
	 * @param int $entityId
	 *
	 * @return Entity
	 */
	public function getEntity($entityId){
		return isset($this->entities[$entityId]) ? $this->entities[$entityId] : null;
	}

	/**
	 * Gets the list of all the entities in this level
	 *
	 * @return Entity[]
	 */
	public function getEntities(){
		return $this->entities;
	}

	/**
	 * Returns the entities colliding the current one inside the AxisAlignedBB
	 *
	 * @param AxisAlignedBB $bb
	 * @param Entity        $entity
	 *
	 * @return Entity[]
	 */
	public function getCollidingEntities(AxisAlignedBB $bb, Entity $entity = null){
		$nearby = [];

		if($entity === null or $entity->canCollide){
			$minX = Math::floorFloat(($bb->minX - 2) / 16);
			$maxX = Math::ceilFloat(($bb->maxX + 2) / 16);
			$minZ = Math::floorFloat(($bb->minZ - 2) / 16);
			$maxZ = Math::ceilFloat(($bb->maxZ + 2) / 16);

			for($x = $minX; $x <= $maxX; ++$x){
				for($z = $minZ; $z <= $maxZ; ++$z){
					foreach($this->getChunkEntities($x, $z) as $ent){
						if($ent !== $entity and ($entity === null or $entity->canCollideWith($ent)) and $ent->boundingBox->intersectsWith($bb)){
							$nearby[] = $ent;
						}
					}
				}
			}
		}

		return $nearby;
	}

	/**
	 * Returns the entities near the current one inside the AxisAlignedBB
	 *
	 * @param AxisAlignedBB $bb
	 * @param Entity        $entity
	 *
	 * @return Entity[]
	 */
	public function getNearbyEntities(AxisAlignedBB $bb, Entity $entity = null){
		$nearby = [];

		$minX = Math::floorFloat(($bb->minX - 2) / 16);
		$maxX = Math::ceilFloat(($bb->maxX + 2) / 16);
		$minZ = Math::floorFloat(($bb->minZ - 2) / 16);
		$maxZ = Math::ceilFloat(($bb->maxZ + 2) / 16);

		for($x = $minX; $x <= $maxX; ++$x){
			for($z = $minZ; $z <= $maxZ; ++$z){
				foreach($this->getChunkEntities($x, $z) as $ent){
					if($ent !== $entity and $ent->boundingBox->intersectsWith($bb)){
						$nearby[] = $ent;
					}
				}
			}
		}

		return $nearby;
	}

	/**
	 * Returns a list of the Tile entities in this level
	 *
	 * @return Tile[]
	 */
	public function getTiles(){
		return $this->tiles;
	}

	/**
	 * @param $tileId
	 *
	 * @return Tile
	 */
	public function getTileById($tileId){
		return isset($this->tiles[$tileId]) ? $this->tiles[$tileId] : null;
	}

	/**
	 * Returns a list of the players in this level
	 *
	 * @return Player[]
	 */
	public function getPlayers(){
		return $this->players;
	}

	/**
	 * Returns the Tile in a position, or null if not found
	 *
	 * @param Vector3 $pos
	 *
	 * @return Tile
	 */
	public function getTile(Vector3 $pos){
		$chunk = $this->getChunk($pos->x >> 4, $pos->z >> 4, false);

		if($chunk !== null){
			return $chunk->getTile($pos->x & 0x0f, $pos->y & 0xff, $pos->z & 0x0f);
		}

		return null;
	}

	/**
	 * Returns a list of the entities on a given chunk
	 *
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Entity[]
	 */
	public function getChunkEntities($X, $Z){
		return ($chunk = $this->getChunk($X, $Z)) !== null ? $chunk->getEntities() : [];
	}

	/**
	 * Gives a list of the Tile entities on a given chunk
	 *
	 * @param int $X
	 * @param int $Z
	 *
	 * @return Tile[]
	 */
	public function getChunkTiles($X, $Z){
		return ($chunk = $this->getChunk($X, $Z)) !== null ? $chunk->getTiles() : [];
	}

	/**
	 * Gets the raw block id.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-255
	 */
	public function getBlockIdAt($x, $y, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getBlockId($x & 0x0f, $y & 0x7f, $z & 0x0f);
	}

	/**
	 * Sets the raw block id.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id 0-255
	 */
	public function setBlockIdAt($x, $y, $z, $id){
		unset($this->blockCache[Level::blockHash($x, $y, $z)]);
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockId($x & 0x0f, $y & 0x7f, $z & 0x0f, $id & 0xff);

		if(!isset($this->changedBlocks[$index = Level::chunkHash($x >> 4, $z >> 4)])){
			$this->changedBlocks[$index] = [];
		}
		$this->changedBlocks[$index][Level::blockHash($x, $y, $z)] = new Vector3($x, $y, $z);
	}

	/**
	 * Gets the raw block metadata
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-15
	 */
	public function getBlockDataAt($x, $y, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getBlockData($x & 0x0f, $y & 0x7f, $z & 0x0f);
	}

	/**
	 * Sets the raw block metadata.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $data 0-15
	 */
	public function setBlockDataAt($x, $y, $z, $data){
		unset($this->blockCache[Level::blockHash($x, $y, $z)]);
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockData($x & 0x0f, $y & 0x7f, $z & 0x0f, $data & 0x0f);

		if(!isset($this->changedBlocks[$index = Level::chunkHash($x >> 4, $z >> 4)])){
			$this->changedBlocks[$index] = [];
		}
		$this->changedBlocks[$index][Level::blockHash($x, $y, $z)] = new Vector3($x, $y, $z);
	}

	/**
	 * Gets the raw block skylight level
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-15
	 */
	public function getBlockSkyLightAt($x, $y, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getBlockSkyLight($x & 0x0f, $y & 0x7f, $z & 0x0f);
	}

	/**
	 * Sets the raw block skylight level.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $level 0-15
	 */
	public function setBlockSkyLightAt($x, $y, $z, $level){
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockSkyLight($x & 0x0f, $y & 0x7f, $z & 0x0f, $level & 0x0f);
	}

	/**
	 * Gets the raw block light level
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-15
	 */
	public function getBlockLightAt($x, $y, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getBlockLight($x & 0x0f, $y & 0x7f, $z & 0x0f);
	}

	/**
	 * Sets the raw block light level.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $level 0-15
	 */
	public function setBlockLightAt($x, $y, $z, $level){
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockLight($x & 0x0f, $y & 0x7f, $z & 0x0f, $level & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBiomeId($x, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getBiomeId($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int[]
	 */
	public function getBiomeColor($x, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getBiomeColor($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getHeightMap($x, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getHeightMap($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $biomeId
	 */
	public function setBiomeId($x, $z, $biomeId){
		$this->getChunk($x >> 4, $z >> 4, true)->setBiomeId($x & 0x0f, $z & 0x0f, $biomeId);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $R
	 * @param int $G
	 * @param int $B
	 */
	public function setBiomeColor($x, $z, $R, $G, $B){
		$this->getChunk($x >> 4, $z >> 4, true)->setBiomeColor($x & 0x0f, $z & 0x0f, $R, $G, $B);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $value
	 */
	public function setHeightMap($x, $z, $value){
		$this->getChunk($x >> 4, $z >> 4, true)->setHeightMap($x & 0x0f, $z & 0x0f, $value);
	}

	/**
	 * @return FullChunk[]|Chunk[]
	 */
	public function getChunks(){
		return $this->chunks;
	}

	/**
	 * Gets the Chunk object
	 *
	 * @param int  $x
	 * @param int  $z
	 * @param bool $create Whether to generate the chunk if it does not exist
	 *
	 * @return FullChunk|Chunk
	 */
	public function getChunk($x, $z, $create = false){
		if(isset($this->chunks[$index = Level::chunkHash($x, $z)])){
			return $this->chunks[$index];
		}elseif($this->loadChunk($x, $z, $create) and $this->chunks[$index] !== null){
			return $this->chunks[$index];
		}

		return null;
	}

	/**
	 * @param int  $x
	 * @param int  $z
	 * @param bool $create
	 *
	 * @return FullChunk|Chunk
	 *
	 * @deprecated
	 */
	public function getChunkAt($x, $z, $create = false){
		return $this->getChunk($x, $z, $create);
	}

	public function generateChunkCallback($x, $z, FullChunk $chunk){
		Timings::$generationTimer->startTiming();
		if(isset($this->chunkPopulationQueue[$index = Level::chunkHash($x, $z)])){
			$oldChunk = $this->getChunk($x, $z, false);
			for($xx = -1; $xx <= 1; ++$xx){
				for($zz = -1; $zz <= 1; ++$zz){
					unset($this->chunkPopulationLock[Level::chunkHash($x + $xx, $z + $zz)]);
				}
			}
			unset($this->chunkPopulationQueue[$index]);
			$chunk->setProvider($this->provider);
			$this->setChunk($x, $z, $chunk);
			$chunk = $this->getChunk($x, $z, false);
			if($chunk !== null and ($oldChunk === null or $oldChunk->isPopulated() === false) and $chunk->isPopulated() and $chunk->getProvider() !== null){
				$this->server->getPluginManager()->callEvent(new ChunkPopulateEvent($chunk));
			}
		}elseif(isset($this->chunkGenerationQueue[$index]) or isset($this->chunkPopulationLock[$index])){
			unset($this->chunkGenerationQueue[$index]);
			unset($this->chunkPopulationLock[$index]);
			$chunk->setProvider($this->provider);
			$this->setChunk($x, $z, $chunk);
		}
		Timings::$generationTimer->stopTiming();
	}

	public function setChunk($x, $z, FullChunk $chunk = null, $unload = true){
		if($chunk === null){
			return;
		}
		$index = Level::chunkHash($x, $z);
		if($unload){
			foreach($this->getUsingChunk($x, $z) as $player){
				$player->unloadChunk($x, $z);
			}
			$this->provider->setChunk($x, $z, $chunk);
			$this->chunks[$index] = $chunk;
		}else{
			$this->provider->setChunk($x, $z, $chunk);
			$this->chunks[$index] = $chunk;
		}

		$chunk->setChanged();
	}

	/**
	 * Gets the highest block Y value at a specific $x and $z
	 *
	 * @param int $x
	 * @param int $z
	 *
	 * @return int 0-127
	 */
	public function getHighestBlockAt($x, $z){
		return $this->getChunk($x >> 4, $z >> 4, true)->getHighestBlockAt($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkLoaded($x, $z){
		return isset($this->chunks[Level::chunkHash($x, $z)]) or $this->provider->isChunkLoaded($x, $z);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkGenerated($x, $z){
		$chunk = $this->getChunk($x, $z);
		return $chunk !== null ? $chunk->isGenerated() : false;
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkPopulated($x, $z){
		$chunk = $this->getChunk($x, $z);
		return $chunk !== null ? $chunk->isPopulated() : false;
	}

	/**
	 * Returns a Position pointing to the spawn
	 *
	 * @return Position
	 */
	public function getSpawnLocation(){
		return Position::fromObject($this->provider->getSpawn(), $this);
	}

	/**
	 * Sets the level spawn location
	 *
	 * @param Vector3 $pos
	 */
	public function setSpawnLocation(Vector3 $pos){
		$previousSpawn = $this->getSpawnLocation();
		$this->provider->setSpawn($pos);
		$this->server->getPluginManager()->callEvent(new SpawnChangeEvent($this, $previousSpawn));
	}

	public function requestChunk($x, $z, Player $player, $order = LevelProvider::ORDER_ZXY){
		$index = Level::chunkHash($x, $z);
		if(!isset($this->chunkSendQueue[$index])){
			$this->chunkSendQueue[$index] = [];
		}

		$this->chunkSendQueue[$index][spl_object_hash($player)] = $player;
	}

	private function processChunkRequest(){
		if(count($this->chunkSendQueue) > 0){
			$this->timings->syncChunkSendTimer->startTiming();

			$x = null;
			$z = null;
			foreach($this->chunkSendQueue as $index => $players){
				if(isset($this->chunkSendTasks[$index])){
					continue;
				}
				Level::getXZ($index, $x, $z);
				$this->chunkSendTasks[$index] = true;
				$this->timings->syncChunkSendPrepareTimer->startTiming();
				$task = $this->provider->requestChunkTask($x, $z);
				if($task instanceof AsyncTask){
					$this->server->getScheduler()->scheduleAsyncTask($task);
				}
				$this->timings->syncChunkSendPrepareTimer->stopTiming();
			}

			$this->timings->syncChunkSendTimer->stopTiming();
		}
	}

	public function chunkRequestCallback($x, $z, $payload){
		$index = Level::chunkHash($x, $z);
		if(isset($this->chunkSendTasks[$index])){
			foreach($this->chunkSendQueue[$index] as $player){
				/** @var Player $player */
				if($player->isConnected() and isset($player->usedChunks[$index])){
					$player->sendChunk($x, $z, $payload);
				}
			}
			unset($this->chunkSendQueue[$index]);
			unset($this->chunkSendTasks[$index]);
		}
	}

	/**
	 * Removes the entity from the level index
	 *
	 * @param Entity $entity
	 *
	 * @throws LevelException
	 */
	public function removeEntity(Entity $entity){
		if($entity->getLevel() !== $this){
			throw new LevelException("Invalid Entity level");
		}

		if($entity instanceof Player){
			unset($this->players[$entity->getId()]);
			//$this->everyoneSleeping();
		}else{
			$entity->kill();
		}

		unset($this->entities[$entity->getId()]);
		unset($this->updateEntities[$entity->getId()]);
	}

	/**
	 * @param Entity $entity
	 *
	 * @throws LevelException
	 */
	public function addEntity(Entity $entity){
		if($entity->getLevel() !== $this){
			throw new LevelException("Invalid Entity level");
		}
		if($entity instanceof Player){
			$this->players[$entity->getId()] = $entity;
		}
		$this->entities[$entity->getId()] = $entity;
	}

	/**
	 * @param Tile $tile
	 *
	 * @throws LevelException
	 */
	public function addTile(Tile $tile){
		if($tile->getLevel() !== $this){
			throw new LevelException("Invalid Tile level");
		}
		$this->tiles[$tile->getId()] = $tile;
	}

	/**
	 * @param Tile $tile
	 *
	 * @throws LevelException
	 */
	public function removeTile(Tile $tile){
		if($tile->getLevel() !== $this){
			throw new LevelException("Invalid Tile level");
		}

		unset($this->tiles[$tile->getId()]);
		unset($this->updateTiles[$tile->getId()]);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkInUse($x, $z){
		return isset($this->usedChunks[Level::chunkHash($x, $z)]) and count($this->usedChunks[Level::chunkHash($x, $z)]) > 0;
	}

	/**
	 * @param int  $x
	 * @param int  $z
	 * @param bool $generate
	 *
	 * @return bool
	 */
	public function loadChunk($x, $z, $generate = true){
		if(isset($this->chunks[$index = Level::chunkHash($x, $z)])){
			return true;
		}

		$this->cancelUnloadChunkRequest($x, $z);

		$chunk = $this->provider->getChunk($x, $z, $generate);
		if($chunk !== null){
			$this->chunks[$index] = $chunk;
			$chunk->initChunk();
		}else{
			$this->timings->syncChunkLoadTimer->startTiming();
			$this->provider->loadChunk($x, $z, $generate);
			$this->timings->syncChunkLoadTimer->stopTiming();

			if(($chunk = $this->provider->getChunk($x, $z)) !== null){
				$this->chunks[$index] = $chunk;
				$chunk->initChunk();
			}else{
				return false;
			}
		}

		if($chunk->getProvider() !== null){
			$this->server->getPluginManager()->callEvent(new ChunkLoadEvent($chunk, !$chunk->isGenerated()));
		}else{
			$this->unloadChunk($x, $z, false);
			return false;
		}

		return true;
	}

	private function queueUnloadChunk($x, $z){
		$this->unloadQueue[$index = Level::chunkHash($x, $z)] = microtime(true);
		unset($this->chunkTickList[$index]);
	}

	public function unloadChunkRequest($x, $z, $safe = true){
		if(($safe === true and $this->isChunkInUse($x, $z)) or $this->isSpawnChunk($x, $z)){
			return false;
		}

		$this->queueUnloadChunk($x, $z);

		return true;
	}

	public function cancelUnloadChunkRequest($x, $z){
		unset($this->unloadQueue[Level::chunkHash($x, $z)]);
	}

	public function unloadChunk($x, $z, $safe = true){
		if(($safe === true and $this->isChunkInUse($x, $z))){
			return false;
		}

		$this->timings->doChunkUnload->startTiming();

		$index = Level::chunkHash($x, $z);

		$chunk = $this->getChunk($x, $z);

		if($chunk !== null and $chunk->getProvider() !== null){
			$this->server->getPluginManager()->callEvent($ev = new ChunkUnloadEvent($chunk));
			if($ev->isCancelled()){
				return false;
			}
		}

		try{
			if($chunk !== null and $this->getAutoSave()){
				$this->provider->setChunk($x, $z, $chunk);
				$this->provider->saveChunk($x, $z);
			}
			$this->provider->unloadChunk($x, $z, $safe);
		}catch(\Exception $e){
			$logger = $this->server->getLogger();
			$logger->error($this->server->getLanguage()->translateString("pocketmine.level.chunkUnloadError", [$e->getMessage()]));
			if($logger instanceof MainLogger){
				$logger->logException($e);
			}
		}

		unset($this->chunks[$index]);
		unset($this->usedChunks[$index]);
		unset($this->chunkTickList[$index]);

		$this->timings->doChunkUnload->stopTiming();

		return true;
	}

	/**
	 * Returns true if the spawn is part of the spawn
	 *
	 * @param int $X
	 * @param int $Z
	 *
	 * @return bool
	 */
	public function isSpawnChunk($X, $Z){
		$spawnX = $this->provider->getSpawn()->getX() >> 4;
		$spawnZ = $this->provider->getSpawn()->getZ() >> 4;

		return abs($X - $spawnX) <= 1 and abs($Z - $spawnZ) <= 1;
	}

	/**
	 * Returns the raw spawnpoint
	 *
	 * @deprecated
	 * @return Position
	 */
	public function getSpawn(){
		return $this->getSpawnLocation();
	}

	/**
	 * @param Vector3 $spawn default null
	 *
	 * @return bool|Position
	 */
	public function getSafeSpawn($spawn = null){
		if(!($spawn instanceof Vector3) or $spawn->y <= 0){
			$spawn = $this->getSpawnLocation();
		}
		if($spawn instanceof Vector3){
			$v = $spawn->floor();
			$chunk = $this->getChunk($v->x >> 4, $v->z >> 4, false);
			$x = $v->x & 0x0f;
			$z = $v->z & 0x0f;
			if($chunk !== null){
				for(; $v->y > 0; --$v->y){
					if($v->y < 127 and Block::$solid[$chunk->getBlockId($x, $v->y & 0x7f, $z)]){
						$v->y++;
						break;
					}
				}
				for(; $v->y >= 0 and $v->y < 128; ++$v->y){
					if(!Block::$solid[$chunk->getBlockId($x, $v->y + 1, $z)]){
						if(!Block::$solid[$chunk->getBlockId($x, $v->y, $z)]){
							return new Position($spawn->x, $v->y === (int) $spawn->y ? $spawn->y : $v->y, $spawn->z, $this);
						}
					}else{
						++$v->y;
					}
				}
			}

			return new Position($spawn->x, $v->y, $spawn->z, $this);
		}

		return false;
	}

	/**
	 * Sets the spawnpoint
	 *
	 * @param Vector3 $pos
	 *
	 * @deprecated
	 */
	public function setSpawn(Vector3 $pos){
		$this->setSpawnLocation($pos);
	}

	/**
	 * Gets the current time
	 *
	 * @return int
	 */
	public function getTime(){
		return (int) $this->time;
	}

	/**
	 * Returns the Level name
	 *
	 * @return string
	 */
	public function getName(){
		return $this->provider->getName();
	}

	/**
	 * Returns the Level folder name
	 *
	 * @return string
	 */
	public function getFolderName(){
		return $this->folderName;
	}

	/**
	 * Sets the current time on the level
	 *
	 * @param int $time
	 */
	public function setTime($time){
		$this->time = (int) $time;
		$this->sendTime();
	}

	/**
	 * Stops the time for the level, will not save the lock state to disk
	 */
	public function stopTime(){
		$this->stopTime = true;
		$this->sendTime();
	}

	/**
	 * Start the time again, if it was stopped
	 */
	public function startTime(){
		$this->stopTime = false;
		$this->sendTime();
	}

	/**
	 * Gets the level seed
	 *
	 * @return int
	 */
	public function getSeed(){
		return $this->provider->getSeed();
	}

	/**
	 * Sets the seed for the level
	 *
	 * @param int $seed
	 */
	public function setSeed($seed){
		$this->provider->setSeed($seed);
	}


	public function populateChunk($x, $z, $force = false){
		if(isset($this->chunkPopulationQueue[$index = Level::chunkHash($x, $z)]) or (count($this->chunkPopulationQueue) >= $this->chunkPopulationQueueSize and !$force)){
			return false;
		}

		Timings::$generationTimer->startTiming();
		if(!$this->isChunkPopulated($x, $z)){
			$populate = true;
			for($xx = -1; $xx <= 1; ++$xx){
				for($zz = -1; $zz <= 1; ++$zz){
					if(isset($this->chunkPopulationLock[Level::chunkHash($x + $xx, $z + $zz)])){
						$populate = false;
					}elseif(!$this->isChunkGenerated($x + $xx, $z + $zz)){
						$populate = false;
						$this->generateChunk($x + $xx, $z + $zz, $force);
					}
				}
			}

			if($populate){
				if(!isset($this->chunkPopulationQueue[$index])){
					$this->chunkPopulationQueue[$index] = true;
					for($xx = -1; $xx <= 1; ++$xx){
						for($zz = -1; $zz <= 1; ++$zz){
							$this->chunkPopulationLock[Level::chunkHash($x + $xx, $z + $zz)] = true;
						}
					}
					$task = new PopulationTask($this, $this->generatorInstance, $this->getChunk($x, $z, true));
					$this->server->getScheduler()->scheduleAsyncTask($task);
				}
				Timings::$generationTimer->stopTiming();
				return false;
			}

			Timings::$generationTimer->stopTiming();
			return false;
		}
		Timings::$generationTimer->stopTiming();
		return true;
	}

	public function generateChunk($x, $z, $force = false){
		if(count($this->chunkGenerationQueue) >= $this->chunkGenerationQueueSize and !$force){
			return;
		}

		if(!isset($this->chunkGenerationQueue[$index = Level::chunkHash($x, $z)])){
			Timings::$generationTimer->startTiming();
			$this->chunkGenerationQueue[$index] = true;
			$task = new GenerationTask($this, $this->generatorInstance, $this->getChunk($x, $z, true));
			$this->server->getScheduler()->scheduleAsyncTask($task);
			Timings::$generationTimer->stopTiming();
		}
	}

	public function regenerateChunk($x, $z){
		$this->unloadChunk($x, $z, false);

		$this->cancelUnloadChunkRequest($x, $z);

		$this->generateChunk($x, $z);
		//TODO: generate & refresh chunk from the generator object
	}

	public function doChunkGarbageCollection(){
		$this->timings->doChunkGC->startTiming();

		$X = null;
		$Z = null;

		foreach($this->chunks as $index => $chunk){
			if(!isset($this->unloadQueue[$index]) and (!isset($this->usedChunks[$index]) or count($this->usedChunks[$index]) === 0)){
				Level::getXZ($index, $X, $Z);
				if(!$this->isSpawnChunk($X, $Z)){
					$this->unloadChunkRequest($X, $Z, true);
				}
			}
		}

		foreach($this->provider->getLoadedChunks() as $chunk){
			if(!isset($this->chunks[Level::chunkHash($chunk->getX(), $chunk->getZ())])){
				$this->provider->unloadChunk($chunk->getX(), $chunk->getZ(), false);
			}
		}

		$this->provider->doGarbageCollection();

		$this->timings->doChunkGC->stopTiming();
	}

	private function unloadChunks(){
		if(count($this->unloadQueue) > 0){
			$X = null;
			$Z = null;
			foreach($this->unloadQueue as $index => $time){
				Level::getXZ($index, $X, $Z);

				//If the chunk can't be unloaded, it stays on the queue
				if($this->unloadChunk($X, $Z, true)){
					unset($this->unloadQueue[$index]);
				}
			}
		}
	}

	public function setMetadata($metadataKey, MetadataValue $metadataValue){
		$this->server->getLevelMetadata()->setMetadata($this, $metadataKey, $metadataValue);
	}

	public function getMetadata($metadataKey){
		return $this->server->getLevelMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata($metadataKey){
		return $this->server->getLevelMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata($metadataKey, Plugin $plugin){
		$this->server->getLevelMetadata()->removeMetadata($this, $metadataKey, $plugin);
	}
}
