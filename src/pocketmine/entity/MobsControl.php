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

use pocketmine\entity\Monster;
use pocketmine\utils\MainLogger;

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
		
		
// 		$this->speedMap["Zombie"] = .1;
// 		$this->damageMap["Zombie"] = 1;
// 		$this->healthMap["Zombie"] = 20;
// 		$this->proximityMap["Zombie"] = 10;
		
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
		
// 		$this->speedMap["ZombiePigman"] = .1;
// 		$this->damageMap["ZombiePigman"] = 1;
// 		$this->healthMap["ZombiePigman"] = 20;
// 		$this->proximityMap["ZombiePigman"] = 10;
		
// 		$this->speedMap["Slime"] = .1;
// 		$this->damageMap["Slime"] = 1;
// 		$this->healthMap["Slime"] = 20;
// 		$this->proximityMap["Slime"] = 10;
		
// 		$this->speedMap["Enderman"] = .1;
// 		$this->damageMap["Enderman"] = 1;
// 		$this->healthMap["Enderman"] = 20;
// 		$this->proximityMap["Enderman"] = 10;
		
// 		$this->speedMap["Silverfish"] = .1;
// 		$this->damageMap["Silverfish"] = 1;
// 		$this->healthMap["Silverfish"] = 20;
// 		$this->proximityMap["Silverfish"] = 10;
		
// 		$this->speedMap["CaveSpider"] = .1;
// 		$this->damageMap["CaveSpider"] = 1;
// 		$this->healthMap["CaveSpider"] = 20;
// 		$this->proximityMap["CaveSpider"] = 10;
		
// 		$this->speedMap["Ghast"] = .1;
// 		$this->damageMap["Ghast"] = 1;
// 		$this->healthMap["Ghast"] = 20;
// 		$this->proximityMap["Ghast"] = 10;
		
// 		$this->speedMap["MagmaCube"] = .1;
// 		$this->damageMap["MagmaCube"] = 1;
// 		$this->healthMap["MagmaCube"] = 20;
// 		$this->proximityMap["MagmaCube"] = 10;
		
// 		$this->speedMap["Blaze"] = .1;
// 		$this->damageMap["Blaze"] = 1;
// 		$this->healthMap["Blaze"] = 20;
// 		$this->proximityMap["Blaze"] = 10;
		
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
			MainLogger::getLogger()->info("Set speed for all mobs to: $speed");
		} else if (in_array($mobName, $this->types)) {
			$this->speedMap[$mobName] = $speed;
			MainLogger::getLogger()->info("Set $mobName speed to: $speed");
		} else {
			MainLogger::getLogger()->info("$mobName is not a supported mob");
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
			MainLogger::getLogger()->info("Set attack damage for all mobs to: $damage");
		} else if (in_array($mobName, $this->types)) {
			$this->damageMap[$mobName] = $damage;
			MainLogger::getLogger()->info("Set $mobName damage to: $damage");
		} else {
			MainLogger::getLogger()->info("$mobName is not a supported mob");
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
			MainLogger::getLogger()->info("Set health for all mobs to: $health");
		} else if (in_array($mobName, $this->types)) {
			$this->healthMap[$mobName] = $health;
			MainLogger::getLogger()->info("Set $mobName health to: $health");
		} else {
			MainLogger::getLogger()->info("$mobName is not a supported mob");
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
			MainLogger::getLogger()->info("Set proximity detection for all mobs to: $proximity");
		} else if (in_array($mobName, $this->types)) {
			$this->proximityMap[$mobName] = $proximity;
			MainLogger::getLogger()->info("Set $mobName proximity detection to: $proximity");
		} else {
			MainLogger::getLogger()->info("$mobName is not a supported mob");
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
			MainLogger::getLogger()->info("Set follow mode for all mobs to: $follow");
		} else if (in_array($mobName, $this->types)) {
			$this->followMap[$mobName] = $follow;
			MainLogger::getLogger()->info("Set $mobName follow mode to: $follow");
		} else {
			MainLogger::getLogger()->info("$$mobName is not a supported mob");
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
		print "MobsControl register 1 \n";
		$mobName = $monster->getName();
		print "MobsControl register 2 type = $mobName \n";
		if (array_key_exists($mobName, $this->countMap)) {
			print "MobsControl register 3 \n";
			$this->countMap[$mobName] = $this->countMap[$mobName] + 1;
			print "MobsControl register 4 \n";
		} else {
			print "MobsControl register 5 \n";
			$this->countMap[$mobName] = 1;
			print "MobsControl register 6 \n";
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
	
	public function displaySupportedTypes() {
		MainLogger::getLogger()->info("Supported Mob Types:");
		foreach($this->types as $val) {
			MainLogger::getLogger()->info("$val");
		}
	}
	
	public function displayState() {
		switch($this->state) {
			case MobsControl::STATE_KILL:
				MainLogger::getLogger()->info("  Mobs state: Killed");
				break;
			case MobsControl::STATE_SLEEP:
				MainLogger::getLogger()->info("  Mobs state: Sleeping");
				break;			
			case MobsControl::STATE_ACTIVE:
				MainLogger::getLogger()->info("  Mobs state: Awake");
				break;
		}
	}
	
	public function displaySpeed() {
		MainLogger::getLogger()->info(" ");
		MainLogger::getLogger()->info("  Speed:");
		foreach($this->types as $val) {
			$speed = $this->getSpeed($val);
			MainLogger::getLogger()->info("    $val: $speed");
		}
	}
	
	public function displayAttackDamage() {
		MainLogger::getLogger()->info(" ");
		MainLogger::getLogger()->info("  Attack Damage:");
		foreach($this->types as $val) {
			$damage = $this->getAttackDamage($val);
			MainLogger::getLogger()->info("    $val: $damage");
		}
	}
	
	public function displayProximity() {
		MainLogger::getLogger()->info(" ");
		MainLogger::getLogger()->info("  Proximity Detection:");
		foreach($this->types as $val) {
			$proximity = $this->getProximity($val);
			MainLogger::getLogger()->info("    $val: $proximity");
		}
	}
	
	public function displayHealth() {
		MainLogger::getLogger()->info(" ");
		MainLogger::getLogger()->info("  Starting Health:");
		foreach($this->types as $val) {
			$health = $this->getHealth($val);
			MainLogger::getLogger()->info("    $val: $health");
		}
	}
	
	public function displayCount() {
		MainLogger::getLogger()->info(" ");
		MainLogger::getLogger()->info("  Monster Count:");
		foreach($this->types as $val) {
			$count = $this->getCount($val);
			MainLogger::getLogger()->info("    $val: $count");
		}
	}
	
	public function displayStatus() {
		MainLogger::getLogger()->info("Mobs Status:");
		MainLogger::getLogger()->info("------------");
		$this->displayState();
		$this->displaySpeed();
		$this->displayProximity();
		$this->displayAttackDamage();
		$this->displayHealth();
		$this->displayCount();
	}
	
}