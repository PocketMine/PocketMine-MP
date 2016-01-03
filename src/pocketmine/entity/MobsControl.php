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

use pocketmine\utils\MainLogger;
use pocketmine\command\CommandSender;

class MobsControl {
	
	const STATE_KILL = 1;
	const STATE_SLEEP = 2;
	const STATE_ACTIVE = 3;
	
	const FOLLOW_NORMAL = 1;
	const FOLLOW_TARGET = 2;
	
	private $types = array("Zombie", "Creeper", "Skeleton", "Spider", "ZombiePigman", "Slime", "Enderman", "Silverfish",
			"CaveSpider", "Ghast", "MagmaCube", "Blaze", "ZombieVillager");
	
	private static $instance;
	
	private $state = MobsControl::STATE_SLEEP; 
	private $speedMap;
	private $damageMap;
	private $healthMap;
	private $proximityMap;
	private $followMap;
	
	private $countMap;
	
	public static function getInstance() {
		if (null === MobsControl::$instance) {
			MobsControl::$instance = new MobsControl();
		}
		return MobsControl::$instance;
	}
	
	protected function __construct() {
		$this->speedMap["Default"] = .1;
		$this->damageMap["Default"] = 1;
		$this->healthMap["Default"] = 20;
		$this->proximityMap["Default"] = 10;
		$this->followMap["Default"] = MobsControl::FOLLOW_NORMAL;
		$this->countMap["PlaceHolder"] = 0;
		
		
		$this->speedMap["Zombie"] = .03;
		$this->damageMap["Zombie"] = 3;
		$this->healthMap["Zombie"] = 20;
		$this->proximityMap["Zombie"] = 4;
		
// 		$this->speedMap["Creeper"] = .1;
// 		$this->damageMap["Creeper"] = 1;
// 		$this->healthMap["Creeper"] = 20;
// 		$this->proximityMap["Creeper"] = 10;
		
// 		$this->speedMap["Skeleton"] = .1;
// 		$this->damageMap["Skeleton"] = 1;
// 		$this->healthMap["Skeleton"] = 20;
// 		$this->proximityMap["Skeleton"] = 10;
		
// 		$this->speedMap["Spider"] = .1;
// 		$this->damageMap["Spider"] = 1;
// 		$this->healthMap["Spider"] = 20;
// 		$this->proximityMap["Spider"] = 10;
		
		$this->speedMap["ZombiePigman"] = .05;
		$this->damageMap["ZombiePigman"] = 6;
		$this->healthMap["ZombiePigman"] = 50;
		$this->proximityMap["ZombiePigman"] = 6;
		
// 		$this->speedMap["Slime"] = .1;
// 		$this->damageMap["Slime"] = 1;
// 		$this->healthMap["Slime"] = 20;
// 		$this->proximityMap["Slime"] = 10;
		
		$this->speedMap["Enderman"] = .08;
		$this->damageMap["Enderman"] = 8;
		$this->healthMap["Enderman"] = 60;
		$this->proximityMap["Enderman"] = 6;
		
		$this->speedMap["Silverfish"] = .01;
		$this->damageMap["Silverfish"] = 0;
		$this->healthMap["Silverfish"] = 20;
		$this->proximityMap["Silverfish"] = 2;
		
// 		$this->speedMap["CaveSpider"] = .1;
// 		$this->damageMap["CaveSpider"] = 1;
// 		$this->healthMap["CaveSpider"] = 20;
// 		$this->proximityMap["CaveSpider"] = 10;
		
		$this->speedMap["Ghast"] = .1;
		$this->damageMap["Ghast"] = 13;
		$this->healthMap["Ghast"] = 1000;
		$this->proximityMap["Ghast"] = 6;
		
// 		$this->speedMap["MagmaCube"] = .1;
// 		$this->damageMap["MagmaCube"] = 1;
// 		$this->healthMap["MagmaCube"] = 20;
// 		$this->proximityMap["MagmaCube"] = 10;
		
		$this->speedMap["Blaze"] = .04;
		$this->damageMap["Blaze"] = 10;
		$this->healthMap["Blaze"] = 15;
		$this->proximityMap["Blaze"] = 2;
		
// 		$this->speedMap["ZombieVillager"] = .1;
// 		$this->damageMap["ZombieVillager"] = 1;
// 		$this->healthMap["ZombieVillager"] = 20;
// 		$this->proximityMap["ZombieVillager"] = 10;
	}
	
	public function getstate(){
		return $this->state;
	}
	
	public function setstate($newstate) {
		$this->state = $newstate;
	}
	
	public function setSpeed($mobName, $speed) {
		if ($mobName === "All") {
			foreach($this->types as $val) {
				$this->speedMap[$val] = $speed;
			}
			return true;
		} else if (in_array($mobName, $this->types)) {
			$this->speedMap[$mobName] = $speed;
			return true;
			MainLogger::getLogger()->info("Set $mobName speed to: $speed");
		} else {
			return false;
		}
	}
	
	public function getSpeed($mobName) {
		if (array_key_exists($mobName, $this->speedMap)) {
			$speed = $this->speedMap[$mobName];
		} else {
			$speed = $this->speedMap["Default"];
		}
		return $speed;
	}
	
	public function setAttackDamage($mobName, $damage) {
		if ($mobName === "All") {
			foreach($this->types as $val) {
				$this->damageMap[$val] = $damage;
			}
			return true;
		} else if (in_array($mobName, $this->types)) {
			$this->damageMap[$mobName] = $damage;
			return true;
		} else {
			return false;
		}
	}
	
	public function getAttackDamage($mobName) {
		if (array_key_exists($mobName, $this->damageMap)) {
			$damage = $this->damageMap[$mobName];
		} else {
			$damage = $this->damageMap["Default"];
		}
		return $damage;
	}
	
	public function setHealth($mobName, $health) {
		if ($mobName === "All") {
			foreach($this->types as $val) {
				$this->healthMap[$val] = $health;
			}
			return true;
		} else if (in_array($mobName, $this->types)) {
			$this->healthMap[$mobName] = $health;
			return true;
		} else {
			return false;
		}
	}
	
	public function getHealth($mobName) {
		if (array_key_exists($mobName, $this->healthMap)) {
			$health = $this->healthMap[$mobName];
		} else {
			$health = $this->healthMap["Default"];
		}
		return $health;
	}
	
	
	public function setProximity($mobName, $proximity) {
		if ($mobName === "All") {
			foreach($this->types as $val) {
				$this->proximityMap[$val] = $proximity;
			}
			return true;
		} else if (in_array($mobName, $this->types)) {
			$this->proximityMap[$mobName] = $proximity;
			return true;
		} else {
			return false;
		}
	}
	
	public function getProximity($mobName) {
		if (array_key_exists($mobName, $this->proximityMap)) {
			$proximity = $this->proximityMap[$mobName];
		} else {
			$proximity = $this->proximityMap["Default"];
		}
		return $proximity;
	}
	
	public function setFollow($mobName, $follow) {
		if ($mobName === "All") {
			foreach($this->types as $val) {
				$this->followMap[$val] = $follow;
			}
			return true;
		} else if (in_array($mobName, $this->types)) {
			$this->followMap[$mobName] = $follow;
			return true;
		} else {
			return false;
		}
		$this->healthMap[$mobName] = $follow;
	}
	
	public function getFollow($mobName) {
		if (array_key_exists($mobName, $this->followMap)) {
			$follow = $this->followMap[$mobName];
		} else {
			$follow = $this->followMap["Default"];
		}
		return $follow;
	}
	
	public function register(Monster $monster) {
		$mobName = $monster->getName();
		if (array_key_exists($mobName, $this->countMap)) {
			$this->countMap[$mobName] = $this->countMap[$mobName] + 1;
		} else {
			$this->countMap[$mobName] = 1;
		}
	}
	
	public function deregister(Monster $monster) {
		$mobName = $monster->getName();
		if (array_key_exists($mobName, $this->countMap)) {
			$this->countMap[$mobName] = $this->countMap[$mobName] - 1;
		}
	}
	
	public function getCount($mobName) {
		if (array_key_exists($mobName, $this->countMap)) {
			$count = $this->countMap[$mobName];
		} else {
			$count = 0;
		}
		return $count;
	}
	
	public function getSupportedTypes() {
		return $this->types;
	}
	
	public function displaySupportedTypes(CommandSender $sender) {
		$sender->sendMessage("Supported Mob Types:");
		foreach($this->types as $val) {
			$sender->sendMessage("$val");
		}
	}
	
	public function displayState(CommandSender $sender) {
		switch($this->state) {
			case MobsControl::STATE_KILL:
				$sender->sendMessage("  Mobs state: Killed");
				break;
			case MobsControl::STATE_SLEEP:
				$sender->sendMessage("  Mobs state: Sleeping");
				break;			
			case MobsControl::STATE_ACTIVE:
				$sender->sendMessage("  Mobs state: Awake");
				break;
		}
	}
	
	public function displaySpeed(CommandSender $sender) {
		$sender->sendMessage(" ");
		$sender->sendMessage("  Speed:");
		foreach($this->types as $val) {
			$speed = $this->getSpeed($val);
			$sender->sendMessage("    $val: $speed");
		}
	}
	
	public function displayAttackDamage(CommandSender $sender) {
		$sender->sendMessage(" ");
		$sender->sendMessage("  Attack Damage:");
		foreach($this->types as $val) {
			$damage = $this->getAttackDamage($val);
			$sender->sendMessage("    $val: $damage");
		}
	}
	
	public function displayProximity(CommandSender $sender) {
		$sender->sendMessage(" ");
		$sender->sendMessage("  Proximity Detection:");
		foreach($this->types as $val) {
			$proximity = $this->getProximity($val);
			$sender->sendMessage("    $val: $proximity");
		}
	}
	
	public function displayHealth(CommandSender $sender) {
		$sender->sendMessage(" ");
		$sender->sendMessage("  Starting Health:");
		foreach($this->types as $val) {
			$health = $this->getHealth($val);
			$sender->sendMessage("    $val: $health");
		}
	}
	
	public function displayCount(CommandSender $sender) {
		$sender->sendMessage(" ");
		$sender->sendMessage("  Monster Count:");
		foreach($this->types as $val) {
			$count = $this->getCount($val);
			$sender->sendMessage("    $val: $count");
		}
	}
	
	public function displayStatus(CommandSender $sender) {
		$sender->sendMessage("Mobs Status:");
		$sender->sendMessage("------------");
		$this->displayState($sender);
		$this->displaySpeed($sender);
		$this->displayProximity($sender);
		$this->displayAttackDamage($sender);
		$this->displayHealth($sender);
		$this->displayCount($sender);
	}
	
}