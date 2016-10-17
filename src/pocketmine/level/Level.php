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

declare(strict_types=1);

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
use pocketmine\block\Fire;
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
use pocketmine\entity\Effect;
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
use pocketmine\level\generator\GeneratorRegisterTask;
use pocketmine\level\generator\GeneratorUnregisterTask;
use pocketmine\level\generator\LightPopulationTask;
use pocketmine\level\generator\PopulationTask;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\Particle;
use pocketmine\level\sound\Sound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\metadata\BlockMetadataStore;
use pocketmine\metadata\Metadatable;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\utils\LevelException;
use pocketmine\utils\Random;
use pocketmine\utils\ReversePriorityQueue;

#include <rules/Level.h>

class Level implements ChunkManager, Metadatable{

	private static $levelIdCounter = 1;
	private static $chunkLoaderCounter = 1;
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

	private $motionToSend = [];
	private $moveToSend = [];

	/** @var Player[] */
	private $players = [];

	/** @var Entity[] */
	private $entities = [];

	/** @var Entity[] */
	public $updateEntities = [];
	/** @var Tile[] */
	public $updateTiles = [];

	private $blockCache = [];

	/** @var DataPacket[] */
	private $chunkCache = [];

	private $cacheChunks = false;

	private $sendTimeTicker = 0;

	/** @var Server */
	private $server;

	/** @var int */
	private $levelId;

	/** @var LevelProvider */
	private $provider;

	/** @var ChunkLoader[] */
	private $loaders = [];
	/** @var int[] */
	private $loaderCounter = [];
	/** @var ChunkLoader[][] */
	private $chunkLoaders = [];
	/** @var Player[][] */
	private $playerLoaders = [];

	/** @var DataPacket[] */
	private $chunkPackets = [];

	/** @var float[] */
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

	public $sleepTicks = 0;

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
		Block::FIRE => Fire::class,
		Block::BEETROOT_BLOCK => Beetroot::class,
	];

	/** @var LevelTimings */
	public $timings;

	private $tickRate;
	public $tickRateTime = 0;
	public $tickRateCounter = 0;

	/** @var Generator */
	private $generator;
	/** @var Generator */
	private $generatorInstance;

	public static function chunkHash(int $x, int $z){
		return PHP_INT_SIZE === 8 ? (($x & 0xFFFFFFFF) << 32) | ($z & 0xFFFFFFFF) : $x . ":" . $z;
	}

	public static function blockHash(int $x, int $y, int $z){
		return PHP_INT_SIZE === 8 ? (($x & 0xFFFFFFF) << 35) | (($y & 0x7f) << 28) | ($z & 0xFFFFFFF) : $x . ":" . $y .":". $z;
	}

	public static function chunkBlockHash(int $x, int $y, int $z) : int{
		return ($x << 11) | ($z << 7) | $y;
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

	public static function generateChunkLoaderId(ChunkLoader $loader) : int{
		if($loader->getLoaderId() === 0 or $loader->getLoaderId() === null or $loader->getLoaderId() === null){
			return self::$chunkLoaderCounter++;
		}else{
			throw new \InvalidStateException("ChunkLoader has a loader id already assigned: " . $loader->getLoaderId());
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
	public function __construct(Server $server, string $name, string $path, string $provider){
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
		$this->clearChunksOnTick = (bool) $this->server->getProperty("chunk-ticking.clear-tick-list", true);
		$this->cacheChunks = (bool) $this->server->getProperty("chunk-sending.cache-chunks", false);

		$this->timings = new LevelTimings($this);
		$this->temporalPosition = new Position(0, 0, 0, $this);
		$this->temporalVector = new Vector3(0, 0, 0);
		$this->tickRate = 1;
	}

	public function getTickRate() : int{
		return $this->tickRate;
	}

	public function getTickRateTime() : float{
		return $this->tickRateTime;
	}

	public function setTickRate(int $tickRate){
		$this->tickRate = $tickRate;
	}

	public function initLevel(){
		$generator = $this->generator;
		$this->generatorInstance = new $generator($this->provider->getGeneratorOptions());
		$this->generatorInstance->init($this, new Random($this->getSeed()));

		$this->registerGenerator();
	}

	public function registerGenerator(){
		$size = $this->server->getScheduler()->getAsyncTaskPoolSize();
		for($i = 0; $i < $size; ++$i){
			$this->server->getScheduler()->scheduleAsyncTaskToWorker(new GeneratorRegisterTask($this,  $this->generatorInstance), $i);
		}
	}

	public function unregisterGenerator(){
		$size = $this->server->getScheduler()->getAsyncTaskPoolSize();
		for($i = 0; $i < $size; ++$i){
			$this->server->getScheduler()->scheduleAsyncTaskToWorker(new GeneratorUnregisterTask($this,  $this->generatorInstance), $i);
		}
	}

	public function getBlockMetadata() : BlockMetadataStore{
		return $this->blockMetadata;
	}

	public function getServer() : Server{
		return $this->server;
	}

	final public function getProvider() : LevelProvider{
		return $this->provider;
	}

	/**
	 * Returns the unique level identifier
	 */
	final public function getId() : int{
		return $this->levelId;
	}

	public function close(){

		if($this->getAutoSave()){
			$this->save();
		}

		foreach($this->chunks as $chunk){
			$this->unloadChunk($chunk->getX(), $chunk->getZ(), false);
		}

		$this->unregisterGenerator();

		$this->provider->close();
		$this->provider = null;
		$this->blockMetadata = null;
		$this->blockCache = [];
		$this->temporalPosition = null;
	}
	
	public function addSound(Sound $sound, array $players = null){
		$pk = $sound->encode();

		if($players === null){
			if($pk !== null){
				if(!is_array($pk)){
					$this->addChunkPacket($sound->x >> 4, $sound->z >> 4, $pk);
				}else{
					foreach($pk as $e){
						$this->addChunkPacket($sound->x >> 4, $sound->z >> 4, $e);
					}
				}
			}
		}else{
			if($pk !== null){
				if(!is_array($pk)){
					Server::broadcastPacket($players, $pk);
				}else{
					$this->server->batchPackets($players, $pk, false);
				}
			}	
		}
	}
	
	public function addParticle(Particle $particle, array $players = null){
		$pk = $particle->encode();

		if($players === null){
			if($pk !== null){
				if(!is_array($pk)){
					$this->addChunkPacket($particle->x >> 4, $particle->z >> 4, $pk);
				}else{
					foreach($pk as $e){
						$this->addChunkPacket($particle->x >> 4, $particle->z >> 4, $e);
					}
				}
			}
		}else{
			if($pk !== null){
				if(!is_array($pk)){
					Server::broadcastPacket($players, $pk);
				}else{
					$this->server->batchPackets($players, $pk, false);
				}
			}
		}
	}

	public function getAutoSave() : bool{
		return $this->autoSave;
	}

	public function setAutoSave(bool $value){
		$this->autoSave = $value;
	}

	/**
	 * Unloads the current level from memory safely
	 *
	 * @param bool $force default false, force unload of default level
	 *
	 * @return bool
	 */
	public function unload(bool $force = false) : bool{

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
	 * Gets the players being used in a specific chunk
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return Player[]
	 */
	public function getChunkPlayers(int $chunkX, int $chunkZ) : array{
		return isset($this->playerLoaders[$index = Level::chunkHash($chunkX, $chunkZ)]) ? $this->playerLoaders[$index] : [];
	}

	/**
	 * Gets the chunk loaders being used in a specific chunk
	 *
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return ChunkLoader[]
	 */
	public function getChunkLoaders(int $chunkX, int $chunkZ) : array{
		return isset($this->chunkLoaders[$index = Level::chunkHash($chunkX, $chunkZ)]) ? $this->chunkLoaders[$index] : [];
	}

	public function addChunkPacket(int $chunkX, int $chunkZ, DataPacket $packet){
		if(!isset($this->chunkPackets[$index = Level::chunkHash($chunkX, $chunkZ)])){
			$this->chunkPackets[$index] = [$packet];
		}else{
			$this->chunkPackets[$index][] = $packet;
		}
	}

	public function registerChunkLoader(ChunkLoader $loader, int $chunkX, int $chunkZ, bool $autoLoad = true){
		$hash = $loader->getLoaderId();

		if(!isset($this->chunkLoaders[$index = Level::chunkHash($chunkX, $chunkZ)])){
			$this->chunkLoaders[$index] = [];
			$this->playerLoaders[$index] = [];
		}elseif(isset($this->chunkLoaders[$index][$hash])){
			return;
		}

		$this->chunkLoaders[$index][$hash] = $loader;
		if($loader instanceof Player){
			$this->playerLoaders[$index][$hash] = $loader;
		}

		if(!isset($this->loaders[$hash])){
			$this->loaderCounter[$hash] = 1;
			$this->loaders[$hash] = $loader;
		}else{
			++$this->loaderCounter[$hash];
		}

		$this->cancelUnloadChunkRequest($chunkX, $chunkZ);

		if($autoLoad){
			$this->loadChunk($chunkX, $chunkZ);
		}
	}

	public function unregisterChunkLoader(ChunkLoader $loader, int $chunkX, int $chunkZ){
		if(isset($this->chunkLoaders[$index = Level::chunkHash($chunkX, $chunkZ)][$hash = $loader->getLoaderId()])){
			unset($this->chunkLoaders[$index][$hash]);
			unset($this->playerLoaders[$index][$hash]);
			if(count($this->chunkLoaders[$index]) === 0){
				unset($this->chunkLoaders[$index]);
				unset($this->playerLoaders[$index]);
				$this->unloadChunkRequest($chunkX, $chunkZ, true);
			}

			if(--$this->loaderCounter[$hash] === 0){
				unset($this->loaderCounter[$hash]);
				unset($this->loaders[$hash]);
			}
		}
	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function checkTime(){
		if($this->stopTime === true){
			return;
		}else{
			$this->time += 1;
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
	 */
	public function doTick(int $currentTick){

		$this->timings->doTick->startTiming();

		$this->checkTime();

		if(++$this->sendTimeTicker === 200){
			$this->sendTime();
			$this->sendTimeTicker = 0;
		}

		$this->unloadChunks();

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
		Timings::$tickTileEntityTimer->startTiming();
		//Update tiles that need update
		if(count($this->updateTiles) > 0){
			foreach($this->updateTiles as $id => $tile){
				if($tile->onUpdate() !== true){
					unset($this->updateTiles[$id]);
				}
			}
		}
		Timings::$tickTileEntityTimer->stopTiming();
		$this->timings->tileEntityTick->stopTiming();

		$this->timings->doTickTiles->startTiming();
		$this->tickChunks();
		$this->timings->doTickTiles->stopTiming();

		if(count($this->changedBlocks) > 0){
			if(count($this->players) > 0){
				foreach($this->changedBlocks as $index => $blocks){
					unset($this->chunkCache[$index]);
					Level::getXZ($index, $chunkX, $chunkZ);
					if(count($blocks) > 512){
						$chunk = $this->getChunk($chunkX, $chunkZ);
						foreach($this->getChunkPlayers($chunkX, $chunkZ) as $p){
							$p->onChunkChanged($chunk);
						}
					}else{
						$this->sendBlocks($this->getChunkPlayers($chunkX, $chunkZ), $blocks, UpdateBlockPacket::FLAG_ALL);
					}
				}
			}else{
				$this->chunkCache = [];
			}

			$this->changedBlocks = [];

		}

		$this->processChunkRequest();

		if($this->sleepTicks > 0 and --$this->sleepTicks <= 0){
			$this->checkSleep();
		}

		foreach($this->moveToSend as $index => $entry){
			Level::getXZ($index, $chunkX, $chunkZ);
			foreach($entry as $e) {
				$pk = new MoveEntityPacket();
				$pk->eid = $e[0];
				$pk->x = $e[1];
				$pk->y = $e[2];
				$pk->z = $e[3];
				$pk->yaw = $e[4];
				$pk->headYaw = $e[5];
				$pk->pitch = $e[6];
				$this->addChunkPacket($chunkX, $chunkZ, $pk);
			}
		}
		$this->moveToSend = [];

		foreach($this->motionToSend as $index => $entry){
			Level::getXZ($index, $chunkX, $chunkZ);
			$pk = new SetEntityMotionPacket();
			$pk->entities = $entry;
			$this->addChunkPacket($chunkX, $chunkZ, $pk);
		}
		$this->motionToSend = [];

		foreach($this->chunkPackets as $index => $entries){
			Level::getXZ($index, $chunkX, $chunkZ);
			$chunkPlayers = $this->getChunkPlayers($chunkX, $chunkZ);
			if(count($chunkPlayers) > 0){
				foreach($entries as $pk){
					Server::broadcastPacket($chunkPlayers, $pk);
				}
			}
		}

		$this->chunkPackets = [];

		$this->timings->doTick->stopTiming();
	}

	public function checkSleep(){
		if(count($this->players) === 0){
			return;
		}

		$resetTime = true;
		foreach($this->getPlayers() as $p){
			if(!$p->isSleeping()){
				$resetTime = false;
				break;
			}
		}

		if($resetTime){
			$time = $this->getTime() % Level::TIME_FULL;

			if($time >= Level::TIME_NIGHT and $time < Level::TIME_SUNRISE){
				$this->setTime($this->getTime() + Level::TIME_FULL - $time);

				foreach($this->getPlayers() as $p){
					$p->stopSleep();
				}
			}
		}
	}

	public function sendBlockExtraData(int $x, int $y, int $z, int $id, int $data, array $targets = null){
		$pk = new LevelEventPacket;
		$pk->evid = LevelEventPacket::EVENT_SET_DATA;
		$pk->x = $x + 0.5;
		$pk->y = $y + 0.5;
		$pk->z = $z + 0.5;
		$pk->data = ($data << 8) | $id;

		Server::broadcastPacket($targets === null ? $this->getChunkPlayers($x >> 4, $z >> 4) : $targets, $pk);
	}

	/**
	 * @param Player[] $target
	 * @param Block[]  $blocks
	 * @param int      $flags
	 * @param bool     $optimizeRebuilds
	 */
	public function sendBlocks(array $target, array $blocks, $flags = UpdateBlockPacket::FLAG_NONE, bool $optimizeRebuilds = false){
		if($optimizeRebuilds){
			$chunks = [];
			foreach($blocks as $b){
				$pk = new UpdateBlockPacket();
				if($b === null){
					continue;
				}

				$first = false;
				if(!isset($chunks[$index = Level::chunkHash($b->x >> 4, $b->z >> 4)])){
					$chunks[$index] = true;
					$first = true;
				}

				if($b instanceof Block){
					$pk->x = $b->x;
					$pk->z = $b->z;
					$pk->y = $b->y;
					$pk->blockId = $b->getId();
					$pk->blockData = $b->getDamage();
					$pk->flags = $first ? $flags : UpdateBlockPacket::FLAG_NONE;
				}else{
					$fullBlock = $this->getFullBlock($b->x, $b->y, $b->z);
					$pk->x = $b->x;
					$pk->z = $b->z;
					$pk->y = $b->y;
					$pk->blockId = $fullBlock >> 4;
					$pk->blockData = $fullBlock & 0xf;
					$pk->flags = $first ? $flags : UpdateBlockPacket::FLAG_NONE;
				}
				Server::broadcastPacket($target, $pk);
			}
		}else{
			foreach($blocks as $b){
				$pk = new UpdateBlockPacket();
				if($b === null){
					continue;
				}
				if($b instanceof Block){
					$pk->x = $b->x;
					$pk->z = $b->z;
					$pk->y = $b->y;
					$pk->blockId = $b->getId();
					$pk->blockData = $b->getDamage();
					$pk->flags = $flags;
				}else{
					$fullBlock = $this->getFullBlock($b->x, $b->y, $b->z);
					$pk->x = $b->x;
					$pk->z = $b->z;
					$pk->y = $b->y;
					$pk->blockId = $fullBlock >> 4;
					$pk->blockData = $fullBlock & 0xf;
					$pk->flags = $flags;
				}
				Server::broadcastPacket($target, $pk);
			}
		}
	}

	public function clearCache(bool $full = false){
		if($full){
			$this->chunkCache = [];
			$this->blockCache = [];
		}else{
			if(count($this->chunkCache) > 768){
				$this->chunkCache = [];
			}

			if(count($this->blockCache) > 2048){
				$this->blockCache = [];
			}

		}

	}

	public function clearChunkCache(int $chunkX, int $chunkZ){
		unset($this->chunkCache[Level::chunkHash($chunkX, $chunkZ)]);
	}

	private function tickChunks(){
		if($this->chunksPerTick <= 0 or count($this->loaders) === 0){
			$this->chunkTickList = [];
			return;
		}

		$chunksPerLoader = min(200, max(1, (int) ((($this->chunksPerTick - count($this->loaders)) / count($this->loaders)) + 0.5)));
		$randRange = 3 + $chunksPerLoader / 30;
		$randRange = (int) ($randRange > $this->chunkTickRadius ? $this->chunkTickRadius : $randRange);

		foreach($this->loaders as $loader){
			$chunkX = $loader->getX() >> 4;
			$chunkZ = $loader->getZ() >> 4;

			$index = Level::chunkHash($chunkX, $chunkZ);
			$existingLoaders = max(0, isset($this->chunkTickList[$index]) ? $this->chunkTickList[$index] : 0);
			$this->chunkTickList[$index] = $existingLoaders + 1;
			for($chunk = 0; $chunk < $chunksPerLoader; ++$chunk){
				$dx = mt_rand(-$randRange, $randRange);
				$dz = mt_rand(-$randRange, $randRange);
				$hash = Level::chunkHash($dx + $chunkX, $dz + $chunkZ);
				if(!isset($this->chunkTickList[$hash]) and isset($this->chunks[$hash])){
					$this->chunkTickList[$hash] = -1;
				}
			}
		}

		$blockTest = 0;

		foreach($this->chunkTickList as $index => $loaders){
			Level::getXZ($index, $chunkX, $chunkZ);



			if(!isset($this->chunks[$index]) or ($chunk = $this->getChunk($chunkX, $chunkZ, false)) === null){
				unset($this->chunkTickList[$index]);
				continue;
			}elseif($loaders <= 0){
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

	public function __debugInfo() : array{
		return [];
	}

	/**
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function save(bool $force = false){

		if(!$this->getAutoSave() and !$force){
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
		$pos = $pos->floor();
		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x, $pos->y - 1, $pos->z))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x, $pos->y + 1, $pos->z))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x - 1, $pos->y, $pos->z))));
		if(!$ev->isCancelled()){
			$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
		}

		$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($this->getBlock($this->temporalVector->setComponents($pos->x + 1, $pos->y, $pos->z))));
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
	public function scheduleUpdate(Vector3 $pos, int $delay){
		if(isset($this->updateQueueIndex[$index = Level::blockHash($pos->x, $pos->y, $pos->z)]) and $this->updateQueueIndex[$index] <= $delay){
			return;
		}
		$this->updateQueueIndex[$index] = $delay;
		$this->updateQueue->insert(new Vector3((int) $pos->x, (int) $pos->y, (int) $pos->z), (int) $delay + $this->server->getTick());
	}

	/**
	 * @param AxisAlignedBB $bb
	 * @param bool          $targetFirst
	 *
	 * @return Block[]
	 */
	public function getCollisionBlocks(AxisAlignedBB $bb, bool $targetFirst = false) : array{
		$minX = Math::floorFloat($bb->minX);
		$minY = Math::floorFloat($bb->minY);
		$minZ = Math::floorFloat($bb->minZ);
		$maxX = Math::ceilFloat($bb->maxX);
		$maxY = Math::ceilFloat($bb->maxY);
		$maxZ = Math::ceilFloat($bb->maxZ);
		
		$collides = [];

		if($targetFirst){
			for($z = $minZ; $z <= $maxZ; ++$z){
				for($x = $minX; $x <= $maxX; ++$x){
					for($y = $minY; $y <= $maxY; ++$y){
						$block = $this->getBlock($this->temporalVector->setComponents($x, $y, $z));
						if($block->getId() !== 0 and $block->collidesWithBB($bb)){
							return [$block];
						}
					}
				}
			}
		}else{
			for($z = $minZ; $z <= $maxZ; ++$z){
				for($x = $minX; $x <= $maxX; ++$x){
					for($y = $minY; $y <= $maxY; ++$y){
						$block = $this->getBlock($this->temporalVector->setComponents($x, $y, $z));
						if($block->getId() !== 0 and $block->collidesWithBB($bb)){
							$collides[] = $block;
						}
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
	public function isFullBlock(Vector3 $pos) : bool{
		if($pos instanceof Block){
			if($pos->isSolid()){
				return true;
			}
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
	public function getCollisionCubes(Entity $entity, AxisAlignedBB $bb, bool $entities = true) : array{
		$minX = Math::floorFloat($bb->minX);
		$minY = Math::floorFloat($bb->minY);
		$minZ = Math::floorFloat($bb->minZ);
		$maxX = Math::ceilFloat($bb->maxX);
		$maxY = Math::ceilFloat($bb->maxY);
		$maxZ = Math::ceilFloat($bb->maxZ);

		$collides = [];

		for($z = $minZ; $z <= $maxZ; ++$z){
			for($x = $minX; $x <= $maxX; ++$x){
				for($y = $minY; $y <= $maxY; ++$y){
					$block = $this->getBlock($this->temporalVector->setComponents($x, $y, $z));
					if(!$block->canPassThrough() and $block->collidesWithBB($bb)){
						$collides[] = $block->getBoundingBox();
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

	public function getFullLight(Vector3 $pos) : int{
		$chunk = $this->getChunk($pos->x >> 4, $pos->z >> 4, false);
		$level = 0;
		if($chunk !== null){
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
	public function getFullBlock(int $x, int $y, int $z) : int{
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
	public function getBlock(Vector3 $pos, $cached = true) : Block{
		$pos = $pos->floor();
		$index = Level::blockHash($pos->x, $pos->y, $pos->z);
		if($cached and isset($this->blockCache[$index])){
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

	public function updateBlockSkyLight(int $x, int $y, int $z){
		//TODO
	}

	public function updateBlockLight(int $x, int $y, int $z){
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

	private function computeRemoveBlockLight(int $x, int $y, int $z, int $currentLight, \SplQueue $queue, \SplQueue $spreadQueue, array &$visited, array &$spreadVisited){
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

	private function computeSpreadBlockLight(int $x, int $y, int $z, int $currentLight, \SplQueue $queue, array &$visited){
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
	public function setBlock(Vector3 $pos, Block $block, bool $direct = false, bool $update = true) : bool{
		$pos = $pos->floor();
		if($pos->y < 0 or $pos->y >= 128){
			return false;
		}

		if($this->getChunk($pos->x >> 4, $pos->z >> 4, true)->setBlock($pos->x & 0x0f, $pos->y & 0x7f, $pos->z & 0x0f, $block->getId(), $block->getDamage())){
			if(!($pos instanceof Position)){
				$pos = $this->temporalPosition->setComponents($pos->x, $pos->y, $pos->z);
			}

			$block->position($pos);
			unset($this->blockCache[Level::blockHash($pos->x, $pos->y, $pos->z)]);

			$index = Level::chunkHash($pos->x >> 4, $pos->z >> 4);

			if($direct === true){
				$this->sendBlocks($this->getChunkPlayers($pos->x >> 4, $pos->z >> 4), [$block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
				unset($this->chunkCache[$index]);
			}else{
				if(!isset($this->changedBlocks[$index])){
					$this->changedBlocks[$index] = [];
				}

				$this->changedBlocks[$index][Level::blockHash($block->x, $block->y, $block->z)] = clone $block;
			}

			foreach($this->getChunkLoaders($pos->x >> 4, $pos->z >> 4) as $loader){
				$loader->onBlockChanged($block);
			}

			if($update === true){
				$this->updateAllLight($block);

				$this->server->getPluginManager()->callEvent($ev = new BlockUpdateEvent($block));
				if(!$ev->isCancelled()){
					foreach($this->getNearbyEntities(new AxisAlignedBB($block->x - 1, $block->y - 1, $block->z - 1, $block->x + 1, $block->y + 1, $block->z + 1)) as $entity){
						$entity->scheduleUpdate();
					}
					$ev->getBlock()->onUpdate(self::BLOCK_UPDATE_NORMAL);
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
	public function dropItem(Vector3 $source, Item $item, Vector3 $motion = null, int $delay = 10){
		$motion = $motion === null ? new Vector3(lcg_value() * 0.2 - 0.1, 0.2, lcg_value() * 0.2 - 0.1) : $motion;
		$itemTag = NBT::putItemHelper($item);
		$itemTag->setName("Item");

		if($item->getId() > 0 and $item->getCount() > 0){
			$itemEntity = Entity::createEntity("Item", $this->getChunk($source->getX() >> 4, $source->getZ() >> 4, true), new CompoundTag("", [
				"Pos" => new ListTag("Pos", [
					new DoubleTag("", $source->getX()),
					new DoubleTag("", $source->getY()),
					new DoubleTag("", $source->getZ())
				]),

				"Motion" => new ListTag("Motion", [
					new DoubleTag("", $motion->x),
					new DoubleTag("", $motion->y),
					new DoubleTag("", $motion->z)
				]),
				"Rotation" => new ListTag("Rotation", [
					new FloatTag("", lcg_value() * 360),
					new FloatTag("", 0)
				]),
				"Health" => new ShortTag("Health", 5),
				"Item" => $itemTag,
				"PickupDelay" => new ShortTag("PickupDelay", $delay)
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
	 * @param bool    $createParticles
	 *
	 * @return bool
	 */
	public function useBreakOn(Vector3 $vector, Item &$item = null, Player $player = null, bool $createParticles = false) : bool{
		$target = $this->getBlock($vector);
		//TODO: Adventure mode checks

		if($item === null){
			$item = Item::get(Item::AIR, 0, 0);
		}

		if($player !== null){
			$ev = new BlockBreakEvent($player, $target, $item, $player->isCreative() ? true : false);

			if($player->isSurvival() and $item instanceof Item and !$target->isBreakable($item)){
				$ev->setCancelled();
			}elseif(!$player->isOp() and ($distance = $this->server->getSpawnRadius()) > -1){
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

			$breakTime = $target->getBreakTime($item);

			if($player->isCreative() and $breakTime > 0.15){
				$breakTime = 0.15;
			}

			if($player->hasEffect(Effect::SWIFTNESS)){
				$breakTime *= 1 - (0.2 * ($player->getEffect(Effect::SWIFTNESS)->getAmplifier() + 1));
			}

			if($player->hasEffect(Effect::MINING_FATIGUE)){
				$breakTime *= 1 + (0.3 * ($player->getEffect(Effect::MINING_FATIGUE)->getAmplifier() + 1));
			}

			$breakTime -= 0.05; //1 tick compensation

			if(!$ev->getInstaBreak() and ($player->lastBreak + $breakTime) > microtime(true)){
				return false;
			}

			$player->lastBreak = microtime(true);

			$drops = $ev->getDrops();

		}elseif($item !== null and !$target->isBreakable($item)){
			return false;
		}else{
			$drops = $target->getDrops($item); //Fixes tile entities being deleted before getting drops
			foreach($drops as $k => $i){
				$drops[$k] = Item::get($i[0], $i[1], $i[2]);
			}
		}

		$above = $this->getBlock(new Vector3($target->x, $target->y + 1, $target->z));
		if($above !== null){
			if($above->getId() === Item::FIRE){
				$this->setBlock($above, new Air(), true);
			}
		}

		$tag = $item->getNamedTagEntry("CanDestroy");
		if($tag instanceof ListTag){
			$canBreak = false;
			foreach($tag as $v){
				if($v instanceof StringTag){
					$entry = Item::fromString($v->getValue());
					if($entry->getId() > 0 and $entry->getBlock() !== null and $entry->getBlock()->getId() === $target->getId()){
						$canBreak = true;
						break;
					}
				}
			}

			if(!$canBreak){
				return false;
			}
		}

		if($createParticles){
			$players = $this->getChunkPlayers($target->x >> 4, $target->z >> 4);
			if($player !== null){
				unset($players[$player->getLoaderId()]);
			}

			$this->addParticle(new DestroyBlockParticle($target->add(0.5), $target), $players);
		}
		
		$target->onBreak($item);
		
		$tile = $this->getTile($target);
		if($tile !== null){
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

		if($item !== null){
			$item->useOn($target);
			if($item->isTool() and $item->getDamage() >= $item->getMaxDurability()){
				$item = Item::get(Item::AIR, 0, 0);
			}
		}

		if($player === null or $player->isSurvival()){
			foreach($drops as $drop){
				if($drop->getCount() > 0){
					$this->dropItem($vector->add(0.5, 0.5, 0.5), $drop);
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
	 * @return bool
	 */
	public function useItemOn(Vector3 $vector, Item &$item, int $face, float $fx = 0.0, float $fy = 0.0, float $fz = 0.0, Player $player = null) : bool{
		$target = $this->getBlock($vector);
		$block = $target->getSide($face);

		if($block->y > 127 or $block->y < 0){
			return false;
		}

		if($target->getId() === Item::AIR){
			return false;
		}

		if($player !== null){
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
				if(!$player->isSneaking() and $target->canBeActivated() === true and $target->onActivate($item, $player) === true){
					return true;
				}

				if(!$player->isSneaking() and $item->canBeActivated() and $item->onActivate($this, $player, $block, $target, $face, $fx, $fy, $fz)){
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

		if($item->canBePlaced()){
			$hand = $item->getBlock();
			$hand->position($block);
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

			if($player !== null){
				if(($diff = $player->getNextPosition()->subtract($player->getPosition())) and $diff->lengthSquared() > 0.00001){
					$bb = $player->getBoundingBox()->getOffsetBoundingBox($diff->x, $diff->y, $diff->z);
					if($hand->getBoundingBox()->intersectsWith($bb)){
						++$realCount;
					}
				}
			}

			if($realCount > 0){
				return false; //Entity in block
			}
		}

		$tag = $item->getNamedTagEntry("CanPlaceOn");
		if($tag instanceof ListTag){
			$canPlace = false;
			foreach($tag as $v){
				if($v instanceof StringTag){
					$entry = Item::fromString($v->getValue());
					if($entry->getId() > 0 and $entry->getBlock() !== null and $entry->getBlock()->getId() === $target->getId()){
						$canPlace = true;
						break;
					}
				}
			}

			if(!$canPlace){
				return false;
			}
		}


		if($player !== null){
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
	public function getEntity(int $entityId){
		return isset($this->entities[$entityId]) ? $this->entities[$entityId] : null;
	}

	/**
	 * Gets the list of all the entities in this level
	 *
	 * @return Entity[]
	 */
	public function getEntities() : array{
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
	public function getCollidingEntities(AxisAlignedBB $bb, Entity $entity = null) : array{
		$nearby = [];

		if($entity === null or $entity->canCollide){
			$minX = Math::floorFloat(($bb->minX - 2) / 16);
			$maxX = Math::ceilFloat(($bb->maxX + 2) / 16);
			$minZ = Math::floorFloat(($bb->minZ - 2) / 16);
			$maxZ = Math::ceilFloat(($bb->maxZ + 2) / 16);

			for($x = $minX; $x <= $maxX; ++$x){
				for($z = $minZ; $z <= $maxZ; ++$z){
					foreach($this->getChunkEntities($x, $z) as $ent){
						if(($entity === null or ($ent !== $entity and $entity->canCollideWith($ent))) and $ent->boundingBox->intersectsWith($bb)){
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
	public function getNearbyEntities(AxisAlignedBB $bb, Entity $entity = null) : array{
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
	public function getTiles() : array{
		return $this->tiles;
	}

	/**
	 * @param $tileId
	 *
	 * @return Tile
	 */
	public function getTileById(int $tileId){
		return isset($this->tiles[$tileId]) ? $this->tiles[$tileId] : null;
	}

	/**
	 * Returns a list of the players in this level
	 *
	 * @return Player[]
	 */
	public function getPlayers() : array{
		return $this->players;
	}

	/**
	 * @return ChunkLoader[]
	 */
	public function getLoaders() : array{
		return $this->loaders;
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
	public function getChunkEntities($X, $Z) : array{
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
	public function getChunkTiles($X, $Z) : array{
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
	public function getBlockIdAt(int $x, int $y, int $z) : int{
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
	public function setBlockIdAt(int $x, int $y, int $z, int $id){
		unset($this->blockCache[Level::blockHash($x, $y, $z)]);
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockId($x & 0x0f, $y & 0x7f, $z & 0x0f, $id & 0xff);

		if(!isset($this->changedBlocks[$index = Level::chunkHash($x >> 4, $z >> 4)])){
			$this->changedBlocks[$index] = [];
		}
		$this->changedBlocks[$index][Level::blockHash($x, $y, $z)] = $v = new Vector3($x, $y, $z);
		foreach($this->getChunkLoaders($x >> 4, $z >> 4) as $loader){
			$loader->onBlockChanged($v);
		}
	}

	/**
	 * Gets the raw block extra data
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 16-bit
	 */
	public function getBlockExtraDataAt(int $x, int $y, int $z) : int{
		return $this->getChunk($x >> 4, $z >> 4, true)->getBlockExtraData($x & 0x0f, $y & 0x7f, $z & 0x0f);
	}

	/**
	 * Sets the raw block metadata.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @param int $id
	 * @param int $data
	 */
	public function setBlockExtraDataAt(int $x, int $y, int $z, int $id, int $data){
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockExtraData($x & 0x0f, $y & 0x7f, $z & 0x0f, ($data << 8) | $id);

		$this->sendBlockExtraData($x, $y, $z, $id, $data);
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
	public function getBlockDataAt(int $x, int $y, int $z) : int{
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
	public function setBlockDataAt(int $x, int $y, int $z, int $data){
		unset($this->blockCache[Level::blockHash($x, $y, $z)]);
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockData($x & 0x0f, $y & 0x7f, $z & 0x0f, $data & 0x0f);

		if(!isset($this->changedBlocks[$index = Level::chunkHash($x >> 4, $z >> 4)])){
			$this->changedBlocks[$index] = [];
		}
		$this->changedBlocks[$index][Level::blockHash($x, $y, $z)] = $v = new Vector3($x, $y, $z);
		foreach($this->getChunkLoaders($x >> 4, $z >> 4) as $loader){
			$loader->onBlockChanged($v);
		}
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
	public function getBlockSkyLightAt(int $x, int $y, int $z) : int{
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
	public function setBlockSkyLightAt(int $x, int $y, int $z, int $level){
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
	public function getBlockLightAt(int $x, int $y, int $z) : int{
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
	public function setBlockLightAt(int $x, int $y, int $z, int $level){
		$this->getChunk($x >> 4, $z >> 4, true)->setBlockLight($x & 0x0f, $y & 0x7f, $z & 0x0f, $level & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getBiomeId(int $x, int $z) : int{
		return $this->getChunk($x >> 4, $z >> 4, true)->getBiomeId($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int[]
	 */
	public function getBiomeColor(int $x, int $z) : array{
		return $this->getChunk($x >> 4, $z >> 4, true)->getBiomeColor($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return int
	 */
	public function getHeightMap(int $x, int $z) : int{
		return $this->getChunk($x >> 4, $z >> 4, true)->getHeightMap($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $biomeId
	 */
	public function setBiomeId(int $x, int $z, int $biomeId){
		$this->getChunk($x >> 4, $z >> 4, true)->setBiomeId($x & 0x0f, $z & 0x0f, $biomeId);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $R
	 * @param int $G
	 * @param int $B
	 */
	public function setBiomeColor(int $x, int $z, int $R, int $G, int $B){
		$this->getChunk($x >> 4, $z >> 4, true)->setBiomeColor($x & 0x0f, $z & 0x0f, $R, $G, $B);
	}

	/**
	 * @param int $x
	 * @param int $z
	 * @param int $value
	 */
	public function setHeightMap(int $x, int $z, int $value){
		$this->getChunk($x >> 4, $z >> 4, true)->setHeightMap($x & 0x0f, $z & 0x0f, $value);
	}

	/**
	 * @return FullChunk[]|Chunk[]
	 */
	public function getChunks() : array{
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
	public function getChunk(int $x, int $z, bool $create = false){
		if(isset($this->chunks[$index = Level::chunkHash($x, $z)])){
			return $this->chunks[$index];
		}elseif($this->loadChunk($x, $z, $create)){
			return $this->chunks[$index];
		}

		return null;
	}

	public function generateChunkCallback(int $x, int $z, FullChunk $chunk){
		Timings::$generationCallbackTimer->startTiming();
		if(isset($this->chunkPopulationQueue[$index = Level::chunkHash($x, $z)])){
			$oldChunk = $this->getChunk($x, $z, false);
			for($xx = -1; $xx <= 1; ++$xx){
				for($zz = -1; $zz <= 1; ++$zz){
					unset($this->chunkPopulationLock[Level::chunkHash($x + $xx, $z + $zz)]);
				}
			}
			unset($this->chunkPopulationQueue[$index]);
			$chunk->setProvider($this->provider);
			$this->setChunk($x, $z, $chunk, false);
			$chunk = $this->getChunk($x, $z, false);
			if($chunk !== null and ($oldChunk === null or $oldChunk->isPopulated() === false) and $chunk->isPopulated() and $chunk->getProvider() !== null){
				$this->server->getPluginManager()->callEvent(new ChunkPopulateEvent($chunk));

				foreach($this->getChunkLoaders($x, $z) as $loader){
					$loader->onChunkPopulated($chunk);
				}
			}
		}elseif(isset($this->chunkGenerationQueue[$index]) or isset($this->chunkPopulationLock[$index])){
			unset($this->chunkGenerationQueue[$index]);
			unset($this->chunkPopulationLock[$index]);
			$chunk->setProvider($this->provider);
			$this->setChunk($x, $z, $chunk, false);
		}else{
			$chunk->setProvider($this->provider);
			$this->setChunk($x, $z, $chunk, false);
		}
		Timings::$generationCallbackTimer->stopTiming();
	}

	/**
	 * @param int       $chunkX
	 * @param int       $chunkZ
	 * @param FullChunk $chunk
	 * @param bool      $unload
	 */
	public function setChunk(int $chunkX, int $chunkZ, FullChunk $chunk = null, bool $unload = true){
		if($chunk === null){
			return;
		}
		$index = Level::chunkHash($chunkX, $chunkZ);
		$oldChunk = $this->getChunk($chunkX, $chunkZ, false);
		if($unload and $oldChunk !== null){
			$this->unloadChunk($chunkX, $chunkZ, false, false);

			$this->provider->setChunk($chunkX, $chunkZ, $chunk);
			$this->chunks[$index] = $chunk;
		}else{
			$oldEntities = $oldChunk !== null ? $oldChunk->getEntities() : [];
			$oldTiles = $oldChunk !== null ? $oldChunk->getTiles() : [];

			$this->provider->setChunk($chunkX, $chunkZ, $chunk);
			$this->chunks[$index] = $chunk;

			foreach($oldEntities as $entity){
				$chunk->addEntity($entity);
				$entity->chunk = $chunk;
			}

			foreach($oldTiles as $tile){
				$chunk->addTile($tile);
				$tile->chunk = $chunk;
			}
		}

		unset($this->chunkCache[$index]);
		$chunk->setChanged();

		if(!$this->isChunkInUse($chunkX, $chunkZ)){
			$this->unloadChunkRequest($chunkX, $chunkZ);
		}else{
			foreach($this->getChunkLoaders($chunkX, $chunkZ) as $loader){
				$loader->onChunkChanged($chunk);
			}
		}
	}

	/**
	 * Gets the highest block Y value at a specific $x and $z
	 *
	 * @param int $x
	 * @param int $z
	 *
	 * @return int 0-127
	 */
	public function getHighestBlockAt(int $x, int $z) : int{
		return $this->getChunk($x >> 4, $z >> 4, true)->getHighestBlockAt($x & 0x0f, $z & 0x0f);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkLoaded(int $x, int $z) : bool{
		return isset($this->chunks[Level::chunkHash($x, $z)]) or $this->provider->isChunkLoaded($x, $z);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkGenerated(int $x, int $z) : bool{
		$chunk = $this->getChunk($x, $z);
		return $chunk !== null ? $chunk->isGenerated() : false;
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkPopulated(int $x, int $z) : bool{
		$chunk = $this->getChunk($x, $z);
		return $chunk !== null ? $chunk->isPopulated() : false;
	}

	/**
	 * Returns a Position pointing to the spawn
	 *
	 * @return Position
	 */
	public function getSpawnLocation() : Position{
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

	public function requestChunk(int $x, int $z, Player $player){
		$index = Level::chunkHash($x, $z);
		if(!isset($this->chunkSendQueue[$index])){
			$this->chunkSendQueue[$index] = [];
		}

		$this->chunkSendQueue[$index][$player->getLoaderId()] = $player;
	}

	private function sendChunkFromCache(int $x, int $z){
		if(isset($this->chunkSendTasks[$index = Level::chunkHash($x, $z)])){
			foreach($this->chunkSendQueue[$index] as $player){
				/** @var Player $player */
				if($player->isConnected() and isset($player->usedChunks[$index])){
					$player->sendChunk($x, $z, $this->chunkCache[$index]);
				}
			}
			unset($this->chunkSendQueue[$index]);
			unset($this->chunkSendTasks[$index]);
		}
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
				if(isset($this->chunkCache[$index])){
					$this->sendChunkFromCache($x, $z);
					continue;
				}
				$this->timings->syncChunkSendPrepareTimer->startTiming();
				$task = $this->provider->requestChunkTask($x, $z);
				if($task !== null){
					$this->server->getScheduler()->scheduleAsyncTask($task);
				}
				$this->timings->syncChunkSendPrepareTimer->stopTiming();
			}

			$this->timings->syncChunkSendTimer->stopTiming();
		}
	}

	public function chunkRequestCallback(int $x, int $z, string $payload, int $ordering = FullChunkDataPacket::ORDER_COLUMNS){
		$this->timings->syncChunkSendTimer->startTiming();

		$index = Level::chunkHash($x, $z);

		if(!isset($this->chunkCache[$index]) and $this->cacheChunks and $this->server->getMemoryManager()->canUseChunkCache()){
			$this->chunkCache[$index] = Player::getChunkCacheFromData($x, $z, $payload, $ordering);
			$this->sendChunkFromCache($x, $z);
			$this->timings->syncChunkSendTimer->stopTiming();
			return;
		}

		if(isset($this->chunkSendTasks[$index])){
			foreach($this->chunkSendQueue[$index] as $player){
				/** @var Player $player */
				if($player->isConnected() and isset($player->usedChunks[$index])){
					$player->sendChunk($x, $z, $payload, $ordering);
				}
			}
			unset($this->chunkSendQueue[$index]);
			unset($this->chunkSendTasks[$index]);
		}
		$this->timings->syncChunkSendTimer->stopTiming();
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
			$this->checkSleep();
		}else{
			$entity->close();
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
		$this->clearChunkCache($tile->getX() >> 4, $tile->getZ() >> 4);
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
		$this->clearChunkCache($tile->getX() >> 4, $tile->getZ() >> 4);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return bool
	 */
	public function isChunkInUse(int $x, int $z) : bool{
		return isset($this->chunkLoaders[$index = Level::chunkHash($x, $z)]) and count($this->chunkLoaders[$index]) > 0;
	}

	/**
	 * @param int  $x
	 * @param int  $z
	 * @param bool $generate
	 *
	 * @return bool
	 *
	 * @throws \InvalidStateException
	 */
	public function loadChunk(int $x, int $z, bool $generate = true) : bool{
		if(isset($this->chunks[$index = Level::chunkHash($x, $z)])){
			return true;
		}

		$this->timings->syncChunkLoadTimer->startTiming();

		$this->cancelUnloadChunkRequest($x, $z);

		$chunk = $this->provider->getChunk($x, $z, $generate);
		if($chunk === null){
			if($generate){
				throw new \InvalidStateException("Could not create new Chunk");
			}
			return false;
		}

		$this->chunks[$index] = $chunk;
		$chunk->initChunk();

		if($chunk->getProvider() !== null){
			$this->server->getPluginManager()->callEvent(new ChunkLoadEvent($chunk, !$chunk->isGenerated()));
		}else{
			$this->unloadChunk($x, $z, false);
			$this->timings->syncChunkLoadTimer->stopTiming();
			return false;
		}

		if(!$chunk->isLightPopulated() and $chunk->isPopulated() and $this->getServer()->getProperty("chunk-ticking.light-updates", false)){
			$this->getServer()->getScheduler()->scheduleAsyncTask(new LightPopulationTask($this, $chunk));
		}

		if($this->isChunkInUse($x, $z)){
			foreach($this->getChunkLoaders($x, $z) as $loader){
				$loader->onChunkLoaded($chunk);
			}
		}else{
			$this->unloadChunkRequest($x, $z);
		}

		$this->timings->syncChunkLoadTimer->stopTiming();

		return true;
	}

	private function queueUnloadChunk(int $x, int $z){
		$this->unloadQueue[$index = Level::chunkHash($x, $z)] = microtime(true);
		unset($this->chunkTickList[$index]);
	}

	public function unloadChunkRequest(int $x, int $z, bool $safe = true){
		if(($safe === true and $this->isChunkInUse($x, $z)) or $this->isSpawnChunk($x, $z)){
			return false;
		}

		$this->queueUnloadChunk($x, $z);

		return true;
	}

	public function cancelUnloadChunkRequest(int $x, int $z){
		unset($this->unloadQueue[Level::chunkHash($x, $z)]);
	}

	public function unloadChunk(int $x, int $z, bool $safe = true, bool $trySave = true) : bool{
		if(($safe === true and $this->isChunkInUse($x, $z))){
			return false;
		}

		if(!$this->isChunkLoaded($x, $z)){
			return true;
		}

		$this->timings->doChunkUnload->startTiming();

		$index = Level::chunkHash($x, $z);

		$chunk = $this->getChunk($x, $z);

		if($chunk !== null and $chunk->getProvider() !== null){
			$this->server->getPluginManager()->callEvent($ev = new ChunkUnloadEvent($chunk));
			if($ev->isCancelled()){
				$this->timings->doChunkUnload->stopTiming();
				return false;
			}
		}

		try{
			if($chunk !== null){
				if($trySave and $this->getAutoSave()){
					$entities = 0;
					foreach($chunk->getEntities() as $e){
						if($e instanceof Player){
							continue;
						}
						++$entities;
					}

					if($chunk->hasChanged() or count($chunk->getTiles()) > 0 or $entities > 0){
						$this->provider->setChunk($x, $z, $chunk);
						$this->provider->saveChunk($x, $z);
					}
				}

				foreach($this->getChunkLoaders($x, $z) as $loader){
					$loader->onChunkUnloaded($chunk);
				}
			}
			$this->provider->unloadChunk($x, $z, $safe);
		}catch(\Throwable $e){
			$logger = $this->server->getLogger();
			$logger->error($this->server->getLanguage()->translateString("pocketmine.level.chunkUnloadError", [$e->getMessage()]));
			$logger->logException($e);
		}

		unset($this->chunks[$index]);
		unset($this->chunkTickList[$index]);
		unset($this->chunkCache[$index]);

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
	public function isSpawnChunk(int $X, int $Z) : bool{
		$spawnX = $this->provider->getSpawn()->getX() >> 4;
		$spawnZ = $this->provider->getSpawn()->getZ() >> 4;

		return abs($X - $spawnX) <= 1 and abs($Z - $spawnZ) <= 1;
	}

	/**
	 * @param Vector3 $spawn default null
	 *
	 * @return bool|Position
	 */
	public function getSafeSpawn($spawn = null){
		if(!($spawn instanceof Vector3) or $spawn->y < 1){
			$spawn = $this->getSpawnLocation();
		}
		if($spawn instanceof Vector3){
			$v = $spawn->floor();
			$chunk = $this->getChunk($v->x >> 4, $v->z >> 4, false);
			$x = $v->x & 0x0f;
			$z = $v->z & 0x0f;
			if($chunk !== null){
				$y = (int) min(126, $v->y);
				$wasAir = ($chunk->getBlockId($x, $y - 1, $z) === 0);
				for(; $y > 0; --$y){
					$b = $chunk->getFullBlock($x, $y, $z);
					$block = Block::get($b >> 4, $b & 0x0f);
					if($this->isFullBlock($block)){
						if($wasAir){
							$y++;
							break;
						}
					}else{
						$wasAir = true;
					}
				}

				for(; $y >= 0 and $y < 128; ++$y){
					$b = $chunk->getFullBlock($x, $y + 1, $z);
					$block = Block::get($b >> 4, $b & 0x0f);
					if(!$this->isFullBlock($block)){
						$b = $chunk->getFullBlock($x, $y, $z);
						$block = Block::get($b >> 4, $b & 0x0f);
						if(!$this->isFullBlock($block)){
							return new Position($spawn->x, $y === (int) $spawn->y ? $spawn->y : $y, $spawn->z, $this);
						}
					}else{
						++$y;
					}
				}

				$v->y = $y;
			}

			return new Position($spawn->x, $v->y, $spawn->z, $this);
		}

		return false;
	}

	/**
	 * Gets the current time
	 *
	 * @return int
	 */
	public function getTime() : int{
		return (int) $this->time;
	}

	/**
	 * Returns the Level name
	 *
	 * @return string
	 */
	public function getName() : string{
		return $this->provider->getName();
	}

	/**
	 * Returns the Level folder name
	 *
	 * @return string
	 */
	public function getFolderName() : string{
		return $this->folderName;
	}

	/**
	 * Sets the current time on the level
	 *
	 * @param int $time
	 */
	public function setTime(int $time){
		$this->time = $time;
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
	 * @return int|string int value of seed, or the string numeric representation of a long in 32-bit systems
	 */
	public function getSeed(){
		return $this->provider->getSeed();
	}

	/**
	 * Sets the seed for the level
	 *
	 * @param int $seed
	 */
	public function setSeed(int $seed){
		$this->provider->setSeed($seed);
	}


	public function populateChunk(int $x, int $z, bool $force = false) : bool{
		if(isset($this->chunkPopulationQueue[$index = Level::chunkHash($x, $z)]) or (count($this->chunkPopulationQueue) >= $this->chunkPopulationQueueSize and !$force)){
			return false;
		}

		$chunk = $this->getChunk($x, $z, true);
		if(!$chunk->isPopulated()){
			Timings::$populationTimer->startTiming();
			$populate = true;
			for($xx = -1; $xx <= 1; ++$xx){
				for($zz = -1; $zz <= 1; ++$zz){
					if(isset($this->chunkPopulationLock[Level::chunkHash($x + $xx, $z + $zz)])){
						$populate = false;
						break;
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
					$task = new PopulationTask($this, $chunk);
					$this->server->getScheduler()->scheduleAsyncTask($task);
				}
			}

			Timings::$populationTimer->stopTiming();
			return false;
		}

		return true;
	}

	public function generateChunk(int $x, int $z, bool $force = false){
		if(count($this->chunkGenerationQueue) >= $this->chunkGenerationQueueSize and !$force){
			return;
		}

		if(!isset($this->chunkGenerationQueue[$index = Level::chunkHash($x, $z)])){
			Timings::$generationTimer->startTiming();
			$this->chunkGenerationQueue[$index] = true;
			$task = new GenerationTask($this, $this->getChunk($x, $z, true));
			$this->server->getScheduler()->scheduleAsyncTask($task);
			Timings::$generationTimer->stopTiming();
		}
	}

	public function regenerateChunk(int $x, int $z){
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

	public function unloadChunks(bool $force = false){
		if(count($this->unloadQueue) > 0){
			$maxUnload = 96;
			$now = microtime(true);
			foreach($this->unloadQueue as $index => $time){
				Level::getXZ($index, $X, $Z);

				if(!$force){
					if($maxUnload <= 0){
						break;
					}elseif($time > ($now - 30)){
						continue;
					}
				}

				//If the chunk can't be unloaded, it stays on the queue
				if($this->unloadChunk($X, $Z, true)){
					unset($this->unloadQueue[$index]);
					--$maxUnload;
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

	public function addEntityMotion(int $chunkX, int $chunkZ, int $entityId, float $x, float $y, float $z){
		if(!isset($this->motionToSend[$index = Level::chunkHash($chunkX, $chunkZ)])){
			$this->motionToSend[$index] = [];
		}
		$this->motionToSend[$index][$entityId] = [$entityId, $x, $y, $z];
	}

	public function addEntityMovement(int $chunkX, int $chunkZ, int $entityId, float $x, float $y, float $z, float $yaw, float $pitch, $headYaw = null){
		if(!isset($this->moveToSend[$index = Level::chunkHash($chunkX, $chunkZ)])){
			$this->moveToSend[$index] = [];
		}
		$this->moveToSend[$index][$entityId] = [$entityId, $x, $y, $z, $yaw, $headYaw === null ? $yaw : $headYaw, $pitch];
	}
}
