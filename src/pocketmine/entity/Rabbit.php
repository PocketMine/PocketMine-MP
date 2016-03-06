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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item as ItemItem;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;

class Rabbit extends Animal{
    const NETWORK_ID = 18;

    const TYPE_BROWN = 0;
    const TYPE_BLACK = 1;
    const TYPE_ALBINO = 2;
    const TYPE_SPOTTED = 3;
    const TYPE_SALT_PEPPER = 4;
    const TYPE_GOLDEN = 5;

    public $height = 0.5;
    public $width = 0.5;
    public $lenght = 0.5;

    public function initEntity(){
        $this->setMaxHealth(3);
        parent::initEntity();
        if(!isset($this->namedtag->Type)){
            $this->setType(mt_rand(0, 5));
        }
    }

    public function getName(){
        return "Rabbit";
    }

    public function spawnTo(Player $player){
        $pk = $this->addEntityDataPacket($player);
        $pk->type = Rabbit::NETWORK_ID;

        $player->dataPacket($pk);
        parent::spawnTo($player);
    }

    public function setType($type){
        $this->namedtag->Profession = new IntTag("Type", $type);
    }

    public function getType(){
        return $this->namedtag["Type"];
    }

    public function getDrops(){
        $drops = [ItemItem::get(ItemItem::RABBIT_HIDE, 0, mt_rand(0, 2))];

        if($this->getLastDamageCause() === EntityDamageEvent::CAUSE_FIRE){
            $drops[] = ItemItem::get(ItemItem::COOKED_RABBIT, 0, mt_rand(1, 2));
        }else{
            $drops[] = ItemItem::get(ItemItem::RAW_RABBIT, 0, mt_rand(1, 2));
        }

        return $drops;
    }


}