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

use pocketmine\item\Item as drp;
use pocketmine\Player;

class IronGolem extends Animal{
    const NETWORK_ID = 20;

    public $height = 2.688;
    public $width = 1.625;
    public $lenght = 0.906;

    public function initEntity(){
        $this->setMaxHealth(100);
        parent::initEntity();
    }

    public function getName(){
        return "Iron Golem";
    }

    public function spawnTo(Player $player){
        $pk = $this->addEntityDataPacket($player);
        $pk->type = IronGolem::NETWORK_ID;

        $player->dataPacket($pk);
        parent::spawnTo($player);
    }

    public function getDrops(){
        return [
            drp::get(ItemItem::IRON_INGOT, 0, mt_rand(3, 5)),
            drp::get(ItemItem::POPPY, 0, mt_rand(0, 2))
        ];
    }

}