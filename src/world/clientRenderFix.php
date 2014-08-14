<?php
/**
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
class clientRenderFix {
    public $player;
    private $server,$startingFromX,$startingFromZ,$done = -16;

    public function __construct($player) {
        if(!$player instanceof Player) return;
        $this->server = ServerAPI::request();
        $this->player = $player;
        $this->startingFromX = round($player->entity->x,0);
        $this->startingFromZ = round($player->entity->z,0);
        $this->server->schedule(3,array($this,"updateSquare"),array(),true);
    }

    public function updateSquare() {
        $this->done += 16;
        if($this->startingFromX + $this->done >= 255
            or $this->startingFromZ + $this->done >= 255
            or $this->startingFromX - $this->done <= 1
            or $this->startingFromZ - $this->done <= 1) {
            return false; // we've updated through the end of the world & can stop.
        }
        /*
         * | --------------------------- |
         * | 2            5            1 |
         * |                             |
         * |              N              |
         * |                             |
         * | 7       W    *    E       8 |
         * |                             |
         * |              S              |
         * |                             |
         * | 4            6            3 |
         * | --------------------------- |
         */

        //1:
        $this->mkPacket($this->startingFromX + $this->done,$this->startingFromZ + $this->done);
        if($this->done === 0) return true; // no need to update the same block 4 times

        //2:
        $this->mkPacket($this->startingFromX + $this->done,$this->startingFromZ - $this->done);
        //3:
        $this->mkPacket($this->startingFromX - $this->done,$this->startingFromZ + $this->done);
        //4:
        $this->mkPacket($this->startingFromX - $this->done,$this->startingFromZ - $this->done);
        //5:
        $this->mkPacket($this->startingFromX + $this->done,$this->startingFromZ);
        //6:
        $this->mkPacket($this->startingFromX - $this->done,$this->startingFromZ);
        //7:
        $this->mkPacket($this->startingFromZ - $this->done,$this->startingFromX);
        //8:
        $this->mkPacket($this->startingFromZ + $this->done,$this->startingFromX);
        return true;
    }

    public function mkPacket($x,$z) {
        $pk = new UpdateBlockPacket();
        $pk->x = $x;
        $pk->z = $z;
        $pk->y = 127;
        $pk->block = 0;
        $pk->meta = 0;
        $this->player->dataPacket($pk);
    }
}