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
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\entity\PositionAndOrientation;
use pocketmine\entity\MobsControl;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\item\Item as ItemItem;

abstract class Monster extends Creature{

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	
	protected $withinProximity = 10;
	protected $chosenPlayer = null;
	protected $mobsControl = null;

	protected function initEntity(){
		parent::initEntity();
		$this->mobsControl = MobsControl::getInstance();
		$this->mobsControl->register($this);
		$health = $this->mobsControl->getHealth($this->getName());
		$this->setMaxHealth($health);
		$this->setHealth($health);
	}
	
	public function kill() {
		if ($this->getHealth() > 0) {
			$name = $this->getName();
			$this->mobsControl->deregister($this);
		}
		parent::kill();
	}
		
	public function entityBaseTick($tickDiff = 1){
		$hasUpdate = parent::entityBaseTick($tickDiff);
		if($this->chunk === null or $this->closed){
			return;
		}
		if ($this->mobsControl == null) {
			$this->mobsControl = MobsControl::getInstance();
		}
		$mobsState = $this->mobsControl->getState();
		if ($mobsState == MobsControl::STATE_KILL) {
			$this->kill();
			return;
		} else if ($mobsState == MobsControl::STATE_SLEEP) {
			return;
		}
		$speed = $this->mobsControl->getSpeed($this->getName());;
		$attackDamage = $this->mobsControl->getAttackDamage($this->getName());
		$withinProximity = $this->mobsControl->getProximity($this->getName());;
		$player = $this->choosePlayer($withinProximity);
		if ($player != null) {			
			$proximity = $this->getPlayerProximity($player, $withinProximity);
			if ($proximity == 0) {
				$ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $attackDamage);
				$player->attack($attackDamage, $ev);
			} else {
				$monsterPosition = new PositionAndOrientation($this);
				if ($this->x < $player->getX()) {
					if ($this->z < $player->getZ()) {
						$monsterPosition->tryNorthWest($speed);
					} else if ($this->z > $player->getZ()) {
						$monsterPosition->trySouthWest($speed);
					} else {
						$monsterPosition->tryWest($speed);
					}
				} else if ($this->x > $player->getX()) {
					$this->x = $this->x - 1;
					if ($this->z < $player->getZ()) {
						$monsterPosition->tryNorthEast($speed);
					} else if ($this->z > $player->getZ()) {
						$monsterPosition->trySouthEast($speed);
					} else {
						$monsterPosition->tryEast($speed);
					}
				} else {
					if ($this->z < $player->getZ()) {
						$monsterPosition->tryNorth($speed);
					} else if ($this->z > $player->getZ()) {
						$monsterPosition->trySouth($speed);
					}
				}
				$this->x = $monsterPosition->x;
				$this->y = $monsterPosition->y;
				$this->z = $monsterPosition->z;
				$this->yaw = $monsterPosition->yaw;
			}
		}
	}
	
	public function createAddEntityPacket(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		return $pk;
	}
	
	public function getDrops(){
		$drops = [
				ItemItem::get(ItemItem::FEATHER, 0, 1)
		];
		if(mt_rand(0, 199) < 5){
			switch(mt_rand(0, 2)){
				case 0:
					$drops[] = ItemItem::get(ItemItem::IRON_INGOT, 0, 1);
					break;
				case 1:
					$drops[] = ItemItem::get(ItemItem::CARROT, 0, 1);
					break;
				case 2:
					$drops[] = ItemItem::get(ItemItem::POTATO, 0, 1);
					break;
			}
		}
		return $drops;
	}
	
	private function choosePlayer($withinProximity) {
		if ($this->chosenPlayer != null) {
			if ($this->chosenPlayer->isOnline() && $this->chosenPlayer->getHealth() > 0) {
				$proximity = $this->getPlayerProximity($this->chosenPlayer, $withinProximity);
				if ($proximity >= 0) {
					return $this->chosenPlayer;
				}
			}
		}
		$playerCount = 0;
		foreach($this->level->getChunkPlayers($this->chunk->getX(), $this->chunk->getZ()) as $player){
			if($player->isOnline() && $player->getHealth() > 0) {
				$proximity = $this->getPlayerProximity($player, $withinProximity);
				if ($proximity >= 0) {
					$playerCount = $playerCount + 1;
					$playerArray[$playerCount] = $player;
				}
			}
		}
		if ($playerCount > 0) {
			$chosen = rand(1, $playerCount);
			$player = $playerArray[$chosen];
			$name = $player->getName();
			$this->chosenPlayer = $player;
			return $player;
		}
		return null;
	}
	
	private function getPlayerProximity(Player $player, $withinProximity) {
		$xd = abs($this->x - $player->getX());
		$yd = abs($this->y - $player->getY());
		$zd = abs($this->z - $player->getZ());
	
		if ($xd <= 1 && $zd <= 1 && $yd <=3) {
			return 0;  // Right next to player
		} else if (($xd < $withinProximity && $zd < $withinProximity) && ($yd <= 5)) {
			$py = $player->getY();
			$my = $this->y;
			return 1;  // Within proximity
		} else {
			return -1;  // Not in proximity;
		}
	}

}