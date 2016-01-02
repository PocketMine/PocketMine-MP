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
		
// 		Command::broadcastCommandMessage($sender, new TranslationContainer("commands.mobs.start"));
		if(count($args) < 1){
// 			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));
			MainLogger::getLogger()->info("$args[0] is not a supported mobs command.  Issue mobs help for a list of valid commands");
			return false;
		}
		
		$mobsControl = MobsControl::getInstance();
		
		if (count($args) == 1) {
			if (strtolower($args[0]) === "sleep"){
				$mobsControl->setState(MobsControl::STATE_SLEEP);
				Command::broadcastCommandMessage($sender, new TranslationContainer("Put all mobs to sleep"));
			} else if (strtolower($args[0]) === "kill"){
				$mobsControl->setState(MobsControl::STATE_KILL);
				Command::broadcastCommandMessage($sender, new TranslationContainer("Killing all mobs"));
			} else if (strtolower($args[0]) === "wakeup"){
				$mobsControl->setState(MobsControl::STATE_ACTIVE);
				Command::broadcastCommandMessage($sender, new TranslationContainer("Woke up all mobs"));
			} else if (strtolower($args[0]) === "types") {
				 $mobsControl->displaySupportedTypes();
			} else if (strtolower($args[0]) === "status") {
				$mobsControl->displayStatus();
			} else if (strtolower($args[0]) === "help"){
				MainLogger::getLogger()->info("Supported mobs commands");
				MainLogger::getLogger()->info("mobs sleep - Put all mobs to sleep");
				MainLogger::getLogger()->info("mobs wakeup - Wake up all mobs");
				MainLogger::getLogger()->info("mobs kill - Kill all mobs");
				MainLogger::getLogger()->info("mobs status - Show mobs status");
				MainLogger::getLogger()->info("mobs types - List supported mob types");
				MainLogger::getLogger()->info("mobs speed [All, MobType] speedValue - Set mob speed");
				MainLogger::getLogger()->info("mobs attack [All, MobType] attackValue - Set mob attack damage");
				MainLogger::getLogger()->info("mobs health [All, MobType] healthValue - Set mob health");
				MainLogger::getLogger()->info("mobs proximity [All, MobType] healthValue - Set mob proximity");
// 				MainLogger::getLogger()->info("mobs follow [All, MobType] [Normal, Target] - Set mob follow rule");
			}
		} else if (count($args) == 3) {
			if (strtolower($args[0]) === "speed") {
				$mobsControl->setSpeed($args[1], $args[2]);
			} else if (strtolower($args[0]) === "attack") {
				$mobsControl->setAttackDamage($args[1], $args[2]);
			} else if (strtolower($args[0]) === "health") {
				$mobsControl->setHealth($args[1], $args[2]);
			} else if (strtolower($args[0]) === "proximity") {
				$mobsControl->setProximity($args[1], $args[2]);
			} else if (strtolower($args[0]) === "follow") {
				if (strtolower($args[2]) === "normal") {
					$follow = MobsControl::FOLLOW_NORMAL;
				} else if (strtolower($args[2]) === "target") {
					$follow = MobsControl::FOLLOW_TARGET;
				} else {
					MainLogger::getLogger()->info("$args[2] is not a supported follow mode");
					return false;
				}
				$mobsControl->setFollow($args[1], $follow);
			} else {
				MainLogger::getLogger()->info("Command entered is not a supported mobs command, enter mobs help for list of supported commands");
				return false;
			}
		} else {
			MainLogger::getLogger()->info("Command entered is not a supported mobs command, enter mobs help for list of supported commands");
			return false;
		}
		return true;
	}
}
