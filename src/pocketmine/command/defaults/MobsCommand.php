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

namespace pocketmine\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\entity\MobsControl;
use pocketmine\utils\MainLogger;

class MobsCommand extends VanillaCommand{
	
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.mobs.description",
			"%commands.mobs.usage"
		);
		$this->setPermission("pocketmine.command.mobs");
	}

	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}
		
		if(count($args) < 1){
			$sender->sendMessage("$args[0] is not a supported mobs command.  Issue mobs help for a list of valid commands");
			return false;
		}
		
		$mobsControl = MobsControl::getInstance();
		
		if (count($args) == 1) {
			if (strtolower($args[0]) === "sleep"){
				$mobsControl->setState(MobsControl::STATE_SLEEP);
				$sender->sendMessage("Put all mobs to sleep");
			} else if (strtolower($args[0]) === "kill"){
				$mobsControl->setState(MobsControl::STATE_KILL);
				$sender->sendMessage("Killing all mobs");
			} else if (strtolower($args[0]) === "wakeup"){
				$mobsControl->setState(MobsControl::STATE_ACTIVE);
				$sender->sendMessage("Woke up all mobs");
			} else if (strtolower($args[0]) === "types") {
				 $mobsControl->displaySupportedTypes($sender);
			} else if (strtolower($args[0]) === "status") {
				$mobsControl->displayStatus($sender);
			} else if (strtolower($args[0]) === "help"){
				$sender->sendMessage("Supported mobs commands");
				$sender->sendMessage("mobs sleep - Put all mobs to sleep");
				$sender->sendMessage("mobs wakeup - Wake up all mobs");
				$sender->sendMessage("mobs kill - Kill all mobs");
				$sender->sendMessage("mobs status - Show mobs status");
				$sender->sendMessage("mobs types - List supported mob types");
				$sender->sendMessage("mobs speed [All, MobType] speedValue - Set mob speed");
				$sender->sendMessage("mobs attack [All, MobType] attackValue - Set mob attack damage");
				$sender->sendMessage("mobs health [All, MobType] healthValue - Set mob health");
				$sender->sendMessage("mobs proximity [All, MobType] healthValue - Set mob proximity");
			} else {
				$sender->sendMessage("Command entered is not a supported mobs command, enter mobs help for list of supported commands");
				return false;
			}
		} else if (count($args) == 3) {
			if (strtolower($args[0]) === "speed") {
				if ($mobsControl->setSpeed($args[1], $args[2])) {
					$sender->sendMessage("Set speed for mobs: $args[1] to: $args[2]");
				} else {
					$sender->sendMessage("$mobName is not a supported mob");
				}
			} else if (strtolower($args[0]) === "attack") {
				if ($mobsControl->setAttackDamage($args[1], $args[2])) {
					$sender->sendMessage("Set attack damage for mobs: $args[1] to: $args[2]");
				} else {
					$sender->sendMessage("$mobName is not a supported mob");
				}
			} else if (strtolower($args[0]) === "health") {
				if ($mobsControl->setHealth($args[1], $args[2])) {
					$sender->sendMessage("Set initial health for mobs: $args[1] to: $args[2]");
				} else {
					$sender->sendMessage("$mobName is not a supported mob");
				}
			} else if (strtolower($args[0]) === "proximity") {
				if ($mobsControl->setProximity($args[1], $args[2])) {
					$sender->sendMessage("Set proximity detection for mobs: $args[1] to: $args[2]");
				} else {
					$sender->sendMessage("$mobName is not a supported mob");
				}
			} else {
				$sender->sendMessage("Command entered is not a supported mobs command, enter mobs help for list of supported commands");
				return false;
			}
		} else {
			$sender->sendMessage("Command entered is not a supported mobs command, enter mobs help for list of supported commands");
			return false;
		}
		return true;
	}
}
