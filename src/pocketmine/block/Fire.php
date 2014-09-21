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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Server;

class Fire extends Flowable{
    public function __construct($meta = 0){
        parent::__construct(self::FIRE, $meta, "Fire");
        $this->isReplaceable = true;
        $this->breakable = false;
        $this->isFullBlock = true;
        $this->hardness = 0;
    }

    public function getBoundingBox(){
        return null;
    }

    public function onEntityCollide(Entity $entity){
        $entity->setOnFire(8);
        $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_FIRE, 1);
        Server::getInstance()->getPluginManager()->callEvent($ev);
        if(!$ev->isCancelled()){
            $entity->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(Item $item){
        return [];
    }

    public function onUpdate($type){
        if($type === Level::BLOCK_UPDATE_NORMAL){
            for($s = 0; $s <= 5; ++$s){
                $side = $this->getSide($s);
                if($side->getID() !== self::AIR and !($side instanceof Liquid)){
                    return false;
                }
            }
            $this->getLevel()->setBlock($this, new Air(), true);

            return Level::BLOCK_UPDATE_NORMAL;
        }elseif($type === Level::BLOCK_UPDATE_RANDOM){
            if($this->getSide(0)->getID() !== self::NETHERRACK){
                $this->getLevel()->setBlock($this, new Air(), true);

                return Level::BLOCK_UPDATE_NORMAL;
            }
        }

        return false;
    }

}