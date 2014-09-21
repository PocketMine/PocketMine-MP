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

use pocketmine\entity\FallingBlock;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\Player;

class Fallable extends Solid{

    public $hasPhysics = true;

    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
        $ret = $this->getLevel()->setBlock($this, $this, true, true);

        return $ret;
    }

    public function onUpdate($type){
        if($this->hasPhysics === true and $type === Level::BLOCK_UPDATE_NORMAL){
            $down = $this->getSide(0);
            if($down->getID() === self::AIR or ($down instanceof Liquid)){
                $fall = new FallingBlock($this->getLevel()->getChunkAt($this->x >> 4, $this->z >> 4), new Compound("", [
                    "Pos" => new Enum("Pos", [
                        new Double("", $this->x + 0.5),
                        new Double("", $this->y + 0.5),
                        new Double("", $this->z + 0.5)
                    ]),
                    //TODO: add random motion with physics
                    "Motion" => new Enum("Motion", [
                        new Double("", 0),
                        new Double("", 0),
                        new Double("", 0)
                    ]),
                    "Rotation" => new Enum("Rotation", [
                        new Float("", 0),
                        new Float("", 0)
                    ]),
                    "Tile" => new Byte("Tile", $this->getID())
                ]));

                $fall->spawnToAll();
            }

            return false;
        }
    }
}