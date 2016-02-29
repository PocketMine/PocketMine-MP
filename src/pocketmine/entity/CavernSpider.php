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

use pocketmine\item\Item as ItemItem;
use pocketmine\Player;

class CavernSpider extends Monster{
	const NETWORK_ID = 40;

	public $width = 1.438;
	public $length = 1.188;
	public $height = 0.547;

	public function initEntity(){
		$this->setMaxHealth(12);
		parent::initEntity();
	}

	public function getName(){
		return "Cave Spider";
	}

	public function spawnTo(Player $player){
		$pk = $this->addEntityDataPacket($player);
		$pk->type = CavernSpider::NETWORK_ID;

		$player->dataPacket($pk);
		parent::spawnTo($player);
	}

	public function getDrops(){
		return[
			ItemItem::get(ItemItem::STRING, 0, mt_rand(0, 2)),
			ItemItem::get(ItemItem::SPIDER_EYE, 0, mt_rand(0, 1))
		];
	 }
  	
}
