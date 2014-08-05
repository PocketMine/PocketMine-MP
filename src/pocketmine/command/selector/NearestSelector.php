<?php

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
