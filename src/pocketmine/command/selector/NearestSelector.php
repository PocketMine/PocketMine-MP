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

namespace pocketmine\command\selector;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;

class NearestSelector extends Selector{
	public function getNames(){
		return ["p"];
	}
	public function onRun(CommandSender $sender, array $args){
		if(!($sender instanceof Position)){
			return null;
		}
		$players = $sender->getNearestPlayers([function(Player $player) use($args, $sender){
			return Selector::checkSelectors($args, $sender, $player);
		}]);
		/** @var Player $rand */
		$rand = array_rand($players);
		return $rand->getName();
	}
}
