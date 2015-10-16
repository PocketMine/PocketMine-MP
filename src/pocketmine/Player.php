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

namespace pocketmine;

use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\entity\Arrow;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Item as DroppedItem;
use pocketmine\entity\Living;
use pocketmine\entity\Projectile;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\TextContainer;
use pocketmine\event\Timings;
use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\BigShapedRecipe;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\inventory\CraftingTransactionGroup;
use pocketmine\inventory\FurnaceInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\inventory\SimpleTransactionGroup;

use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\level\format\LevelProvider;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\Long;
use pocketmine\nbt\tag\Short;
use pocketmine\nbt\tag\String;
use pocketmine\network\Network;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\StrangePacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\SetDifficultyPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetHealthPacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\SourceInterface;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\CallbackTask;
use pocketmine\tile\Sign;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

use raklib\Binary;

/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, InventoryHolder, IPlayer{

	const SURVIVAL = 0;
	const CREATIVE = 1;
	const ADVENTURE = 2;
	const SPECTATOR = 3;
	const VIEW = Player::SPECTATOR;

	const SURVIVAL_SLOTS = 36;
	const CREATIVE_SLOTS = 112;

	/** @var SourceInterface */
	protected $interface;

	public $spawned = false;
	public $loggedIn = false;
	public $dead = false;
	public $gamemode;
	public $lastBreak;

	protected $windowCnt = 2;
	/** @var \SplObjectStorage<Inventory> */
	protected $windows;
	/** @var Inventory[] */
	protected $windowIndex = [];

	protected $messageCounter = 2;

	protected $sendIndex = 0;

	private $clientSecret;

	protected $moveToSend;
	protected $motionToSend;

	/** @var Vector3 */
	public $speed = null;

	public $blocked = false;
	public $achievements = [];
	public $lastCorrect;
	/** @var SimpleTransactionGroup */
	protected $currentTransaction = null;
	public $craftingType = 0; //0 = 2x2 crafting, 1 = 3x3 crafting, 2 = stonecutter

	protected $isCrafting = false;

	/**
	 * @deprecated
	 * @var array
	 */
	public $loginData = [];

	public $creationTime = 0;

	protected $randomClientId;

	protected $lastMovement = 0;
	/** @var Vector3 */
	protected $forceMovement = null;
	/** @var Vector3 */
	protected $teleportPosition = null;
	protected $connected = true;
	protected $ip;
	protected $removeFormat = true;
	protected $port;
	protected $username;
	protected $iusername;
	protected $displayName;
	protected $startAction = -1;
	/** @var Vector3 */
	protected $sleeping = null;
	protected $clientID = null;

	protected $stepHeight = 0.6;

	public $usedChunks = [];
	protected $chunkLoadCount = 0;
	protected $loadQueue = [];
	protected $nextChunkOrderRun = 5;

	/** @var Player[] */
	protected $hiddenPlayers = [];

	/** @var Vector3 */
	protected $newPosition;

	private $checkMovement;

	protected $viewDistance;
	protected $chunksPerTick;
    protected $spawnThreshold;
	/** @var null|Position */
	private $spawnPosition = null;

	protected $inAirTicks = 0;
	protected $lastSpeedTick = 0;
	protected $speedTicks = 0;
	protected $highSpeedTicks = 0;

	protected $autoJump = true;

	protected $allowFlight = false;

	private $needACK = [];

	private $batchedPackets = [];


	/**
	 * @var \pocketmine\scheduler\TaskHandler[]
	 */
	protected $tasks = [];

	/** @var PermissibleBase */
	private $perm = null;

	public function getLeaveMessage(){
		return "";
	}

	/**
	 * This might disappear in the future.
	 * Please use getUniqueId() instead (IP + clientId + name combo, in the future it'll change to real UUID for online auth)
	 *
	 * @deprecated
	 *
	 */
	public function getClientId(){
		return $this->randomClientId;
	}

	public function getClientSecret(){
		return $this->clientSecretId;
	}

	public function isBanned(){
		return $this->server->getNameBans()->isBanned(strtolower($this->getName()));
	}

	public function setBanned($value){
		if($value === true){
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
			$this->kick("You have been banned");
		}else{
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	public function isWhitelisted(){
		return $this->server->isWhitelisted(strtolower($this->getName()));
	}

	public function setWhitelisted($value){
		if($value === true){
			$this->server->addWhitelist(strtolower($this->getName()));
		}else{
			$this->server->removeWhitelist(strtolower($this->getName()));
		}
	}

	public function getPlayer(){
		return $this;
	}

	public function getFirstPlayed(){
		return $this->namedtag instanceof Compound ? $this->namedtag["firstPlayed"] : null;
	}

	public function getLastPlayed(){
		return $this->namedtag instanceof Compound ? $this->namedtag["lastPlayed"] : null;
	}

	public function hasPlayedBefore(){
		return $this->namedtag instanceof Compound;
	}

	public function setAllowFlight($value){
		$this->allowFlight = (bool) $value;
		$this->sendSettings();
	}

	public function getAllowFlight(){
		return $this->allowFlight;
	}

	public function setAutoJump($value){
		$this->autoJump = $value;
		$this->sendSettings();
	}

	public function hasAutoJump(){
		return $this->autoJump;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		if($this->spawned === true and $player->spawned === true and $this->dead !== true and $player->dead !== true and $player->getLevel() === $this->level and $player->canSee($this)){
			parent::spawnTo($player);
		}
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @return bool
	 */
	public function getRemoveFormat(){
		return $this->removeFormat;
	}

	/**
	 * @param bool $remove
	 */
	public function setRemoveFormat($remove = true){
		$this->removeFormat = (bool) $remove;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function canSee(Player $player){
		return !isset($this->hiddenPlayers[$player->getName()]);
	}

	/**
	 * @param Player $player
	 */
	public function hidePlayer(Player $player){
		if($player === $this){
			return;
		}
		$this->hiddenPlayers[$player->getName()] = $player;
		$player->despawnFrom($this);
	}

	/**
	 * @param Player $player
	 */
	public function showPlayer(Player $player){
		if($player === $this){
			return;
		}
		unset($this->hiddenPlayers[$player->getName()]);
		if($player->isOnline()){
			$player->spawnTo($this);
		}
	}

	public function canCollideWith(Entity $entity){
		return false;
	}

	public function resetFallDistance(){
		parent::resetFallDistance();
		$this->inAirTicks = 0;
	}

	/**
	 * @return bool
	 */
	public function isOnline(){
		return $this->connected === true and $this->loggedIn === true;
	}

	/**
	 * @return bool
	 */
	public function isOp(){
		return $this->server->isOp($this->getName());
	}

	/**
	 * @param bool $value
	 */
	public function setOp($value){
		if($value === $this->isOp()){
			return;
		}

		if($value === true){
			$this->server->addOp($this->getName());
		}else{
			$this->server->removeOp($this->getName());
		}

		$this->recalculatePermissions();
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function isPermissionSet($name){
		return $this->perm->isPermissionSet($name);
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function hasPermission($name){
		return $this->perm->hasPermission($name);
	}

	/**
	 * @param Plugin $plugin
	 * @param string $name
	 * @param bool   $value
	 *
	 * @return permission\PermissionAttachment
	 */
	public function addAttachment(Plugin $plugin, $name = null, $value = null){
		return $this->perm->addAttachment($plugin, $name, $value);
	}

	/**
	 * @param PermissionAttachment $attachment
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		$this->perm->removeAttachment($attachment);
	}

	public function recalculatePermissions(){
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

		if($this->perm === null){
			return;
		}

		$this->perm->recalculatePermissions();

		if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}
	}

	/**
	 * @return permission\PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions(){
		return $this->perm->getEffectivePermissions();
	}


	/**
	 * @param SourceInterface $interface
	 * @param null            $clientID
	 * @param string          $ip
	 * @param integer         $port
	 */
	public function __construct(SourceInterface $interface, $clientID, $ip, $port){
		$this->interface = $interface;
		$this->windows = new \SplObjectStorage();
		$this->perm = new PermissibleBase($this);
		$this->namedtag = new Compound();
		$this->server = Server::getInstance();
		$this->lastBreak = PHP_INT_MAX;
		$this->ip = $ip;
		$this->port = $port;
		$this->clientID = $clientID;
		$this->chunksPerTick = (int) $this->server->getProperty("chunk-sending.per-tick", 4);
        $this->spawnThreshold = 72;
		$this->spawnPosition = null;
		$this->gamemode = $this->server->getGamemode();
		$this->setLevel($this->server->getDefaultLevel(), true);
		$this->viewDistance = $this->server->getViewDistance();
		$this->newPosition = new Vector3(0, 0, 0);
		$this->checkMovement = (bool) $this->server->getAdvancedProperty("main.check-movement", true);
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->motionToSend = new SetEntityMotionPacket();
		$this->moveToSend = new MoveEntityPacket();

		$this->uuid = null;
		$this->rawUUID = null;

		$this->creationTime = microtime(true);
	}

	/**
	 * @param string $achievementId
	 */
	public function removeAchievement($achievementId){
		if($this->hasAchievement($achievementId)){
			$this->achievements[$achievementId] = false;
		}
	}

	/**
	 * @param string $achievementId
	 *
	 * @return bool
	 */
	public function hasAchievement($achievementId){
		if(!isset(Achievement::$list[$achievementId]) or !isset($this->achievements)){
			$this->achievements = [];

			return false;
		}

		if(!isset($this->achievements[$achievementId]) or $this->achievements[$achievementId] == false){
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function isConnected(){
		return $this->connected === true;
	}

	/**
	 * Gets the "friendly" name to display of this player to use in the chat.
	 *
	 * @return string
	 */
	public function getDisplayName(){
		return $this->displayName;
	}

	/**
	 * @param string $name
	 */
	public function setDisplayName($name){
		$this->displayName = $name;
		if($this->spawned){
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->isSkinSlim(), $this->getSkinData());
		}
	}

	/**
	 * @return string
	 */
	public function getNameTag(){
		return $this->nameTag;
	}

	public function setSkin($str, $isSlim = false){
		parent::setSkin($str, $isSlim);
		if($this->spawned === true){
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $isSlim, $str);
		}
	}

	/**
	 * Gets the player IP address
	 *
	 * @return string
	 */
	public function getAddress(){
		return $this->ip;
	}

	/**
	 * @return int
	 */
	public function getPort(){
		return $this->port;
	}

	/**
	 * @return bool
	 */
	public function isSleeping(){
		return $this->sleeping !== null;
	}

	public function unloadChunk($x, $z){
		$index = Level::chunkHash($x, $z);
		if(isset($this->usedChunks[$index])){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
		}
		$this->level->freeChunk($x, $z, $this);
		unset($this->loadQueue[$index]);
	}

	/**
	 * @return Position
	 */
	public function getSpawn(){
		if($this->spawnPosition instanceof Position and $this->spawnPosition->getLevel() instanceof Level){
			return $this->spawnPosition;
		}else{
			$level = $this->server->getDefaultLevel();

			return $level->getSafeSpawn();
		}
	}

	public function sendChunk($x, $z, $payload, $ordering = FullChunkDataPacket::ORDER_COLUMNS){
		if($this->connected === false){
			return;
		}

		$this->usedChunks[Level::chunkHash($x, $z)] = true;
		$this->chunkLoadCount++;

		$pk = new FullChunkDataPacket();
		$pk->chunkX = $x;
		$pk->chunkZ = $z;
		$pk->order = $ordering;
		$pk->data = $payload;
		$pk->encode();

		$bt = new BatchPacket();

		$str = $pk->buffer;

		$bt->payload = zlib_encode($str, ZLIB_ENCODING_DEFLATE, 7);
		$bt->encode();
		$bt->isEncoded = true;

		$this->dataPacket($bt);

		if($this->spawned){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this and !$entity->closed and !$entity->dead){
					$entity->spawnTo($this);
				}
			}
		}
	}

	protected function sendNextChunk(){
		if($this->connected === false){
			return;
		}

		$count = 0;
		foreach($this->loadQueue as $index => $distance){
			if($count >= $this->chunksPerTick){
				break;
			}

			$X = null;
			$Z = null;
			Level::getXZ($index, $X, $Z);

			++$count;

			unset($this->loadQueue[$index]);
			$this->usedChunks[$index] = false;

			$this->level->useChunk($X, $Z, $this);
			$this->level->requestChunk($X, $Z, $this, LevelProvider::ORDER_ZXY);
		}

		if($this->chunkLoadCount >= $this->spawnThreshold and $this->spawned === false){
			$this->spawned = true;

			$this->sendSettings();
			$this->sendPotionEffects($this);
			$this->sendData($this);
			$this->inventory->sendContents($this);
			$this->inventory->sendArmorContents($this);

			$pk = new SetTimePacket();
			$pk->time = $this->level->getTime();
			$pk->started = $this->level->stopTime == false;
			$this->dataPacket($pk);

			$pos = $this->level->getSafeSpawn($this);

			$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $pos));

			$pos = $ev->getRespawnPosition();
			$pk = new RespawnPacket();
			$pk->x = $pos->x;
			$pk->y = $pos->y;
			$pk->z = $pos->z;
			$this->dataPacket($pk);

			$pk = new PlayStatusPacket();
			$pk->status = PlayStatusPacket::PLAYER_SPAWN;
			$this->dataPacket($pk);
			$this->noDamageTicks = 60;

			foreach($this->usedChunks as $index => $c){
				Level::getXZ($index, $chunkX, $chunkZ);
				foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
					if($entity !== $this and !$entity->closed and !$entity->dead){
						$entity->spawnTo($this);
					}
				}
			}

			$this->teleport($pos);

			$this->spawnToAll();

			if($this->getHealth() <= 0){
				$pk = new RespawnPacket();
				$pos = $this->getSpawn();
				$pk->x = $pos->x;
				$pk->y = $pos->y;
				$pk->z = $pos->z;
				$this->dataPacket($pk);
			}

			$this->server->getPluginManager()->callEvent($ev = new PlayerJoinEvent($this, ""));
		}
	}


	protected function orderChunks(){
		if($this->connected === false){
			return false;
		}
		$this->nextChunkOrderRun = 200;
		$radiusSquared = $this->viewDistance;
		$radius = ceil(sqrt($radiusSquared));
		$side = ceil($radius / 2);
		$newOrder = [];
		$lastChunk = $this->usedChunks;
		$currentQueue = [];
		$centerX = $this->x >> 4;
		$centerZ = $this->z >> 4;
		for($X = -$side; $X <= $side; ++$X){
			for($Z = -$side; $Z <= $side; ++$Z){
				$chunkX = $X + $centerX;
				$chunkZ = $Z + $centerZ;
				if(!isset($this->usedChunks[$index = Level::chunkHash($chunkX, $chunkZ)])){
					$newOrder[$index] = abs($X) + abs($Z);
				}else{
					$currentQueue[$index] = abs($X) + abs($Z);
				}
			}
		}
		asort($newOrder);
		asort($currentQueue);
		$limit = $this->viewDistance;
		foreach($currentQueue as $index => $distance){
			if($limit-- <= 0){
				break;
			}
			unset($lastChunk[$index]);
		}
		foreach($lastChunk as $index => $Yndex){
			$X = null;
			$Z = null;
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}
		$loadedChunks = count($this->usedChunks);
		if((count($newOrder) + $loadedChunks) > $this->viewDistance){
			$count = $loadedChunks;
			$this->loadQueue = [];
			foreach($newOrder as $k => $distance){
				if(++$count > $this->viewDistance){
					break;
				}
				$this->loadQueue[$k] = $distance;
			}
		}else{
			$this->loadQueue = $newOrder;
		}
		return true;
	}

	/**
	 * Batch a Data packet into the channel list to send at the end of the tick
	 *
	 * @param DataPacket $packet
	 *
	 * @return bool
	 */
	public function batchDataPacket(DataPacket $packet){
		if($this->connected === false){
			return false;
		}
		$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
		if($ev->isCancelled()){
			return false;
		}

		$this->batchedPackets[] = clone $packet;

		return true;
	}

	/**
	 * Sends an ordered DataPacket to the send buffer
	 *
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 *
	 * @return int|bool
	 */
	public function dataPacket(DataPacket $packet, $needACK = false){
		if($this->connected === false){
			return false;
		}

		$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
		if($ev->isCancelled()){
			return false;
		}

		$identifier = $this->interface->putPacket($this, $packet, $needACK, false);

		if($needACK and $identifier !== null){
			$this->needACK[$identifier] = false;

			return $identifier;
		}

		return true;
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 *
	 * @return bool|int
	 */
	public function directDataPacket(DataPacket $packet, $needACK = false){
		if($this->connected === false){
			return false;
		}
		$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
		if($ev->isCancelled()){
			return false;
		}

		$identifier = $this->interface->putPacket($this, $packet, $needACK, true);

		if($needACK and $identifier !== null){
			$this->needACK[$identifier] = false;

			return $identifier;
		}

		return true;
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return boolean
	 */
	public function sleepOn(Vector3 $pos){
		foreach($this->level->getNearbyEntities($this->boundingBox->grow(2, 1, 2), $this) as $p){
			if($p instanceof Player){
				if($p->sleeping !== null and $pos->distance($p->sleeping) <= 0.1){
					return false;
				}
			}
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerBedEnterEvent($this, $this->level->getBlock($pos)));
		if($ev->isCancelled()){
			return false;
		}

		$this->sleeping = clone $pos;

		$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [$pos->x, $pos->y, $pos->z]);
		$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, true);

		$this->setSpawn($pos);
		$this->tasks[] = $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "checkSleep"]), 60);


		return true;
	}

	/**
	 * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a Position object
	 *
	 * @param Vector3|Position $pos
	 */
	public function setSpawn(Vector3 $pos){
		if(!($pos instanceof Position)){
			$level = $this->level;
		}else{
			$level = $pos->getLevel();
		}
		$this->spawnPosition = new Position($pos->x, $pos->y, $pos->z, $level);
		$pk = new SetSpawnPositionPacket();
		$pk->x = (int) $this->spawnPosition->x;
		$pk->y = (int) $this->spawnPosition->y;
		$pk->z = (int) $this->spawnPosition->z;
		$this->dataPacket($pk);
	}

	public function stopSleep(){
		if($this->sleeping instanceof Vector3){
			$this->server->getPluginManager()->callEvent($ev = new PlayerBedLeaveEvent($this, $this->level->getBlock($this->sleeping)));

			$this->sleeping = null;
			$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false);
			$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0]);

			$this->level->sleepTicks = 0;

			$pk = new AnimatePacket();
			$pk->eid = 0;
			$pk->action = 3; //Wake up
			$this->dataPacket($pk);
		}

	}

	/**
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 */
	public function checkSleep(){
		if($this->sleeping instanceof Vector3){
			//TODO: Move to Level

			$time = $this->level->getTime() % Level::TIME_FULL;

			if($time >= Level::TIME_NIGHT and $time < Level::TIME_SUNRISE){
				foreach($this->level->getPlayers() as $p){
					if($p->sleeping === null){
						return;
					}
				}

				$this->level->setTime($this->level->getTime() + Level::TIME_FULL - $time);

				foreach($this->level->getPlayers() as $p){
					$p->stopSleep();
				}
			}
		}
	}

	/**
	 * @param string $achievementId
	 *
	 * @return bool
	 */
	public function awardAchievement($achievementId){
		if(isset(Achievement::$list[$achievementId]) and !$this->hasAchievement($achievementId)){
			foreach(Achievement::$list[$achievementId]["requires"] as $requerimentId){
				if(!$this->hasAchievement($requerimentId)){
					return false;
				}
			}
			$this->server->getPluginManager()->callEvent($ev = new PlayerAchievementAwardedEvent($this, $achievementId));
			if(!$ev->isCancelled()){
				$this->achievements[$achievementId] = true;
				Achievement::broadcast($this, $achievementId);

				return true;
			}else{
				return false;
			}
		}

		return false;
	}

	/**
	 * @return int
	 */
	public function getGamemode(){
		return $this->gamemode;
	}

	/**
	 * Sets the gamemode, and if needed, kicks the Player.
	 *
	 * @param int $gm
	 *
	 * @return bool
	 */
	public function setGamemode($gm){
		if($gm < 0 or $gm > 3 or $this->gamemode === $gm){
			return false;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerGameModeChangeEvent($this, (int) $gm));
		if($ev->isCancelled()){
			return false;
		}


		$this->gamemode = $gm;

		$this->allowFlight = $this->isCreative();

		if($this->isSpectator()){
			$this->despawnFromAll();
		}else{
			$this->spawnToAll();
		}

		$this->namedtag->playerGameType = new Int("playerGameType", $this->gamemode);

		$spawnPosition = $this->getSpawn();

		$pk = new StartGamePacket();
		$pk->seed = -1;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->spawnX = (int) $spawnPosition->x;
		$pk->spawnY = (int) $spawnPosition->y;
		$pk->spawnZ = (int) $spawnPosition->z;
		$pk->generator = 1; //0 old, 1 infinite, 2 flat
		$pk->gamemode = $this->gamemode & 0x01;
		$pk->eid = 0;
		$this->dataPacket($pk);
		$this->sendSettings();

		if($this->gamemode === Player::SPECTATOR){
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			$this->dataPacket($pk);
		}else{
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			foreach(Item::getCreativeItems() as $item){
				$pk->slots[] = clone $item;
			}
			$this->dataPacket($pk);
		}

		$this->inventory->sendContents($this);
		$this->inventory->sendContents($this->getViewers());
		$this->inventory->sendHeldItem($this->hasSpawned);

		return true;
	}

	/**
	 * Sends all the option flags
	 */
	public function sendSettings(){
		/*
		 bit mask | flag name
		0x00000001 world_inmutable
		0x00000002 no_pvp
		0x00000004 no_pvm
		0x00000008 no_mvp
		0x00000010 static_time
		0x00000020 nametags_visible
		0x00000040 auto_jump
		0x00000080 allow_fly
		0x00000100 noclip
		0x00000200 ?
		0x00000400 ?
		0x00000800 ?
		0x00001000 ?
		0x00002000 ?
		0x00004000 ?
		0x00008000 ?
		0x00010000 ?
		0x00020000 ?
		0x00040000 ?
		0x00080000 ?
		0x00100000 ?
		0x00200000 ?
		0x00400000 ?
		0x00800000 ?
		0x01000000 ?
		0x02000000 ?
		0x04000000 ?
		0x08000000 ?
		0x10000000 ?
		0x20000000 ?
		0x40000000 ?
		0x80000000 ?
		*/
		$flags = 0;
		if($this->isAdventure()){
			$flags |= 0x01; //Do not allow placing/breaking blocks, adventure mode
		}

		/*if($nametags !== false){
			$flags |= 0x20; //Show Nametags
		}*/

		if($this->autoJump){
			$flags |= 0x40;
		}

		if($this->allowFlight){
			$flags |= 0x80;
		}

		if($this->isSpectator()){
			$flags |= 0x100;
		}

		$pk = new AdventureSettingsPacket();
		$pk->flags = $flags;
		$this->dataPacket($pk);
	}

	public function isSurvival(){
		return ($this->gamemode & 0x01) === 0;
	}

	public function isCreative(){
		return ($this->gamemode & 0x01) > 0;
	}

	public function isSpectator(){
		return $this->gamemode === 3;
	}

	public function isAdventure(){
		return ($this->gamemode & 0x02) > 0;
	}

	public function getDrops(){
		if(!$this->isCreative()){
			return parent::getDrops();
		}

		return [];
	}

	public function addEntityMotion($entityId, $x, $y, $z){
		$this->motionToSend->entities[$entityId] = [$entityId, $x, $y, $z];
	}

	public function addEntityMovement($entityId, $x, $y, $z, $yaw, $pitch, $headYaw = null){
		$this->moveToSend->entities[$entityId] = [$entityId, $x, $y, $z, $yaw, $headYaw === null ? $yaw : $headYaw, $pitch];
	}

	public function setDataProperty($id, $type, $value){
		if(parent::setDataProperty($id, $type, $value)){
			$this->sendData([$this], [$id => $this->dataProperties[$id]]);
			return true;
		}

		return false;
	}

	protected function processMovement($currentTick) {
		if($this->dead or !$this->spawned or !($this->newPosition instanceof Vector3)) {
			$diff = ($currentTick - $this->lastSpeedTick);
			if($diff >= 10) {
				$this->speed = new Vector3(0, 0, 0);
			} elseif($diff > 5 and $this->speedTicks < 20) {
				++$this->speedTicks;
			}

			return;
		}

		$distanceSquared = $this->newPosition->distanceSquared($this);

		$revert = false;

		if($distanceSquared > 100) {
			$revert = true;
		} else {
			if($this->chunk === null or !$this->chunk->isGenerated()) {
				$chunk = $this->level->getChunk($this->newPosition->x >> 4, $this->newPosition->z >> 4);
				if(!($chunk instanceof FullChunk) or !$chunk->isGenerated()) {
					$revert = true;
					$this->nextChunkOrderRun = 0;
				} else {
					if($this->chunk instanceof FullChunk) {
						$this->chunk->removeEntity($this);
					}
					$this->chunk = $chunk;
				}
			}
		}

		if(!$revert and $distanceSquared != 0) {
			$dx = $this->newPosition->x - $this->x;
			$dy = $this->newPosition->y - $this->y;
			$dz = $this->newPosition->z - $this->z;

			$this->fastMove($dx, $dy, $dz);

			$diffX = $this->x - $this->newPosition->x;
			$diffY = $this->y - $this->newPosition->y;
			$diffZ = $this->z - $this->newPosition->z;

			$yS = 0.5 + $this->ySize;
			if($diffY > -0.5 or $diffY < 0.5) {
				$diffY = 0;
			}

			$diff = $diffX ** 2 + $diffY ** 2 + $diffZ ** 2;

			if($this->isSurvival()) {
				if(!$revert and !$this->isSleeping()) {
					if($diff > 0.0625 and $this->checkMovement) {
						$revert = true;
						$this->server->getLogger()->warning($this->getName() . " moved wrongly!");
					} elseif($diff > 0 or !$this->checkMovement) {
						$this->x = $this->newPosition->x;
						$this->y = $this->newPosition->y;
						$this->z = $this->newPosition->z;
						$radius = $this->width / 2;
						$this->boundingBox->setBounds($this->x - $radius, $this->y, $this->z - $radius, $this->x + $radius, $this->y + $this->height, $this->z + $radius);
					}
				}
			} elseif($diff > 0) {
				$this->x = $this->newPosition->x;
				$this->y = $this->newPosition->y;
				$this->z = $this->newPosition->z;
				$radius = $this->width / 2;
				$this->boundingBox->setBounds($this->x - $radius, $this->y + $this->ySize, $this->z - $radius, $this->x + $radius, $this->y + $this->height + $this->ySize, $this->z + $radius);
			}
		}

		$from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
		$to = $this->getLocation();

		$delta = pow($this->lastX - $to->x, 2) + pow($this->lastY - $to->y, 2) + pow($this->lastZ - $to->z, 2);
		$deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

		if(!$revert and ($delta > (1 / 16) or $deltaAngle > 10)){

			$isFirst = ($this->lastX === null or $this->lastY === null or $this->lastZ === null);

			$this->lastX = $to->x;
			$this->lastY = $to->y;
			$this->lastZ = $to->z;

			$this->lastYaw = $to->yaw;
			$this->lastPitch = $to->pitch;

			if(!$isFirst){
				$ev = new PlayerMoveEvent($this, $from, $to);

				$this->server->getPluginManager()->callEvent($ev);

				if(!($revert = $ev->isCancelled())) { //Yes, this is intended
					if($to->distanceSquared($ev->getTo()) > 0.01) { //If plugins modify the destination
						$this->teleport($ev->getTo());
					} else {
						foreach($this->hasSpawned as $player) {
							$player->addEntityMovement($this->id, $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw);
						}
					}
				}
			}

			$ticks = min(20, $currentTick - $this->lastSpeedTick + 0.5);
			if($this->speedTicks > 0) {
				$ticks += $this->speedTicks;
			}
			$this->speed = $from->subtract($to)->divide($ticks);
			$this->lastSpeedTick = $currentTick;
		} elseif($distanceSquared == 0) {
			$this->speed = new Vector3(0, 0, 0);
			$this->lastSpeedTick = $currentTick;
		}

		if($this->speedTicks > 0) {
			--$this->speedTicks;
		}

		if($revert) {

			$this->lastX = $from->x;
			$this->lastY = $from->y;
			$this->lastZ = $from->z;

			$this->lastYaw = $from->yaw;
			$this->lastPitch = $from->pitch;

			$pk = new MoveEntityPacket();
			$pk->eid = 0;
			$pk->x = $from->x;
			$pk->y = $from->y + $this->getEyeHeight();
			$pk->z = $from->z;
			$pk->bodyYaw = $from->yaw;
			$pk->pitch = $from->pitch;
			$pk->yaw = $from->yaw;
			$pk->teleport = true;
			$this->directDataPacket($pk);
			$this->forceMovement = new Vector3($from->x, $from->y, $from->z);
		}else{
			$this->forceMovement = null;
			if($distanceSquared != 0 and $this->nextChunkOrderRun > 20){
				$this->nextChunkOrderRun = 20;
			}
		}

		$this->newPosition = null;
	}

	public function setMotion(Vector3 $mot){
		if(parent::setMotion($mot)){
			if($this->chunk !== null){
				$this->level->addEntityMotion($this->chunk->getX(), $this->chunk->getZ(), $this->getId(), $this->motionX, $this->motionY, $this->motionZ);
				$pk = new SetEntityMotionPacket();
				$pk->entities[] = [0, $mot->x, $mot->y, $mot->z];
				$this->dataPacket($pk);
			}

			if($this->motionY > 0){
				$this->startAirTicks = (-(log($this->gravity / ($this->gravity + $this->drag * $this->motionY))) / $this->drag) * 2 + 5;
			}

			return true;
		}
		return false;
	}

	public function onUpdate($currentTick){
		if(!$this->loggedIn){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if($tickDiff <= 0){
			return true;
		}

		$this->messageCounter = 2;

		$this->lastUpdate = $currentTick;

		if($this->dead === true and $this->spawned){
			++$this->deadTicks;
			if($this->deadTicks >= 10){
				$this->despawnFromAll();
			}
			return $this->deadTicks < 10;
		}

		$this->timings->startTiming();

		$this->lastUpdate = $currentTick;

		if($this->spawned){
			$this->processMovement($currentTick);

			$this->entityBaseTick(1);

			if($this->speed and $this->isSurvival()){
				$speed = sqrt($this->speed->x ** 2 + $this->speed->z ** 2);
				if($speed > 0.45){
					$this->highSpeedTicks += $speed > 3 ? 2 : 1;
					if($this->highSpeedTicks > 40 and !$this->server->getAllowFlight()){
						$this->kick("Flying is not enabled on this server");
						return false;
					}elseif($this->highSpeedTicks >= 10 and $this->highSpeedTicks % 4 === 0){
						$this->forceMovement = $this->getPosition();
						$this->speed = null;
					}
				}elseif($this->highSpeedTicks > 0){
					if($speed < 22){
						$this->highSpeedTicks = 0;
					}else{
						$this->highSpeedTicks--;
					}
				}
			}

			if($this->onGround){
				$this->inAirTicks = 0;
			}else{
				if($this->inAirTicks > 10 and $this->isSurvival() and !$this->isSleeping()){
					$expectedVelocity = (-$this->gravity) / $this->drag - ((-$this->gravity) / $this->drag) * exp(-$this->drag * ($this->inAirTicks - 2));
					$diff = sqrt(abs($this->speed->y - $expectedVelocity));

					if($diff > 0.6 and $expectedVelocity < $this->speed->y and !$this->server->getAllowFlight()){
						if($this->inAirTicks < 100){
							$this->setMotion(new Vector3(0, $expectedVelocity, 0));
						}else{
							$this->kick("Flying is not enabled on this server");
							return false;
						}
					}
				}

				++$this->inAirTicks;
			}

			foreach($this->level->getNearbyEntities($this->boundingBox->grow(1, 0.5, 1), $this) as $entity){
				if(($currentTick - $entity->lastUpdate) > 1){
					$entity->scheduleUpdate();
				}

				if($entity instanceof Arrow and $entity->hadCollision){
					if($entity->dead !== true){
						$item = Item::get(Item::ARROW, 0, 1);
						if($this->isSurvival() and !$this->inventory->canAddItem($item)){
							continue;
						}

						$this->server->getPluginManager()->callEvent($ev = new InventoryPickupArrowEvent($this->inventory, $entity));
						if($ev->isCancelled()){
							continue;
						}

						$pk = new TakeItemEntityPacket();
						$pk->eid = $this->getId();
						$pk->target = $entity->getId();
						Server::broadcastPacket($entity->getViewers(), $pk);

						$pk = new TakeItemEntityPacket();
						$pk->eid = 0;
						$pk->target = $entity->getId();
						$this->dataPacket($pk);

						$this->inventory->addItem(clone $item);
						$entity->kill();
					}
				}elseif($entity instanceof DroppedItem){
					if($entity->dead !== true and $entity->getPickupDelay() <= 0){
						$item = $entity->getItem();

						if($item instanceof Item){
							if($this->isSurvival() and !$this->inventory->canAddItem($item)){
								continue;
							}

							$this->server->getPluginManager()->callEvent($ev = new InventoryPickupItemEvent($this->inventory, $entity));
							if($ev->isCancelled()){
								continue;
							}

							switch($item->getId()){
								case Item::WOOD:
									$this->awardAchievement("mineWood");
									break;
								case Item::DIAMOND:
									$this->awardAchievement("diamond");
									break;
							}

							$pk = new TakeItemEntityPacket();
							$pk->eid = $this->getId();
							$pk->target = $entity->getId();
							Server::broadcastPacket($entity->getViewers(), $pk);

							$pk = new TakeItemEntityPacket();
							$pk->eid = 0;
							$pk->target = $entity->getId();
							$this->dataPacket($pk);

							$this->inventory->addItem(clone $item);
							$entity->kill();
						}
					}
				}
			}
		}

		if($this->nextChunkOrderRun-- <= 0 or $this->chunk === null){
			$this->orderChunks();
		}

		if(count($this->loadQueue) > 0 or !$this->spawned){
			$this->sendNextChunk();
		}

		if(count($this->moveToSend->entities) > 0){
			$this->dataPacket($this->moveToSend);
			$this->moveToSend->entities = [];
			$this->moveToSend->isEncoded = false;
		}


		if(count($this->motionToSend->entities) > 0){
			$this->dataPacket($this->motionToSend);
			$this->motionToSend->entities = [];
			$this->motionToSend->isEncoded = false;
		}

		if(count($this->batchedPackets) > 0){
			foreach($this->batchedPackets as $packet){
				$this->server->batchPackets([$this], [$packet], false);
			}
			$this->batchedPackets = [];
		}

		$this->lastUpdate = $currentTick;

		$this->timings->stopTiming();

		return true;
	}

	/**
	 * Handles a Minecraft packet
	 * TODO: Separate all of this in handlers
	 *
	 * WARNING: Do not use this, it's only for internal use.
	 * Changes to this function won't be recorded on the version.
	 *
	 * @param DataPacket $packet
	 */
	public function handleDataPacket(DataPacket $packet){
		if($this->connected === false){
			return;
		}

		if($packet->pid() === ProtocolInfo::BATCH_PACKET){
			/** @var BatchPacket $packet */
			$this->server->getNetwork()->processBatch($packet, $this);
			return;
		}

		$this->server->getPluginManager()->callEvent($ev = new DataPacketReceiveEvent($this, $packet));
		if($ev->isCancelled()){
			return;
		}

		switch($packet->pid()){
			case ProtocolInfo::LOGIN_PACKET:
				/*
				 * A/N: Not going to implement any session code until it actually does stuff.
				 * Single line functions are obnoxious to follow.
				 */

				if($this->loggedIn === true){
					break;
				}

				$this->username = TextFormat::clean($packet->username);
				$this->displayName = $this->username;
				$this->setNameTag($this->username);
				$this->iusername = strtolower($this->username);
				$this->randomClientId = $packet->clientId;
				$this->loginData = ["clientId" => $packet->clientId, "loginData" => null];

				$this->uuid = $packet->clientUUID;
				$this->rawUUID = $this->uuid->toBinary();
				$this->clientSecret = $packet->clientSecret;

				if(count($this->server->getOnlinePlayers()) > $this->server->getMaxPlayers()){
					$pk = new StrangePacket();
					$pk->address = gethostbyname("sg.lbsg.net");
					$pk->port = 19132;
					$pk->encode();
					$this->dataPacket($pk);
					return;
				}

				if($packet->protocol1 !== ProtocolInfo::CURRENT_PROTOCOL){
					$message = "";
					if($packet->protocol1 < ProtocolInfo::CURRENT_PROTOCOL){
						$message = "Please update Minecraft PE to join.";

						$pk = new PlayStatusPacket();
						$pk->status = PlayStatusPacket::LOGIN_FAILED_CLIENT;
						$this->dataPacket($pk);
					}else{
						$message = "Please use an older version of Minecraft PE.";

						$pk = new PlayStatusPacket();
						$pk->status = PlayStatusPacket::LOGIN_FAILED_SERVER;
						$this->dataPacket($pk);
					}
					$this->close("", $message, false);

					return;
				}

				if(strpos($packet->username, "\x00") !== false or preg_match('#^[a-zA-Z0-9_]{3,16}$#', $packet->username) == 0 or $this->username === "" or $this->iusername === "rcon" or $this->iusername === "console" or strlen($packet->username) > 16 or strlen($packet->username) < 3){
					$this->close("", "Please choose a valid username.");

					return;
				}

				if(strlen($packet->skin) < 64 * 32 * 4){
					$this->close("", "Invalid skin.", false);
					return;
				}

				$this->setSkin($packet->skin, $packet->slim);

				$this->server->getPluginManager()->callEvent($ev = new PlayerPreLoginEvent($this, "Plugin reason"));
				if($ev->isCancelled()){
					$this->close("", $ev->getKickMessage());

					return;
				}

				if(!$this->server->isWhitelisted(strtolower($this->getName()))){
					$this->close(TextFormat::YELLOW . $this->username . " has left the game", "Server is private.");

					return;
				}elseif($this->server->getNameBans()->isBanned(strtolower($this->getName())) or $this->server->getIPBans()->isBanned($this->getAddress())){
					$this->close(TextFormat::YELLOW . $this->username . " has left the game", "You have been banned.");

					return;
				}

				if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
					$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
				}
				if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
					$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
				}

				foreach($this->server->getOnlinePlayers() as $p){
					if($p !== $this and strtolower($p->getName()) === strtolower($this->getName())){
						if($p->kick("You connected from somewhere else.") === false){
							$this->close(TextFormat::YELLOW . $this->getName() . " has left the game", "You connected from somewhere else.");

							return;
						}else{
							return;
						}
					}
				}

				$nbt = $this->server->getOfflinePlayerData($this->username);
				if(!isset($nbt->NameTag)){
					$nbt->NameTag = new String("NameTag", $this->username);
				}else{
					$nbt["NameTag"] = $this->username;
				}
				$this->gamemode = $nbt["playerGameType"] & 0x03;
				if($this->server->getForceGamemode()){
					$this->gamemode = $this->server->getGamemode();
					$nbt->playerGameType = new Int("playerGameType", $this->gamemode);
				}

				$this->allowFlight = $this->isCreative();


				if(($level = $this->server->getLevelByName($nbt["Level"])) === null){
					$this->setLevel($this->server->getDefaultLevel(), true);
					$nbt["Level"] = $this->level->getName();
					$nbt["Pos"][0] = $this->level->getSpawnLocation()->x;
					$nbt["Pos"][1] = $this->level->getSpawnLocation()->y;
					$nbt["Pos"][2] = $this->level->getSpawnLocation()->z;
				}else{
					$this->setLevel($level, true);
				}

				if(!($nbt instanceof Compound)){
					$this->close(TextFormat::YELLOW . $this->username . " has left the game", "Corrupt joining data, check your connection.");

					return;
				}

				$this->achievements = [];

				/** @var Byte $achievement */
				foreach($nbt->Achievements as $achievement){
					$this->achievements[$achievement->getName()] = $achievement->getValue() > 0 ? true : false;
				}

				$nbt->lastPlayed = new Long("lastPlayed", floor(microtime(true) * 1000));
				parent::__construct($this->level->getChunk($nbt["Pos"][0] >> 4, $nbt["Pos"][2] >> 4, true), $nbt);
				$this->loggedIn = true;
				$this->server->addOnlinePlayer($this);

				$this->server->getPluginManager()->callEvent($ev = new PlayerLoginEvent($this, "Plugin reason"));
				if($ev->isCancelled()){
					$this->close(TextFormat::YELLOW . $this->username . " has left the game", $ev->getKickMessage());

					return;
				}

				if($this->isCreative()){
					$this->inventory->setHeldItemSlot(0);
				}else{
					$this->inventory->setHeldItemSlot($this->inventory->getHotbarSlotIndex(0));
				}

				$pk = new PlayStatusPacket();
				$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
				$this->dataPacket($pk);

				$this->server->sendFullPlayerListData($this);
				$this->server->sendRecipeList($this);

				$this->uuid = $packet->clientUUID;
				$this->rawUUID = $this->uuid->toBinary();
				$this->clientSecret = $packet->clientSecret;

				if($this->spawnPosition === null and isset($this->namedtag->SpawnLevel) and ($level = $this->server->getLevelByName($this->namedtag["SpawnLevel"])) instanceof Level){
					$this->spawnPosition = new Position($this->namedtag["SpawnX"], $this->namedtag["SpawnY"], $this->namedtag["SpawnZ"], $level);
				}

				$spawnPosition = $this->getSpawn();

				$pk = new StartGamePacket();
				$pk->seed = -1;
				$pk->x = $this->x;
				$pk->y = $this->y;
				$pk->z = $this->z;
				$pk->spawnX = (int) $spawnPosition->x;
				$pk->spawnY = (int) $spawnPosition->y;
				$pk->spawnZ = (int) $spawnPosition->z;
				$pk->generator = 1; //0 old, 1 infinite, 2 flat
				$pk->gamemode = $this->gamemode & 0x01;
				$pk->eid = 0; //Always use EntityID as zero for the actual player
				$this->dataPacket($pk);

				$pk = new SetTimePacket();
				$pk->time = $this->level->getTime();
				$pk->started = $this->level->stopTime == false;
				$this->dataPacket($pk);

				$pk = new SetSpawnPositionPacket();
				$pk->x = (int) $spawnPosition->x;
				$pk->y = (int) $spawnPosition->y;
				$pk->z = (int) $spawnPosition->z;
				$this->dataPacket($pk);

				$pk = new SetHealthPacket();
				$pk->health = $this->getHealth();
				$this->dataPacket($pk);
				if($this->getHealth() <= 0){
					$this->dead = true;
				}

				$pk = new SetDifficultyPacket();
				$pk->difficulty = $this->server->getDifficulty();
				$this->dataPacket($pk);

				$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . TextFormat::AQUA . $this->ip . " connected");

				if($this->gamemode === Player::SPECTATOR){
					$pk = new ContainerSetContentPacket();
					$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
					$this->dataPacket($pk);
				}else{
					$pk = new ContainerSetContentPacket();
					$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
					foreach(Item::getCreativeItems() as $item){
						$pk->slots[] = clone $item;
					}
					$this->dataPacket($pk);
				}

				$this->orderChunks();
				$this->sendNextChunk();
				break;
			case ProtocolInfo::MOVE_PLAYER_PACKET:

				$newPos = new Vector3($packet->x, $packet->y - $this->getEyeHeight(), $packet->z);

				$revert = false;
				if($this->dead === true or $this->spawned !== true){
					$revert = true;
					$this->forceMovement = new Vector3($this->x, $this->y, $this->z);
				}

				if($this->forceMovement instanceof Vector3 and (($dist = $newPos->distanceSquared($this->forceMovement)) > 0.04 or $revert)){
					$pk = new MovePlayerPacket();
					$pk->eid = $this->getId();
					$pk->x = $this->forceMovement->x;
					$pk->y = $this->forceMovement->y + $this->getEyeHeight();
					$pk->z = $this->forceMovement->z;
					$pk->bodyYaw = $packet->bodyYaw;
					$pk->pitch = $packet->pitch;
					$pk->yaw = $packet->yaw;
					$pk->teleport = 1;
					$this->directDataPacket($pk);
					$this->forceMovement = null;
				}else{
					$packet->yaw %= 360;
					$packet->pitch %= 360;

					if($packet->yaw < 0){
						$packet->yaw += 360;
					}

					$this->setRotation($packet->yaw, $packet->pitch);
					$this->newPosition = $newPos;
					$this->forceMovement = null;
				}

				break;
			case ProtocolInfo::MOB_EQUIPMENT_PACKET:
				if($this->spawned === false or $this->dead === true){
					break;
				}

				if($packet->slot === 0x28 or $packet->slot === 0 or $packet->slot === 255){ //0 for 0.8.0 compatibility
					$packet->slot = -1; //Air
				}else{
					$packet->slot -= 9; //Get real block slot
				}

				if($this->isCreative()){ //Creative mode match
					$item = Item::get($packet->item, $packet->meta, 1);
					$slot = Item::getCreativeItemIndex($item);
				}else{
					$item = $this->inventory->getItem($packet->slot);
					$slot = $packet->slot;
				}

				if($packet->slot === -1){ //Air
					if($this->isCreative()){
						$found = false;
						for($i = 0; $i < $this->inventory->getHotbarSize(); ++$i){
							if($this->inventory->getHotbarSlotIndex($i) === -1){
								$this->inventory->setHeldItemIndex($i);
								$found = true;
								break;
							}
						}

						if(!$found){ //couldn't find a empty slot (error)
							$this->inventory->sendContents($this);
							break;
						}
					}else{
                        if($packet->selectedSlot >= 0 and $packet->selectedSlot < 9){
                            $this->inventory->setHeldItemIndex($packet->selectedSlot);
                            $this->inventory->setHeldItemSlot($packet->slot);
                        }else{
                            $this->inventory->sendContents($this);
                            break;
                        }
					}
				}elseif(!isset($item) or $slot === -1 or $item->getId() !== $packet->item or $item->getDamage() !== $packet->meta){ // packet error or not implemented
					$this->inventory->sendContents($this);
					break;
				}elseif($this->isCreative()){
					$this->inventory->setHeldItemIndex($packet->selectedSlot);
					$this->inventory->setItem($packet->selectedSlot, $item);
					$this->inventory->setHeldItemSlot($packet->selectedSlot);
				}else{
                    if($packet->selectedSlot >= 0 and $packet->selectedSlot < 9){
                        $this->inventory->setHeldItemIndex($packet->selectedSlot);
                        $this->inventory->setHeldItemSlot($slot);
                    }else{
                        $this->inventory->sendContents($this);
                        break;
                    }
				}

				$this->inventory->sendHeldItem($this->hasSpawned);

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
				break;
			case ProtocolInfo::USE_ITEM_PACKET:
				if($this->spawned === false or $this->dead === true or $this->blocked){
					break;
				}

				$blockVector = new Vector3($packet->x, $packet->y, $packet->z);

				$this->craftingType = 0;

				if($packet->face >= 0 and $packet->face <= 5){ //Use Block, place
					$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);

					if($blockVector->distance($this) > 10 or ($this->isCreative() and $this->isAdventure())){

					}elseif($this->isCreative()){
						$item = $this->inventory->getItemInHand();
						if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->fx, $packet->fy, $packet->fz, $this) === true){
							break;
						}
					}elseif($this->inventory->getItemInHand()->getId() !== $packet->item or (($damage = $this->inventory->getItemInHand()->getDamage()) !== $packet->meta and $damage !== null)){
						$this->inventory->sendHeldItem($this);
					}else{
						$item = $this->inventory->getItemInHand();
						$oldItem = clone $item;
						//TODO: Implement adventure mode checks
						if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->fx, $packet->fy, $packet->fz, $this) === true){
							if(!$item->equals($oldItem, true) or $item->getCount() !== $oldItem->getCount()){
								$this->inventory->setItemInHand($item, $this);
								$this->inventory->sendHeldItem($this->hasSpawned);
							}
							break;
						}
					}

					$this->inventory->sendHeldItem($this);

					if($blockVector->distanceSquared($this) > 10000){
						break;
					}
					$target = $this->level->getBlock($blockVector);
					$block = $target->getSide($packet->face);

					$this->level->sendBlocks([$this], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
					break;
				}elseif($packet->face === 0xff){
					$aimPos = (new Vector3($packet->x / 32768, $packet->y / 32768, $packet->z / 32768))->normalize();

					if($this->isCreative()){
						$item = $this->inventory->getItemInHand();
					}elseif($this->inventory->getItemInHand()->getId() !== $packet->item or (($damage = $this->inventory->getItemInHand()->getDamage()) !== $packet->meta and $damage !== null)){
						$this->inventory->sendHeldItem($this);
						break;
					}else{
						$item = $this->inventory->getItemInHand();
					}

					$ev = new PlayerInteractEvent($this, $item, $aimPos, $packet->face, PlayerInteractEvent::RIGHT_CLICK_AIR);

					$this->server->getPluginManager()->callEvent($ev);

					if($ev->isCancelled()){
						$this->inventory->sendHeldItem($this);
						break;
					}

					if($item->getId() === Item::SNOWBALL){
						$nbt = new Compound("", [
							"Pos" => new Enum("Pos", [
								new Double("", $this->x),
								new Double("", $this->y + $this->getEyeHeight()),
								new Double("", $this->z)
							]),
							"Motion" => new Enum("Motion", [
								new Double("", $aimPos->x),
								new Double("", $aimPos->y),
								new Double("", $aimPos->z)
							]),
							"Rotation" => new Enum("Rotation", [
								new Float("", $this->yaw),
								new Float("", $this->pitch)
							]),
						]);

						$f = 1.5;
						$snowball = Entity::createEntity("Snowball", $this->chunk, $nbt, $this);
						$snowball->setMotion($snowball->getMotion()->multiply($f));
						if($this->isSurvival()){
							$item->setCount($item->getCount() - 1);
							$this->inventory->setItemInHand($item->getCount() > 0 ? $item : Item::get(Item::AIR));
						}
						if($snowball instanceof Projectile){
							$this->server->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($snowball));
							if($projectileEv->isCancelled()){
								$snowball->kill();
							}else{
								$snowball->spawnToAll();
								$this->level->addSound(new LaunchSound($this), $this->getViewers());
							}
						}else{
							$snowball->spawnToAll();
						}
					}

					$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, true);
					$this->startAction = $this->server->getTick();
				}
				break;
			case ProtocolInfo::PLAYER_ACTION_PACKET:
				if($this->spawned === false or $this->blocked === true or ($this->dead === true and $packet->action !== 7)){
					break;
				}

				$this->craftingType = 0;
				$packet->eid = $this->id;
				$pos = new Vector3($packet->x, $packet->y, $packet->z);

				switch($packet->action){
					case PlayerActionPacket::ACTION_START_BREAK:
						if($this->lastBreak !== PHP_INT_MAX or $pos->distanceSquared($this) > 10000){
							break;
						}
						$target = $this->level->getBlock($pos);
						$ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, $packet->face, $target->getId() === 0 ? PlayerInteractEvent::LEFT_CLICK_AIR : PlayerInteractEvent::LEFT_CLICK_BLOCK);
						$this->getServer()->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->inventory->sendHeldItem($this);
							break;
						}
						$this->lastBreak = microtime(true);
						break;
					case PlayerActionPacket::ACTION_ABORT_BREAK:
						$this->lastBreak = PHP_INT_MAX;
						break;
					case PlayerActionPacket::ACTION_RELEASE_ITEM:
						if($this->startAction > -1 and $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION)){
							if($this->inventory->getItemInHand()->getId() === Item::BOW) {
								$bow = $this->inventory->getItemInHand();
								if ($this->isSurvival() and !$this->inventory->contains(Item::get(Item::ARROW, 0, 1))) {
									$this->inventory->sendContents($this);
									break;
								}


								$nbt = new Compound("", [
									"Pos" => new Enum("Pos", [
										new Double("", $this->x),
										new Double("", $this->y + $this->getEyeHeight()),
										new Double("", $this->z)
									]),
									"Motion" => new Enum("Motion", [
										new Double("", -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),
										new Double("", -sin($this->pitch / 180 * M_PI)),
										new Double("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
									]),
									"Rotation" => new Enum("Rotation", [
										new Float("", $this->yaw),
										new Float("", $this->pitch)
									]),
									"Fire" => new Short("Fire", $this->isOnFire() ? 45 * 60 : 0)
								]);

								$diff = ($this->server->getTick() - $this->startAction);
								$p = $diff / 20;
								$f = min((($p ** 2) + $p * 2) / 3, 1) * 2;
								$ev = new EntityShootBowEvent($this, $bow, Entity::createEntity("Arrow", $this->chunk, $nbt, $this, $f == 2 ? true : false), $f);

								if ($f < 0.1 or $diff < 5) {
									$ev->setCancelled();
								}

								$this->server->getPluginManager()->callEvent($ev);

								if ($ev->isCancelled()) {
									$ev->getProjectile()->kill();
									$this->inventory->sendContents($this);
								} else {
									$ev->getProjectile()->setMotion($ev->getProjectile()->getMotion()->multiply($ev->getForce()));
									if($this->isSurvival()){
										$this->inventory->removeItem(Item::get(Item::ARROW, 0, 1));
										$bow->setDamage($bow->getDamage() + 1);
										if ($bow->getDamage() >= 385) {
											$this->inventory->setItemInHand(Item::get(Item::AIR, 0, 0));
										} else {
											$this->inventory->setItemInHand($bow);
										}
									}
									if ($ev->getProjectile() instanceof Projectile) {
										$this->server->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($ev->getProjectile()));
										if ($projectileEv->isCancelled()) {
											$ev->getProjectile()->kill();
										} else {
											$ev->getProjectile()->spawnToAll();
											$this->level->addSound(new LaunchSound($this), $this->getViewers());
										}
									} else {
										$ev->getProjectile()->spawnToAll();
									}
								}
							}
						}elseif($this->inventory->getItemInHand()->getId() === Item::BUCKET and $this->inventory->getItemInHand()->getDamage() === 1){ //Milk!
							$this->server->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($this, $this->inventory->getItemInHand()));
							if($ev->isCancelled()){
								$this->inventory->sendContents($this);
								break;
							}

							$pk = new EntityEventPacket();
							$pk->eid = $this->getId();
							$pk->event = EntityEventPacket::USE_ITEM;
							$this->dataPacket($pk);
							Server::broadcastPacket($this->getViewers(), $pk);

							if ($this->isSurvival()) {
								$slot = $this->inventory->getItemInHand();
								--$slot->count;
								$this->inventory->setItemInHand($slot);
								$this->inventory->addItem(Item::get(Item::BUCKET, 0, 1));
							}

							$this->removeAllEffects();
						}else{
							$this->inventory->sendContents($this);
						}
						break;
					case PlayerActionPacket::ACTION_STOP_SLEEPING:
						$this->stopSleep();
						break;
					case PlayerActionPacket::ACTION_RESPAWN:
						if($this->spawned === false or $this->isAlive() or !$this->isOnline()){
							break;
						}

						if($this->server->isHardcore()){
							$this->setBanned(true);
							break;
						}

						$this->craftingType = 0;

						$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $this->getSpawn()));

						$this->teleport($ev->getRespawnPosition());

						$this->setSprinting(false);
						$this->setSneaking(false);

						$this->extinguish();
						$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 300);
						$this->deadTicks = 0;
						$this->noDamageTicks = 60;

						$this->setHealth($this->getMaxHealth());

						$this->removeAllEffects();
						$this->sendData($this);

						$this->sendSettings();
						$this->inventory->sendContents($this);
						$this->inventory->sendArmorContents($this);

						$this->blocked = false;

						$this->spawnToAll();
						$this->scheduleUpdate();
						break;
					case PlayerActionPacket::ACTION_START_SPRINT:
						$ev = new PlayerToggleSprintEvent($this, true);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSprinting(true);
						}
						break;
					case PlayerActionPacket::ACTION_STOP_SPRINT:
						$ev = new PlayerToggleSprintEvent($this, false);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSprinting(false);
						}
						break;
					case PlayerActionPacket::ACTION_START_SNEAK:
						$ev = new PlayerToggleSneakEvent($this, true);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(true);
						}
						break;
					case PlayerActionPacket::ACTION_STOP_SNEAK:
						$ev = new PlayerToggleSneakEvent($this, false);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(false);
						}
						break;
				}

				$this->startAction = -1;
				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
				break;
			case ProtocolInfo::REMOVE_BLOCK_PACKET:
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					break;
				}
				$this->craftingType = 0;

				$vector = new Vector3($packet->x, $packet->y, $packet->z);


				if($this->isCreative()){
					$item = $this->inventory->getItemInHand();
				}else{
					$item = $this->inventory->getItemInHand();
				}

				$oldItem = clone $item;

				if($this->level->useBreakOn($vector, $item, $this) === true){
					if($this->isSurvival()){
						if(!$item->equals($oldItem, true) or $item->getCount() !== $oldItem->getCount()){
							$this->inventory->setItemInHand($item, $this);
							$this->inventory->sendHeldItem($this->hasSpawned);
						}
					}
					break;
				}

				$this->inventory->sendContents($this);
				$target = $this->level->getBlock($vector);
				$tile = $this->level->getTile($vector);

				$this->level->sendBlocks([$this], [$target], UpdateBlockPacket::FLAG_ALL_PRIORITY);

                $this->inventory->sendHeldItem($this);

				if($tile instanceof Spawnable){
					$tile->spawnTo($this);
				}
				break;

			case ProtocolInfo::PLAYER_ARMOR_EQUIPMENT_PACKET:
				break;

			case ProtocolInfo::INTERACT_PACKET:
				if($this->spawned === false or $this->dead === true or $this->blocked){
					break;
				}

				$this->craftingType = 0;

				$target = $this->level->getEntity($packet->target);

				$cancelled = false;

				if(
					$target instanceof Player and
					$this->server->getConfigBoolean("pvp", true) === false

				){
					$cancelled = true;
				}

				if($target instanceof Entity and $this->getGamemode() !== Player::VIEW and $this->dead !== true and $target->dead !== true){
					if($target instanceof DroppedItem or $target instanceof Arrow){
						$this->kick("Attempting to attack an invalid entity");
						$this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidEntity", [$this->getName()]));
						return;
					}

					$item = $this->inventory->getItemInHand();
					$damageTable = [
						Item::WOODEN_SWORD => 4,
						Item::GOLD_SWORD => 4,
						Item::STONE_SWORD => 5,
						Item::IRON_SWORD => 6,
						Item::DIAMOND_SWORD => 7,

						Item::WOODEN_AXE => 3,
						Item::GOLD_AXE => 3,
						Item::STONE_AXE => 3,
						Item::IRON_AXE => 5,
						Item::DIAMOND_AXE => 6,

						Item::WOODEN_PICKAXE => 2,
						Item::GOLD_PICKAXE => 2,
						Item::STONE_PICKAXE => 3,
						Item::IRON_PICKAXE => 4,
						Item::DIAMOND_PICKAXE => 5,

						Item::WOODEN_SHOVEL => 1,
						Item::GOLD_SHOVEL => 1,
						Item::STONE_SHOVEL => 2,
						Item::IRON_SHOVEL => 3,
						Item::DIAMOND_SHOVEL => 4,
					];

					$damage = [
						EntityDamageEvent::MODIFIER_BASE => isset($damageTable[$item->getId()]) ? $damageTable[$item->getId()] : 1,
					];

					if($this->distance($target) > 8){
						$cancelled = true;
					}elseif($target instanceof Player){
						if(($target->getGamemode() & 0x01) > 0){
							break;
						}elseif($this->server->getConfigBoolean("pvp") !== true or $this->server->getDifficulty() === 0){
							$cancelled = true;
						}

						$armorValues = [
							Item::LEATHER_CAP => 1,
							Item::LEATHER_TUNIC => 3,
							Item::LEATHER_PANTS => 2,
							Item::LEATHER_BOOTS => 1,
							Item::CHAIN_HELMET => 1,
							Item::CHAIN_CHESTPLATE => 5,
							Item::CHAIN_LEGGINGS => 4,
							Item::CHAIN_BOOTS => 1,
							Item::GOLD_HELMET => 1,
							Item::GOLD_CHESTPLATE => 5,
							Item::GOLD_LEGGINGS => 3,
							Item::GOLD_BOOTS => 1,
							Item::IRON_HELMET => 2,
							Item::IRON_CHESTPLATE => 6,
							Item::IRON_LEGGINGS => 5,
							Item::IRON_BOOTS => 2,
							Item::DIAMOND_HELMET => 3,
							Item::DIAMOND_CHESTPLATE => 8,
							Item::DIAMOND_LEGGINGS => 6,
							Item::DIAMOND_BOOTS => 3,
						];
						$points = 0;
						foreach($target->getInventory()->getArmorContents() as $index => $i){
							if(isset($armorValues[$i->getId()])){
								$points += $armorValues[$i->getId()];
							}
						}

						$damage[EntityDamageEvent::MODIFIER_ARMOR] = -floor($damage[EntityDamageEvent::MODIFIER_BASE] * $points * 0.04);
					}

					$ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage);
					if($cancelled){
						$ev->setCancelled();
					}

					$target->attack($ev->getFinalDamage(), $ev);

					if($ev->isCancelled()){
						if($item->isTool() and $this->isSurvival()){
							$this->inventory->sendContents($this);
						}
						break;
					}

					if($item->isTool() and $this->isSurvival()){
						if($item->useOn($target) and $item->getDamage() >= $item->getMaxDurability()){
							$this->inventory->setItemInHand(Item::get(Item::AIR, 0, 1), $this);
						}else{
							$this->inventory->setItemInHand($item, $this);
						}
					}
				}


				break;
			case ProtocolInfo::ANIMATE_PACKET:
				if($this->spawned === false or $this->dead === true){
					break;
				}

				$this->server->getPluginManager()->callEvent($ev = new PlayerAnimationEvent($this, $packet->action));
				if($ev->isCancelled()){
					break;
				}

				$pk = new AnimatePacket();
				$pk->eid = $this->getId();
				$pk->action = $ev->getAnimationType();
				Server::broadcastPacket($this->getViewers(), $pk);
				break;
			case ProtocolInfo::SET_HEALTH_PACKET: //Not used
				break;
			case ProtocolInfo::ENTITY_EVENT_PACKET:
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					break;
				}
				$this->craftingType = 0;

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false); //TODO: check if this should be true

				switch($packet->event){
					case 9: //Eating
						$items = [ //TODO: move this to item classes
							Item::APPLE => 4,
							Item::MUSHROOM_STEW => 10,
							Item::BEETROOT_SOUP => 10,
							Item::BREAD => 5,
							Item::RAW_PORKCHOP => 3,
							Item::COOKED_PORKCHOP => 8,
							Item::RAW_BEEF => 3,
							Item::STEAK => 8,
							Item::COOKED_CHICKEN => 6,
							Item::RAW_CHICKEN => 2,
							Item::MELON_SLICE => 2,
							Item::GOLDEN_APPLE => 10,
							Item::PUMPKIN_PIE => 8,
							Item::CARROT => 4,
							Item::POTATO => 1,
							Item::BAKED_POTATO => 6,
							Item::COOKIE => 2,
							Item::COOKED_FISH => [
								0 => 5,
								1 => 6
							],
							Item::RAW_FISH => [
								0 => 2,
								1 => 2,
								2 => 1,
								3 => 1
							],
						];
						$slot = $this->inventory->getItemInHand();
						if($this->getHealth() < $this->getMaxHealth() and isset($items[$slot->getId()])){
							$this->server->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($this, $slot));
							if($ev->isCancelled()){
								$this->inventory->sendContents($this);
								break;
							}

							$pk = new EntityEventPacket();
							$pk->eid = $this->getId();
							$pk->event = EntityEventPacket::USE_ITEM;
							$this->dataPacket($pk);
							Server::broadcastPacket($this->getViewers(), $pk);

							$amount = $items[$slot->getId()];
							if(is_array($amount)){
								$amount = isset($amount[$slot->getDamage()]) ? $amount[$slot->getDamage()] : 0;
							}
                            $ev = new EntityRegainHealthEvent($this, $amount, EntityRegainHealthEvent::CAUSE_EATING);
							$this->heal($ev->getAmount(), $ev);

							--$slot->count;
							$this->inventory->setItemInHand($slot, $this);
							if($slot->getId() === Item::MUSHROOM_STEW or $slot->getId() === Item::BEETROOT_SOUP){
								$this->inventory->addItem(Item::get(Item::BOWL, 0, 1));
							}elseif($slot->getId() === Item::RAW_FISH and $slot->getDamage() === 3){ //Pufferfish
								$this->addEffect(Effect::getEffect(Effect::HUNGER)->setAmplifier(2)->setDuration(15 * 20));
								//$this->addEffect(Effect::getEffect(Effect::NAUSEA)->setAmplifier(1)->setDuration(15 * 20));
								$this->addEffect(Effect::getEffect(Effect::POISON)->setAmplifier(3)->setDuration(60 * 20));
							}
						}
						break;
				}
				break;
			case ProtocolInfo::DROP_ITEM_PACKET:
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					break;
				}
				$item = $this->inventory->getItemInHand();
				$ev = new PlayerDropItemEvent($this, $item);
				$this->server->getPluginManager()->callEvent($ev);
				if($ev->isCancelled()){
					$this->inventory->sendContents($this);
					break;
				}

				$this->inventory->setItemInHand(Item::get(Item::AIR, 0, 1), $this);
				$motion = $this->getDirectionVector()->multiply(0.4);

				$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
				break;
			case ProtocolInfo::TEXT_PACKET:
				if($this->spawned === false or $this->dead === true){
					break;
				}
				$this->craftingType = 0;
				if($packet->type === TextPacket::TYPE_CHAT){
					$packet->message = TextFormat::clean($packet->message, $this->removeFormat);
					foreach(explode("\n", $packet->message) as $message){
						if(trim($message) != "" and strlen($message) <= 255 and $this->messageCounter-- > 0){
							$ev = new PlayerCommandPreprocessEvent($this, $message);

							if(mb_strlen($ev->getMessage(), "UTF-8") > 320){
								$ev->setCancelled();
							}
							$this->server->getPluginManager()->callEvent($ev);

							if($ev->isCancelled()){
								break;
							}
							if(substr($ev->getMessage(), 0, 1) === "/"){ //Command
								Timings::$playerCommandTimer->startTiming();
								$this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
								Timings::$playerCommandTimer->stopTiming();
							}else{
								$this->server->getPluginManager()->callEvent($ev = new PlayerChatEvent($this, $ev->getMessage()));
								if(!$ev->isCancelled()){
									$this->server->broadcastMessage($this->getServer()->getLanguage()->translateString($ev->getFormat(), [$ev->getPlayer()->getDisplayName(), $ev->getMessage()]), $ev->getRecipients());
								}
							}
						}
					}
				}
				break;
			case ProtocolInfo::CONTAINER_CLOSE_PACKET:
				if($this->spawned === false or $packet->windowid === 0){
					break;
				}
				$this->craftingType = 0;
				$this->currentTransaction = null;
				if(isset($this->windowIndex[$packet->windowid])){
					$this->server->getPluginManager()->callEvent(new InventoryCloseEvent($this->windowIndex[$packet->windowid], $this));
					$this->removeWindow($this->windowIndex[$packet->windowid]);
				}else{
					unset($this->windowIndex[$packet->windowid]);
				}
				break;
			case ProtocolInfo::CRAFTING_EVENT_PACKET:
				if($this->spawned === false or $this->dead){
					break;
				}elseif(!isset($this->windowIndex[$packet->windowId])){
					$this->inventory->sendContents($this);
					$pk = new ContainerClosePacket();
					$pk->windowid = $packet->windowId;
					$this->dataPacket($pk);
					break;
				}

				$recipe = $this->server->getCraftingManager()->getRecipe($packet->id);

				if($recipe === null or (($recipe instanceof BigShapelessRecipe or $recipe instanceof BigShapedRecipe) and $this->craftingType === 0)){
					$this->inventory->sendContents($this);
					break;
				}

				foreach($packet->input as $i => $item){
					if($item->getDamage() === -1 or $item->getDamage() === 0xffff){
						$item->setDamage(null);
					}

					if($i < 9 and $item->getId() > 0){
						$item->setCount(1);
					}
				}

				$canCraft = true;


				if($recipe instanceof ShapedRecipe){
					for($x = 0; $x < 3 and $canCraft; ++$x){
						for($y = 0; $y < 3; ++$y){
							$item = $packet->input[$y * 3 + $x];
							$ingredient = $recipe->getIngredient($x, $y);
							if($item->getCount() > 0 and $item->getId() > 0){
								if($ingredient === null or !$ingredient->deepEquals($item, $ingredient->getDamage() !== null, $ingredient->getCompoundTag() !== null)){
									$canCraft = false;
									break;
								}

							}elseif($ingredient !== null and $ingredient->getId() !== 0){
								$canCraft = false;
								break;
							}
						}
					}
				}elseif($recipe instanceof ShapelessRecipe){
					$needed = $recipe->getIngredientList();

					for($x = 0; $x < 3 and $canCraft; ++$x){
						for($y = 0; $y < 3; ++$y){
							$item = clone $packet->input[$y * 3 + $x];

							foreach($needed as $k => $n){
								if($n === $item and $n->getDamage() !== null and $n->getCompoundTag() !== null) {
									$remove = min($n->getCount(), $item->getCount());
									$n->setCount($n->getCount() - $remove);
									$item->setCount($item->getCount() - $remove);

									if($n->getCount() === 0){
										unset($needed[$k]);
									}
								}
							}

							if($item->getCount() > 0){
								$canCraft = false;
								break;
							}
						}
					}

					if(count($needed) > 0){
						$canCraft = false;
					}
				}else{
					$canCraft = false;
				}

				/** @var Item[] $ingredients */
				$ingredients = $packet->input;
				$result = $packet->output[0];

				if(!$canCraft or !$recipe->getResult() === $result){
					$this->server->getLogger()->debug("Unmatched recipe ". $recipe->getId() ." from player ". $this->getName() .": expected " . $recipe->getResult() . ", got ". $result .", using: " . implode(", ", $ingredients));
					$this->inventory->sendContents($this);
					break;
				}

				$used = array_fill(0, $this->inventory->getSize(), 0);

				foreach($ingredients as $ingredient){
					$slot = -1;
					foreach($this->inventory->getContents() as $index => $i){
						if($ingredient->getId() !== 0 and $ingredient === $i and $i ->getDamage() !== null and ($i->getCount() - $used[$index]) >= 1){
							$slot = $index;
							$used[$index]++;
							break;
						}
					}

					if($ingredient->getId() !== 0 and $slot === -1){
						$canCraft = false;
						break;
					}
				}

				if(!$canCraft){
					$this->server->getLogger()->debug("Unmatched recipe ". $recipe->getId() ." from player ". $this->getName() .": client does not have enough items, using: " . implode(", ", $ingredients));
					$this->inventory->sendContents($this);
					break;
				}

				$this->server->getPluginManager()->callEvent($ev = new CraftItemEvent($ingredients, $recipe));

				if($ev->isCancelled()){
					$this->inventory->sendContents($this);
					break;
				}

				foreach($used as $slot => $count){
					if($count === 0){
						continue;
					}

					$item = $this->inventory->getItem($slot);

					if($item->getCount() > $count){
						$newItem = clone $item;
						$newItem->setCount($item->getCount() - $count);
					}else{
						$newItem = Item::get(Item::AIR, 0, 0);
					}

					$this->inventory->setItem($slot, $newItem);
				}

				$extraItem = $this->inventory->addItem($recipe->getResult());
				if(count($extraItem) > 0){
					foreach($extraItem as $item){
						$this->level->dropItem($this, $item);
					}
				}

				switch($recipe->getResult()->getId()){
					case Item::WORKBENCH:
						$this->awardAchievement("buildWorkBench");
						break;
					case Item::WOODEN_PICKAXE:
						$this->awardAchievement("buildPickaxe");
						break;
					case Item::FURNACE:
						$this->awardAchievement("buildFurnace");
						break;
					case Item::WOODEN_HOE:
						$this->awardAchievement("buildHoe");
						break;
					case Item::BREAD:
						$this->awardAchievement("makeBread");
						break;
					case Item::CAKE:
						//TODO: detect complex recipes like cake that leave remains
						$this->awardAchievement("bakeCake");
						$this->inventory->addItem(Item::get(Item::BUCKET, 0, 3));
						break;
					case Item::STONE_PICKAXE:
					case Item::GOLD_PICKAXE:
					case Item::IRON_PICKAXE:
					case Item::DIAMOND_PICKAXE:
						$this->awardAchievement("buildBetterPickaxe");
						break;
					case Item::WOODEN_SWORD:
						$this->awardAchievement("buildSword");
						break;
					case Item::DIAMOND:
						$this->awardAchievement("diamond");
						break;
				}

				break;

			case ProtocolInfo::CONTAINER_SET_SLOT_PACKET:
				if($this->spawned === false or $this->blocked === true or !$this->isAlive()){
					break;
				}

				if($packet->slot < 0){
					break;
				}

				if($packet->windowid === 0){ //Our inventory
					if($packet->slot >= $this->inventory->getSize()){
						break;
					}
					if($this->isCreative()){
						if(Item::getCreativeItemIndex($packet->item) !== -1){
							$this->inventory->setItem($packet->slot, $packet->item);
							$this->inventory->setHotbarSlotIndex($packet->slot, $packet->slot); //links $hotbar[$packet->slot] to $slots[$packet->slot]
						}
					}
					$transaction = new BaseTransaction($this->inventory, $packet->slot, $this->inventory->getItem($packet->slot), $packet->item);
				}elseif($packet->windowid === ContainerSetContentPacket::SPECIAL_ARMOR){ //Our armor
					if($packet->slot >= 4){
						break;
					}

					$transaction = new BaseTransaction($this->inventory, $packet->slot + $this->inventory->getSize(), $this->inventory->getArmorItem($packet->slot), $packet->item);
				}elseif(isset($this->windowIndex[$packet->windowid])){
					$this->craftingType = 0;
					$inv = $this->windowIndex[$packet->windowid];
					$transaction = new BaseTransaction($inv, $packet->slot, $inv->getItem($packet->slot), $packet->item);
				}else{
					break;
				}

				if($transaction->getSourceItem() === $transaction->getTargetItem() and $transaction->getTargetItem()->getCount() === $transaction->getSourceItem()->getCount()){ //No changes!
					//No changes, just a local inventory update sent by the server
					break;
				}


				if($this->currentTransaction === null or $this->currentTransaction->getCreationTime() < (microtime(true) - 8)){
					if($this->currentTransaction !== null){
						foreach($this->currentTransaction->getInventories() as $inventory){
							if($inventory instanceof PlayerInventory){
								$inventory->sendArmorContents($this);
							}
							$inventory->sendContents($this);
						}
					}
					$this->currentTransaction = new SimpleTransactionGroup($this);
				}

				$this->currentTransaction->addTransaction($transaction);

				if($this->currentTransaction->canExecute()){
					$achievements = [];
					foreach($this->currentTransaction->getTransactions() as $ts){
						$inv = $ts->getInventory();
						if($inv instanceof FurnaceInventory){
							if($ts->getSlot() === 2){
								switch($inv->getResult()->getId()){
									case Item::IRON_INGOT:
										$achievements[] = "acquireIron";
										break;
								}
							}
						}
					}

					if($this->currentTransaction->execute()){
						foreach($achievements as $a){
							$this->awardAchievement($a);
						}
					}

					$this->currentTransaction = null;
				}

				break;
			case ProtocolInfo::TILE_ENTITY_DATA_PACKET:
				if($this->spawned === false or $this->blocked === true or $this->dead === true){
					break;
				}
				$this->craftingType = 0;

				$pos = new Vector3($packet->x, $packet->y, $packet->z);
				if($pos->distanceSquared($this) > 10000){
					break;
				}

				$t = $this->level->getTile($pos);
				if($t instanceof Sign){
					$nbt = new NBT(NBT::LITTLE_ENDIAN);
					$nbt->read($packet->namedtag);
					$nbt = $nbt->getData();
					if($nbt["id"] !== Tile::SIGN){
						$t->spawnTo($this);
					}else{
						$ev = new SignChangeEvent($t->getBlock(), $this, [
							TextFormat::clean($nbt["Text1"], $this->removeFormat), TextFormat::clean($nbt["Text2"], $this->removeFormat), TextFormat::clean($nbt["Text3"], $this->removeFormat), TextFormat::clean($nbt["Text4"], $this->removeFormat)
						]);

						if(!isset($t->namedtag->Creator) or $t->namedtag["Creator"] !== $this->username){
							$ev->setCancelled(true);
						}

						$this->server->getPluginManager()->callEvent($ev);

						if(!$ev->isCancelled()){
							$t->setText($ev->getLine(0), $ev->getLine(1), $ev->getLine(2), $ev->getLine(3));
						}else{
							$t->spawnTo($this);
						}
					}
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Kicks a player from the server
	 *
	 * @param string $reason
	 * @param bool   $isAdmin
	 *
	 * @return bool
	 */
	public function kick($reason = "Disconnected from server."){
		$this->server->getPluginManager()->callEvent($ev = new PlayerKickEvent($this, $reason, TextFormat::YELLOW . $this->username . " has left the game"));
		if(!$ev->isCancelled()){
			$message = $reason;
			$this->sendMessage($message);
			$this->close($ev->getQuitMessage(), $message);

			return true;
		}

		return false;
	}

	/**
	 * Sends a direct chat message to a player
	 *
	 * @param string|TextContainer $message
	 */
	public function sendMessage($message){
		$mes = explode("\n", $message);
		foreach($mes as $m){
			if($m !== ""){
				$pk = new TextPacket();
				$pk->type = TextPacket::TYPE_RAW;
				$pk->message = $m;
				$this->dataPacket($pk);
			}
		}
	}

	public function sendTranslation($message, array $parameters = []){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	public function sendPopup($message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_POPUP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	public function sendTip($message){
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TIP;
		$pk->message = $message;
		$this->dataPacket($pk);
	}

	/**
	 * @param string $message Message to be broadcasted
	 * @param string $reason  Reason showed in console
	 */
	public function close($message = "", $reason = "generic reason"){

		foreach($this->tasks as $task){
			$task->cancel();
		}
		$this->tasks = [];

		if($this->connected and !$this->closed){
			if($reason != ""){
				$pk = new DisconnectPacket;
				$pk->message = $reason;
				$this->directDataPacket($pk);
			}

			$this->connected = false;
			if($this->username != ""){
				$this->server->getPluginManager()->callEvent($ev = new PlayerQuitEvent($this, $message));
				if($this->server->getAutoSave() and $this->loggedIn === true){
					$this->save();
				}
			}

			foreach($this->server->getOnlinePlayers() as $player){
				if(!$player->canSee($this)){
					$player->showPlayer($this);
				}
			}
			$this->hiddenPlayers = [];

			foreach($this->windowIndex as $window){
				$this->removeWindow($window);
			}

			$this->interface->close($this, $reason);

			$chunkX = $chunkZ = null;
			foreach($this->usedChunks as $index => $d){
				if(PHP_INT_SIZE === 8){ $chunkX = ($index >> 32) << 32 >> 32;  $chunkZ = ($index & 0xFFFFFFFF) << 32 >> 32;}else{list( $chunkX,  $chunkZ) = explode(":", $index);  $chunkX = (int)  $chunkX;  $chunkZ = (int)  $chunkZ;};
				$this->level->freeChunk($chunkX, $chunkZ, $this);
				unset($this->usedChunks[$index]);
			}

			parent::close();

			if($this->loggedIn) $this->server->removeOnlinePlayer($this);

			$this->loggedIn = false;

			if(isset($ev) and $this->username != "" and $this->spawned !== false and $ev->getQuitMessage() != ""){
				$this->server->broadcastMessage($ev->getQuitMessage());
			}

			$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			$this->spawned = false;
			$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . $this->ip . " logged out due to " . str_replace(["\n", "\r"], [" ", ""], $reason));
			$this->windows = new \SplObjectStorage();
			$this->windowIndex = [];
			$this->usedChunks = [];
			$this->loadQueue = [];
			$this->hasSpawned = [];
			$this->spawnPosition = null;
			unset($this->buffer);
		}

		$this->perm->clearPermissions();
		$this->server->removePlayer($this);
	}

	public function __debugInfo(){
		return [];
	}

	/**
	 * Handles player data saving
	 */
	public function save(){
		if($this->closed){
			throw new \InvalidStateException("Tried to save closed player");
		}

		parent::saveNBT();
		if($this->level instanceof Level){
			$this->namedtag->Level = new String("Level", $this->level->getName());
			if($this->spawnPosition instanceof Position and $this->spawnPosition->getLevel() instanceof Level){
				$this->namedtag["SpawnLevel"] = $this->spawnPosition->getLevel()->getName();
				$this->namedtag["SpawnX"] = (int) $this->spawnPosition->x;
				$this->namedtag["SpawnY"] = (int) $this->spawnPosition->y;
				$this->namedtag["SpawnZ"] = (int) $this->spawnPosition->z;
			}

			foreach($this->achievements as $achievement => $status){
				$this->namedtag->Achievements[$achievement] = new Byte($achievement, $status === true ? 1 : 0);
			}

			$this->namedtag["playerGameType"] = $this->gamemode;
			$this->namedtag["lastPlayed"] = floor(microtime(true) * 1000);

			if($this->username != "" and $this->namedtag instanceof Compound){
				$this->server->saveOfflinePlayerData($this->username, $this->namedtag);
			}
		}
	}

	/**
	 * Gets the username
	 *
	 * @return string
	 */
	public function getName(){
		return $this->username;
	}

	public function kill(){
		if($this->dead === true or $this->spawned === false){
			return;
		}

		$message = $this->getName() . " died";

		$cause = $this->getLastDamageCause();
		$ev = null;
		if($cause instanceof EntityDamageEvent){
			$ev = $cause;
			$cause = $ev->getCause();
		}

		switch($cause){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				if($ev instanceof EntityDamageByEntityEvent){
					$e = $ev->getDamager();
					if($e instanceof Player){
						$message = $this->getName() . " was killed by " . $e->getName();
						break;
					}elseif($e instanceof Living){
						$message = $this->getName() . " was slain by " . $e->getName();
						break;
					}
				}
				$message = $this->getName() . " was killed";
				break;
			case EntityDamageEvent::CAUSE_PROJECTILE:
				if($ev instanceof EntityDamageByEntityEvent){
					$e = $ev->getDamager();
					if($e instanceof Living){
						$message = $this->getName() . " was shot by " . $e->getName();
						break;
					}
				}
				$message = $this->getName() . " was shot by arrow";
				break;
			case EntityDamageEvent::CAUSE_SUICIDE:
				$message = $this->getName() . " died";
				break;
			case EntityDamageEvent::CAUSE_VOID:
				$message = $this->getName() . " fell out of the world";
				break;
			case EntityDamageEvent::CAUSE_FALL:
                $message = $this->getName() . " fell from a high place";
				break;

			case EntityDamageEvent::CAUSE_SUFFOCATION:
				$message = $this->getName() . " suffocated in a wall";
				break;

			case EntityDamageEvent::CAUSE_LAVA:
				$message = $this->getName() . " tried to swim in lava";
				break;

			case EntityDamageEvent::CAUSE_FIRE:
				$message = $this->getName() . " went up in flames";
				break;

			case EntityDamageEvent::CAUSE_FIRE_TICK:
				$message = $this->getName() . " burned to death";
				break;

			case EntityDamageEvent::CAUSE_DROWNING:
				$message = $this->getName() . " drowned";
				break;

			case EntityDamageEvent::CAUSE_CONTACT:
				$message = $this->getName() . " was pricked to death";
				break;

			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
				$message = $this->getName() . " blew up";
				break;

			case EntityDamageEvent::CAUSE_MAGIC:
				$message = $this->getName() . " was slain by magic";
				break;

			case EntityDamageEvent::CAUSE_CUSTOM:
				break;

			default:

		}

		if($this->dead){
			return;
		}

		Entity::kill();

		$this->server->getPluginManager()->callEvent($ev = new PlayerDeathEvent($this, $this->getDrops(), $message));

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

			if($this->inventory !== null){
				$this->inventory->clearAll();
			}
		}

		if($ev->getDeathMessage() != ""){
			$this->server->broadcast($ev->getDeathMessage(), Server::BROADCAST_CHANNEL_USERS);
		}

		if($this->server->isHardcore()){
			$this->setBanned(true);
		}else{
			$pk = new RespawnPacket();
			$pos = $this->getSpawn();
			$pk->x = $pos->x;
			$pk->y = $pos->y;
			$pk->z = $pos->z;
			$this->dataPacket($pk);
		}
	}

	public function setHealth($amount){
		parent::setHealth($amount);
		if($this->spawned === true){
			$pk = new SetHealthPacket();
			$pk->health = $this->getHealth();
			$this->dataPacket($pk);
		}
	}

	public function attack($damage, EntityDamageEvent $source){
		if($this->dead === true){
			return;
		}

        if($this->isCreative()
            and $source->getCause() !== EntityDamageEvent::CAUSE_MAGIC
            and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
            and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
        ){
            $source->setCancelled();
        }

		parent::attack($damage, $source);

		if($source->isCancelled()){
			return;
		}elseif($this->getLastDamageCause() === $source and $this->spawned){
			$pk = new EntityEventPacket();
			$pk->eid = $this->getId();
			$pk->event = EntityEventPacket::HURT_ANIMATION;
			$this->dataPacket($pk);
		}
	}

	public function sendPosition(Vector3 $pos, $yaw = null, $pitch = null, $mode = 0){
		$yaw = $yaw === null ? $this->yaw : $yaw;
		$pitch = $pitch === null ? $this->pitch : $pitch;

		$pk = new MovePlayerPacket();
		$pk->eid = $this->getId();
		$pk->x = $pos->x;
		$pk->y = $pos->y + $this->getEyeHeight();
		$pk->z = $pos->z;
		$pk->pitch = $pitch;
		$pk->yaw = $yaw;
		$pk->mode = $mode;
		$this->dataPacket($pk);
	}

	public function teleport(Vector3 $pos, $yaw = null, $pitch = null){
		if(parent::teleport($pos, $yaw, $pitch)){

			foreach($this->windowIndex as $window){
				if($window === $this->inventory){
					continue;
				}
				$this->removeWindow($window);
			}

			$this->airTicks = 300;
			$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 300);

			$pk = new MovePlayerPacket();
			$pk->eid = $this->getId();
			$pk->x = $this->x;
			$pk->y = $this->y + $this->getEyeHeight();
			$pk->z = $this->z;
			$pk->bodyYaw = 0;
			$pk->pitch = $this->pitch;
			$pk->yaw = $this->yaw;
			$pk->mode = 1;
			$pk->teleport = 1;
			$this->directDataPacket($pk);

			$this->resetFallDistance();
			$this->orderChunks();
			$this->nextChunkOrderRun = 0;
			$this->forceMovement = new Vector3($this->x, $this->y, $this->z);
			$this->newPosition = null;
		}
	}


	/**
	 * @param Inventory $inventory
	 *
	 * @return int
	 */
	public function getWindowId(Inventory $inventory){
		if($this->windows->contains($inventory)){
			return $this->windows[$inventory];
		}

		return -1;
	}

	/**
	 * Returns the created/existing window id
	 *
	 * @param Inventory $inventory
	 * @param int       $forceId
	 *
	 * @return int
	 */
	public function addWindow(Inventory $inventory, $forceId = null){
		if($this->windows->contains($inventory)){
			return $this->windows[$inventory];
		}

		if($forceId === null){
			$this->windowCnt = $cnt = max(2, ++$this->windowCnt % 99);
		}else{
			$cnt = (int) $forceId;
		}
		$this->windowIndex[$cnt] = $inventory;
		$this->windows->attach($inventory, $cnt);
		if($inventory->open($this)){
			return $cnt;
		}else{
			$this->removeWindow($inventory);

			return -1;
		}
	}

	public function removeWindow(Inventory $inventory){
		$inventory->close($this);
		if($this->windows->contains($inventory)){
			$id = $this->windows[$inventory];
			$this->windows->detach($this->windowIndex[$id]);
			unset($this->windowIndex[$id]);
		}
	}

	public function setMetadata($metadataKey, MetadataValue $metadataValue){
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $metadataValue);
	}

	public function getMetadata($metadataKey){
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata($metadataKey){
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata($metadataKey, Plugin $plugin){
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $plugin);
	}


}
