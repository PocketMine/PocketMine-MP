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
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SpawnpointCommand extends VanillaCommand{

	public function __construct($name){
		parent::__construct(
			$name,
			"Sets a player's spawn point",
			"/spawnpoint OR /spawnpoint <player> OR /spawnpoint <player> <x> <y> <z>"
		);
		$this->setPermission("pocketmine.command.spawnpoint");
	}

	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		$target = null;

		if(count($args) === 0){
			if($sender instanceof Player){
				$target = $sender;
			}else{
				$sender->sendMessage(TextFormat::RED . "Please provide a player!");
				return true;
			}
		}else{
			$target = Server::getInstance()->getPlayer($args[0]);
			if($target === null){
				$sender->sendMessage(TextFormat::RED . "Can't find player ".$args[0]);
				return true;
			}
		}

		$level = $target->getLevel();

		if(count($args) === 4){
			if($level !== null){
				$x = (int) $this->getRelativeDouble($sender->x, $sender, $args[1]);
				$y = (int) $this->getRelativeDouble($sender->y, $sender, $args[2], 0, 128);
				$z = (int) $this->getRelativeDouble($sender->z, $sender, $args[3]);
				$target->setSpawn(new Position($x, $y, $z, $level));
				Command::broadcastCommandMessage($sender, "Set ".$target->getName()."'s spawnpoint to ".$x.", ".$y.", ".$z);
				return true;
			}
		}elseif(count($args) <= 1){
			if($sender instanceof Player) {
				$pos = new Position((int) $sender->x, (int) $sender->y, (int) $sender->z, $sender->getLevel());
				$target->setSpawn($pos);
				Command::broadcastCommandMessage($sender, "Set ".$target->getName()."'s spawnpoint to ".$pos->x.", ".$pos->y.", ".$pos->z);
				return true;
			}else{
				$sender->sendMessage(TextFormat::RED . "Please provide a player!");
				return true;
			}
		}

		$sender->sendMessage(TextFormat::RED . "Usage: ".$this->usageMessage);
		return true;
	}
}
