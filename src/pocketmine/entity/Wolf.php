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

use pocketmine\Player;


class Wolf extends Animal implements Tameable{
	const NETWORK_ID = 14;

	public $height = 0.969;
	public $width = 0.5;
	public $lenght = 1.594;

	public function initEntity(){
		$this->setMaxHealth(8); //Untamed
		parent::initEntity();
	}

	public function getName(){
		return "Wolf";
	}
	
	public function spawnTo(Player $player){
		$pk = $this->addEntityDataPacket($player);
		$pk->type = Wolf::NETWORK_ID;

		$player->dataPacket($pk);
		parent::spawnTo($player);
	}
}
