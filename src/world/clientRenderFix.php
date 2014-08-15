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
    private $server, $startingFromX, $startingFromZ, $sideLength = 1, $doing = 0, $nUpdated = 0,$sideIndex = 0, $spiralDone = false,$startChunkX, $startChunkZ;

    public function __construct($player) {
        if(!$player instanceof Player) return;
        $this->server = ServerAPI::request();
        $this->player = $player;
        $this->startingFromX = round($player->entity->x,0);
        $this->startChunkX = $this->block2Chunk( $this->startingFromX );
        $this->lowerLeftChunkX = $this->startChunkX;
        $this->startingFromZ = round($player->entity->z,0);
        $this->startChunkZ = $this->block2Chunk( $this->startingFromZ);
        $this->lowerLeftChunkZ = $this->startChunkZ;
        $this->server->schedule(3,array($this,"updateSpiral"),array(),true);
        // Doing  0 = bottom, 1 = left side, 2 = top, 3 = right side.
    }

    private function block2Chunk($inputBlock) {
        $output = intval( $inputBlock / 16 );
        return $output;
    }
    private function chunk2MidBlock($inputChunk) {
        $output = $inputChunk * 16 + 8;
        return $output;
    }

    // Given x and y chunks, see if these chunks lie within the world.
    // If they do, send out the packet and increase our nupdated.  Otherwise, return false.
    // Also, check to see if we are done.
    private function try2Update($xChunk, $zChunk) {
        if($xChunk < 0) return false;
        if($xChunk > 15) return false;
        if($zChunk < 0) return false;
        if($zChunk > 15) return false;
        $this->mkPacket($this->chunk2MidBlock($xChunk), $this->chunk2MidBlock($zChunk));
        $this->nUpdated++;
        if($this->nUpdated > 255) {
            $this->spiralDone = true;
        }

        return true;
    }

    // Send a spiral, which is really a box that grows around the starting chunk x, z.
    // This function should be called set up for whatever chunk it expects to try next.
    // For each growing box it sends the bottom, left side, top, right side.
    public function updateSpiral() {
        if(!$this->player instanceof Player) return false;
        $sentPacket = false;
        $maxSideIndex = $this->sideLength - 1;

        // Keep try to send chunks until we succeed in sending one.
        while(!$sentPacket) {
            // Prevent problem is updating from locking up server.  Should never happen:
            if ($this->sideLength > 32) return false;

            switch($this->doing) {
                case 0:   // Sending bottom.  Send index 0 to sideLength;
                    $sentPacket = $this->try2Update($this->lowerLeftChunkX + $this->sideIndex, $this->lowerLeftChunkZ);
                    // Look for special case of first chunk sent.  If so, move the lowerLeft down and left one.
                    if($this->sideLength === 1) {
                        //echo("starting x z: ".$this->startingFromX." ".$this->startingFromZ."   Chunk x z ".$this->startChunkX." ".$this->startChunkZ."\n");
                        $this->lowerLeftChunkX--;
                        $this->lowerLeftChunkZ--;
                        $this->sideLength += 2;
                        $this->sideIndex = 0;
                        $this->doing = 0;
                        break;
                    }
                    // Check to see if we are done sending bottom:
                    $this->sideIndex++;
                    if($this->sideIndex > $maxSideIndex) {
                        $this->doing = 1; // Set up to start sending left side.
                        $this->sideIndex = 1; // start with index 1, not zero.
                    }
                break;
                case 1:   // Sending left side
                    $sentPacket = $this->try2Update($this->lowerLeftChunkX, $this->lowerLeftChunkZ  + $this->sideIndex);
                    // Done sending left?
                    $this->sideIndex++;
                    if($this->sideIndex >= $this->sideLength) {
                        $this->sideIndex = 1;
                        $this->doing = 2;
                    }
                break;
                case 2:   // Sending top
                    $sentPacket = $this->try2Update($this->lowerLeftChunkX + $this->sideIndex, $this->lowerLeftChunkZ  + $maxSideIndex);
                    $this->sideIndex++;
                    // Done sending top?
                    if($this->sideIndex >= $this->sideLength) {
                        $this->sideIndex = 1;
                        $this->doing = 3;
                    }
                break;
                case 3:   // Sending right
                    $sentPacket = $this->try2Update( $this->lowerLeftChunkX + $maxSideIndex, $this->lowerLeftChunkZ  + $this->sideIndex);
                    $this->sideIndex++;
                    // Done sending top?  If so, move the lower left down and left a chunk, increase sideLength by 2.
                    if ($this->sideIndex >= $maxSideIndex) {
                        $this->lowerLeftChunkX--;
                        $this->lowerLeftChunkZ--;
                        $this->sideLength += 2;
                        $this->sideIndex = 0;
                        $this->doing = 0;
                    }
                break;
            }
        }

        if($this->spiralDone === false) return true;
        if($this->spiralDone === true) return false;
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