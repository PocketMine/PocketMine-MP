<?php

/* <?php

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


use pocketmine\item\Item as ItemItem;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\Int;
use pocketmine\math\AxisAlignedBB;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\entity\Monster;

class Zombie extends Monster{
    
	const NETWORK_ID = 32;
	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
        public $limit = 0;
        private $randomtick;
        private $jumptick;

        public function getName(){
		return "Zombie";
	}

	public function spawnTo(Player $player){
                $pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = Zombie::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}

	public function getData(){ //TODO
		$flags = 0;
		$flags |= $this->fireTicks > 0 ? 1 : 0;
		//$flags |= ($this->crouched === true ? 0b10:0) << 1;
		//$flags |= ($this->inAction === true ? 0b10000:0);
		$d = [
			0 => ["type" => 0, "value" => $flags],
			1 => ["type" => 1, "value" => $this->airTicks],
			16 => ["type" => 0, "value" => 0],
			17 => ["type" => 6, "value" => [0, 0, 0]],
		];

		return $d;
	}

        public function getBoundingBox(){
		$this->boundingBox = new AxisAlignedBB(
			$x = $this->x - $this->width / 2,
			$y = $this->y - $this->stepHeight,
			$z = $this->z - $this->length / 2,
			$x + $this->width,
			$y + $this->height,
			$z + $this->length
		);
		return $this->boundingBox;
	}
        
        public function updateDirection($yaw , $pitch){   
            foreach($this->hasSpawned as $player){
                $player->addEntityMovement($this->getId(), $this->x, $this->y, $this->z, $yaw, $pitch);
            }
        }
	
	public function updateMove(Vector3 $vec, Player $p = null){
		$this->x = $vec->x;
		$this->y = $vec->y;
		$this->z = $vec->z;
                foreach($this->hasSpawned as $player){
			$p !== null ?
                                $player->addEntityMovement($this->getId(), $this->x, $this->y, $this->z, $this->generateYaw($p), $this->generatePitch($p))
                                : $player->addEntityMovement($this->getId(), $this->x, $this->y, $this->z, mt_rand(0, 360), mt_rand(0, 90));
		}
	}
	public function generateYaw(Player $target){
            $x = $target->x - $this->x;
            $y = $target->y - $this->y;
            $z = $target->z - $this->z;
            $atn = atan2($z, $x);
            return rad2deg($atn - M_PI_2);
	}
        
        public function generatePitch(Player $target) {
            $x = $target->x - $this->x;
            $y = $target->y - $this->y;
            $z = $target->z - $this->z;
            return rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }
        
        public function isDay() {
           $time = $this->getLevel()->getTime();
           if((12000 > $time) && ( $time > 23001)){
               $this->setOnFire(1);
           }
        }
        
         public function onUpdate($currentTick){
             $this->entityBaseTick();
             $this->isDay();
            foreach ($this->hasSpawned as $p){
                    $dis = sqrt(pow($dZ = $p->getZ() - $this->z, 2) + pow($dX = $p->getX() - $this->x, 2));
                    if($dis <= 10 && $p->isSurvival() && $p->spawned && !$p->dead){
                            $this->motionX = 0;
                            $this->motionZ = 0;
                            $this->motionY = 0;
                            $target = $p;
                            $x = $target->x - $this->x;
                            $y = $target->y - $this->y;
                            $z = $target->z - $this->z;
                            $atn = atan2($z, $x);

                            $this->motionX = /*$this->x + */(cos($atn) * 0.1);
                            $this->motionZ = /*$this->z + */(sin($atn) * 0.1);
                            if($dis < 1.3 && (max($p->getY(),$this->y) - min($p->getY(),$this->y)) <= 2 ){
                                $p->knockBack($this, 1, ($this->motionX) /5,($this->motionZ) /5 ,.2); 
                                $p->attack(1, new EntityDamageEvent($this, 1, 1));
                            }
                           
                            $bb = clone $this->getBoundingBox();
                            $onGround = count($this->level->getCollisionBlocks($bb->offset(0, -.1, 0))) > 0;
                            if(!$onGround){$this->motionY = -.1;}else{$this->motionY = 0;};
                            $isJump = $this->isJumpable();
                            $noRoof = count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset($this->motionX, 2, $this->motionZ))) == 0;
                            if($isJump && $noRoof && $this->jumptick < 0){$this->jumptick = 35;$this->motionY = 1;$this->motionX /= 10;$this->motionZ /= 10;}
                            if($this->isMoveable()){
                                $this->updateMove(new Vector3($this->x + $this->motionX, $this->y + $this->motionY,$this->z + $this->motionZ), $p);
                            }else{
                                $this->updateDirection($this->generateYaw($p), $this->generatePitch($p));
                                //$this->MoveAround();
                            }
                }else{
                    if($this->randomtick <= 0){
                        $this->randomtick = 20 * mt_rand(1, 30);//Random 30 Secs
                        $this->motionZ = mt_rand(-1, 1);
                        $this->motionX = mt_rand(-1, 1);
                        echo $this->x + $this->motionX."_".  $this->y."_".($this->z + $this->motionZ)."\n";
                        $this->updateMove(new Vector3($this->x + $this->motionX,  $this->y, ($this->z + $this->motionZ)), null);
                }
            }
            $this->randomtick--;
            $this->jumptick--;
        }
        }
        
        public function isMoveable() {
            $bb = clone $this->getBoundingBox();
            if ($this->motionX > 0 ) $ax = 1;
            if ($this->motionX < 0 ) $ax = -1;
            if ($this->motionX == 0 ) $ax = 0;
            if ($this->motionZ > 0 ) $az = 1;
            if ($this->motionZ < 0 ) $az = -1;
            if ($this->motionZ == 0 ) $az = 0;
                
                if(count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset($ax, 0, $az))) > 0){
                    if(count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset($this->motionX, 0, $this->motionZ))) < 0){
                        return true;
                     }   else{
                     return false;
                     }
                }
            return true;
        }
        
        public function isJumpable() {
            $bb = clone $this->getBoundingBox();
            $this->motionX > 0  ? $ax = 1 : $ax = -1;
            $this->motionZ > 0  ? $az = 1 : $az = -1;
            
            if(count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset($ax, 0, $az))) > 0){
                if(count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset($ax, 2, $az))) > 0){
                    return false;
                }
                if(count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset(0, 2, 0))) > 0){
                    return false;
                }
                if(count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset($ax, .9, $az))) <= 0){
                    return true;
                }
                if(count($this->level->getCollisionBlocks($bb->grow(0,0,0)->offset(0, .9, 0))) <= 0){
                    return true;
                }
            }
            return false;
        }
        
	public function getDrops(){
		$drops = [
			ItemItem::get(ItemItem::FEATHER, 0, 1)
		];
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent and $this->lastDamageCause->getEntity() instanceof Player){
			if(mt_rand(0, 199) < 50){//1 in 4 Chance
				switch(mt_rand(0, 2)){
					case 0:
						$drops[] = ItemItem::get(ItemItem::IRON_INGOT, 0, mt_rand(0, 3));
						break;
					case 1:
						$drops[] = ItemItem::get(ItemItem::CARROT, 0, mt_rand(0, 3));
						break;
					case 2:
						$drops[] = ItemItem::get(ItemItem::POTATO, 0, mt_rand(0, 3));
						break;
				}
			}
		}

		return $drops;
	}
        
        public function heal($amount, EntityRegainHealthEvent $source){
            //$this->setHealth($amount);
            parent::heal($amount, $source);
            /*$pk = new SetHealthPacket();
            $pk->health = $this->getHealth();
            $this->dataPacket($pk);*/
	}
        
        public function kill() {
            if($this->getLastDamageCause()){
            $d = $this->getLastDamageCause()->getEntity();
            if ($d instanceof Player){
                $this->server->broadcastMessage($d->getName()." Has Just Killed a Zombie!");
            }
            }
            parent::kill();
        }
        
        public function attack($damage, EntityDamageEvent $source){
            parent::attack($damage, $source);
        }
}

