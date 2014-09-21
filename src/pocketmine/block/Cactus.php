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
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\Player;
use pocketmine\Server;

class Cactus extends Transparent{
    public function __construct($meta = 0){
        parent::__construct(self::CACTUS, $meta, "Cactus");
        $this->isFullBlock = false;
        $this->hardness = 2;
    }

    public function getBoundingBox(){
        return new AxisAlignedBB(
            $this->x + 0.0625,
            $this->y,
            $this->z + 0.0625,
            $this->x + 0.9375,
            $this->y + 1,
            $this->z + 0.9375
        );
    }

    public function onEntityCollide(Entity $entity){
        $ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_CONTACT, 1);
        Server::getInstance()->getPluginManager()->callEvent($ev);
        if(!$ev->isCancelled()){
            $entity->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function onUpdate($type){
        if($type === Level::BLOCK_UPDATE_NORMAL){
            $down = $this->getSide(0);
            if($down->getID() !== self::SAND and $down->getID() !== self::CACTUS){ //Replace with common break method
                $this->getLevel()->setBlock($this, new Air(), false);
                $this->getLevel()->dropItem($this, Item::get($this->id));

                return;
            }else{
                for($side = 2; $side <= 5; ++$side){
                    $b = $this->getSide($side);
                    if(!$b->isFlowable){
                        $this->getLevel()->setBlock($this, new Air(), false);
                        $this->getLevel()->dropItem($this, Item::get($this->id));

                        return;
                    }
                }
            }
        }elseif($type === Level::BLOCK_UPDATE_RANDOM){
            if($this->getSide(0)->getID() !== self::CACTUS){
                if($this->meta == 0x0F){
                    for($y = 1; $y < 3; ++$y){
                        $b = $this->getLevel()->getBlock(new Vector3($this->x, $this->y + $y, $this->z));
                        if($b->getID() === self::AIR){
                            $this->getLevel()->setBlock($b, new Cactus(), true);
                            break;
                        }
                    }
                    $this->meta = 0;
                    $this->getLevel()->setBlock($this, $this);
                }else{
                    ++$this->meta;
                    $this->getLevel()->setBlock($this, $this);
                }

                return;
            }
        }

        return false;
    }

    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
        $down = $this->getSide(0);
        if($down->getID() === self::SAND or $down->getID() === self::CACTUS){
            $block0 = $this->getSide(2);
            $block1 = $this->getSide(3);
            $block2 = $this->getSide(4);
            $block3 = $this->getSide(5);
            if($block0->isTransparent === true and $block1->isTransparent === true and $block2->isTransparent === true and $block3->isTransparent === true){
                $this->getLevel()->setBlock($this, $this, true);

                return true;
            }
        }

        return false;
    }

    public function getDrops(Item $item){
        return [
            [$this->id, 0, 1],
        ];
    }
}