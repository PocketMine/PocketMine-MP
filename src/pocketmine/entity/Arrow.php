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

namespace pocketmine\entity;


use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\format\FullChunk;
use pocketmine\level\MovingObjectPosition;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Short;
use pocketmine\nbt\tag\String;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\Player;

class Arrow extends Projectile{
	const NETWORK_ID = 80;

	public $width = 0.5;
	public $length = 0.5;
	public $height = 0.5;

	/** @var Entity */
	public $shootingEntity = null;

	protected $gravity = 0.05;
	protected $drag = 0.01;

	private $damage = 6;

	public function __construct(FullChunk $chunk, Compound $nbt, Entity $shootingEntity = null){
		$this->shootingEntity = $shootingEntity;
		parent::__construct($chunk, $nbt);
	}

	protected function initEntity(){
		$this->namedtag->id = new String("id", "Arrow");
		$this->setMaxHealth(1);
		$this->setHealth(1);
		if(isset($this->namedtag->Age)){
			$this->age = $this->namedtag["Age"];
		}

	}

	public function onUpdate(){
		$this->entityBaseTick();

		if($this->closed !== false){
			return false;
		}

		$movingObjectPosition = null;

		$this->motionY -= $this->gravity;

		$this->inBlock = $this->checkObstruction($this->x, ($this->boundingBox->minY + $this->boundingBox->maxY) / 2, $this->z);

		$moveVector = new Vector3($this->x + $this->motionX, $this->y + $this->motionY, $this->z + $this->motionZ);

		$list = $this->getLevel()->getCollidingEntities($this->boundingBox->addCoord($this->motionX, $this->motionY, $this->motionZ)->expand(1, 1, 1), $this);

		$nearDistance = PHP_INT_MAX;
		$nearEntity = null;

		foreach($list as $entity){
			if(!$entity->canCollideWith($this) or ($entity === $this->shootingEntity and $this->ticksLived < 5)){
				continue;
			}

			$axisalignedbb = $entity->boundingBox->grow(0.3, 0.3, 0.3);
			$ob = $axisalignedbb->calculateIntercept($this, $moveVector);

			if($ob === null){
				continue;
			}

			$distance = $this->distance($ob->hitVector);

			if($distance < $nearDistance){
				$nearDistance = $distance;
				$nearEntity = $entity;
			}
		}

		if($nearEntity !== null){
			$movingObjectPosition = MovingObjectPosition::fromEntity($nearEntity);
		}

		if($movingObjectPosition !== null){
			if($movingObjectPosition->entityHit !== null){
				$motion = sqrt($this->motionX ** 2 + $this->motionY ** 2 + $this->motionZ ** 2);
				$damage = ceil($motion * $this->damage);


				$ev = new EntityDamageByEntityEvent($this->shootingEntity === null ? $this : $this->shootingEntity, $movingObjectPosition->entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);

				$this->server->getPluginManager()->callEvent($ev);

				if(!$ev->isCancelled()){
					$movingObjectPosition->entityHit->attack($damage, $ev);
					if($this->fireTicks > 0){
						$movingObjectPosition->entityHit->setOnFire(5);
					}
					$this->kill();
				}
			}
		}

		$this->move($this->motionX, $this->motionY, $this->motionZ);

		$friction = 1 - $this->drag;

		if($this->onGround){
			$friction = $this->getLevel()->getBlock(new Vector3($this->getFloorX(), $this->getFloorY() - 1, $this->getFloorZ()))->frictionFactor * $friction;
		}

		$this->motionX *= $friction;
		$this->motionY *= 1 - $this->drag;
		$this->motionZ *= $friction;

		if($this->onGround){
			$this->motionY *= -0.5;
		}

		if(abs($this->motionX) < 0.01){
			$this->motionX = 0;
		}
		if(abs($this->motionZ) < 0.01){
			$this->motionZ = 0;
		}

		if($this->motionX != 0 or $this->motionY != 0 or $this->motionZ != 0){
			$f = sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
			$this->yaw = (atan2($this->motionX, $this->motionZ) * 180 / M_PI);
			$this->pitch = (atan2($this->motionY, $f) * 180 / M_PI);
		}

		if($this->age > 1200){
			$this->kill();
		}
		$this->updateMovement();

		return !$this->onGround or ($this->motionX == 0 and $this->motionY == 0 and $this->motionZ == 0);
	}

	public function attack($damage, $source = EntityDamageEvent::CAUSE_MAGIC){
		$this->setLastDamageCause($source);
		$this->setHealth($this->getHealth() - $damage);
	}

	public function heal($amount){

	}

	public function saveNBT(){
		$this->namedtag->Age = new Short("Age", $this->age);
	}

	public function getData(){
		$flags = 0;
		$flags |= $this->fireTicks > 0 ? 1 : 0;

		return [
			0 => ["type" => 0, "value" => $flags]
		];
	}

	public function canCollideWith(Entity $entity){
		return $entity instanceof Living;
	}

	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->type = Arrow::NETWORK_ID;
		$pk->eid = $this->getID();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->did = 0; //TODO: send motion here
		$player->dataPacket($pk);

		$pk = new SetEntityMotionPacket();
		$pk->entities = [
			[$this->getID(), $this->motionX, $this->motionY, $this->motionZ]
		];
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}
}