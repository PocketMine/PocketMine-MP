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

class Level{
	public $entities, $tiles, $blockUpdates, $nextSave, $players = array(), $level;
	private $time, $startCheck, $startTime, $server, $name, $usedChunks, $changedBlocks, $changedCount, $stopTime,$ochunkCache;
	
	public function __construct(PMFLevel $level, Config $entities, Config $tiles, Config $blockUpdates, $name){
		$this->server = ServerAPI::request();
		$this->level = $level;
		$this->level->level = $this;
		$this->entities = $entities;
		$this->tiles = $tiles;
		$this->blockUpdates = $blockUpdates;
		$this->startTime = $this->time = (int) $this->level->getData("time");
		$this->nextSave = $this->startCheck = microtime(true);
		$this->nextSave += 90;
		$this->stopTime = false;
		$this->server->schedule(15, array($this, "checkThings"), array(), true);
		$this->server->schedule(20 * 13, array($this, "checkTime"), array(), true);
		$this->name = $name;
		$this->usedChunks = array();
		$this->changedBlocks = array();
		$this->changedCount = array();

        $done = 0;
        if(FORCE_OCHUNK_GEN_ON_LEVEL_LOAD) {
            for($x=0;$x<=15;$x++) {
                for($z=0;$z<=15;$z++) {
                    $this->getOptimizedChunk($x,$z,true,true);
                    $done++;
                    $percent = round(($done / 256) * 100);
                    echo "Optimizing level: ".$this->name."... $percent%  \r";
                }
            }
        }
	}
	
	public function close(){
		$this->__destruct();
	}
	
	public function useChunk($X, $Z, Player $player){
		if(!isset($this->usedChunks[$X.".".$Z])){
			$this->usedChunks[$X.".".$Z] = array();
		}
		$this->usedChunks[$X.".".$Z][$player->CID] = true;
		if(isset($this->level)){
			$this->level->loadChunk($X, $Z);
		}
	}
	
	public function freeAllChunks(Player $player){
		foreach($this->usedChunks as $i => $c){
			unset($this->usedChunks[$i][$player->CID]);
		}
	}

	public function freeChunk($X, $Z, Player $player){
		unset($this->usedChunks[$X.".".$Z][$player->CID]);
	}
	
	public function checkTime(){
		if(!isset($this->level)){
			return false;
		}
		$now = microtime(true);
		if($this->stopTime == true){

		}else{
			$time = $this->startTime + ($now - $this->startCheck) * 20;
		}
		if($this->server->api->dhandle("time.change", array("level" => $this, "time" => $time)) !== false){
			$this->time = $time;
			
			$pk = new SetTimePacket;
			$pk->time = (int) $this->time;
			$pk->started = $this->stopTime == false;
			$this->server->api->player->broadcastPacket($this->players, $pk);
		}
	}
	
	public function checkThings(){
		if(!isset($this->level)){
			return false;
		}
		$now = microtime(true);
		$this->players = $this->server->api->player->getAll($this);
		
		if(count($this->changedCount) > 0){
			arsort($this->changedCount);
			$resendChunks = array();
			foreach($this->changedCount as $index => $count){
				if($count < 582){//Optimal value, calculated using the relation between minichunks and single packets
					break;
				}
				foreach($this->players as $p){
					unset($p->chunksLoaded[$index]);
				}
				unset($this->changedBlocks[$index]);
			}
			$this->changedCount = array();

			if(count($this->changedBlocks) > 0){
				foreach($this->changedBlocks as $blocks){
					foreach($blocks as $b){
						$pk = new UpdateBlockPacket;
						$pk->x = $b->x;
						$pk->y = $b->y;
						$pk->z = $b->z;
						$pk->block = $b->getID();
						$pk->meta = $b->getMetadata();
						$this->server->api->player->broadcastPacket($this->players, $pk);
					}
				}
				$this->changedBlocks = array();
			}
		}
		
		if($this->nextSave < $now){
			foreach($this->usedChunks as $i => $c){
				if(count($c) === 0){
					unset($this->usedChunks[$i]);
					$X = explode(".", $i);
					$Z = array_pop($X);
					$this->level->unloadChunk((int) array_pop($X), (int) $Z, $this->server->saveEnabled);
				}
			}
			$this->save(false, false);
		}
	}
	
	public function __destruct(){
		if(isset($this->level)){
			$this->save(false, false);
			$this->level->close();
			unset($this->level);
		}
	}
	
	public function save($force = false, $extra = true){
		if(!isset($this->level)){
			return false;
		}
		if($this->server->saveEnabled === false and $force === false){
			return;
		}
		
		if($extra !== false){
			$entities = array();
			foreach($this->server->api->entity->getAll($this) as $entity){
				if($entity->class === ENTITY_MOB){
					$entities[] = array(
						"id" => $entity->type,
						"Color" => @$entity->data["Color"],
						"Sheared" => @$entity->data["Sheared"],
						"Health" => $entity->health,
						"Pos" => array(
							0 => $entity->x,
							1 => $entity->y,
							2 => $entity->z,
						),
						"Rotation" => array(
							0 => $entity->yaw,
							1 => $entity->pitch,
						),
					);
				}elseif($entity->class === ENTITY_OBJECT){
					if($entity->type === OBJECT_PAINTING){
						$entities[] = array(
							"id" => $entity->type,
							"TileX" => $entity->x,
							"TileY" => $entity->y,
							"TileZ" => $entity->z,
							"Health" => $entity->health,
							"Motive" => $entity->data["Motive"],
							"Pos" => array(
								0 => $entity->x,
								1 => $entity->y,
								2 => $entity->z,
							),
							"Rotation" => array(
								0 => $entity->yaw,
								1 => $entity->pitch,
							),
						);
					}else{
						$entities[] = array(
							"id" => $entity->type,
							"Health" => $entity->health,
							"Pos" => array(
								0 => $entity->x,
								1 => $entity->y,
								2 => $entity->z,
							),
							"Rotation" => array(
								0 => $entity->yaw,
								1 => $entity->pitch,
							),
						);
					}
				}elseif($entity->class === ENTITY_FALLING){
					$entities[] = array(
						"id" => $entity->type,
						"Health" => $entity->health,
						"Tile" => $entity->data["Tile"],
						"Pos" => array(
							0 => $entity->x,
							1 => $entity->y,
							2 => $entity->z,
						),
						"Rotation" => array(
							0 => 0,
							1 => 0,
						),
					);
				}elseif($entity->class === ENTITY_ITEM){
					$entities[] = array(
						"id" => 64,
						"Item" => array(
							"id" => $entity->type,
							"Damage" => $entity->meta,
							"Count" => $entity->stack,
						),
						"Health" => $entity->health,
						"Pos" => array(
							0 => $entity->x,
							1 => $entity->y,
							2 => $entity->z,
						),
						"Rotation" => array(
							0 => 0,
							1 => 0,
						),
					);
				}
			}
			$this->entities->setAll($entities);
			$this->entities->save();
			$tiles = array();
			foreach($this->server->api->tile->getAll($this) as $tile){		
				$tiles[] = $tile->data;
			}
			$this->tiles->setAll($tiles);
			$this->tiles->save();
			
			$blockUpdates = array();
			$updates = $this->server->query("SELECT x,y,z,type,delay FROM blockUpdates WHERE level = '".$this->getName()."';");
			if($updates !== false and $updates !== true){
				$timeu = microtime(true);
				while(($bupdate = $updates->fetchArray(SQLITE3_ASSOC)) !== false){
					$bupdate["delay"] = max(1, ($bupdate["delay"] - $timeu) * 20);					
					$blockUpdates[] = $bupdate;
				}
			}

			$this->blockUpdates->setAll($blockUpdates);
			$this->blockUpdates->save();
		
		}
		
		$this->level->setData("time", (int) $this->time);
		$this->level->doSaveRound();
		$this->level->saveData();
		$this->nextSave = microtime(true) + 45;
	}
	
	public function getBlockRaw(Vector3 $pos){
		$b = $this->level->getBlock($pos->x, $pos->y, $pos->z);
		return BlockAPI::get($b[0], $b[1], new Position($pos->x, $pos->y, $pos->z, $this));
	}
	
	public function getBlock(Vector3 $pos){
		if(!isset($this->level) or ($pos instanceof Position) and $pos->level !== $this){
			return false;
		}
		$b = $this->level->getBlock($pos->x, $pos->y, $pos->z);
		return BlockAPI::get($b[0], $b[1], new Position($pos->x, $pos->y, $pos->z, $this));
	}
	
	public function setBlockRaw(Vector3 $pos, Block $block, $direct = true, $send = true){
		if(($ret = $this->level->setBlock($pos->x, $pos->y, $pos->z, $block->getID(), $block->getMetadata())) === true and $send !== false){
			if($direct === true){
				$pk = new UpdateBlockPacket;
				$pk->x = $pos->x;
				$pk->y = $pos->y;
				$pk->z = $pos->z;
				$pk->block = $block->getID();
				$pk->meta = $block->getMetadata();
				$this->server->api->player->broadcastPacket($this->players, $pk);
			}elseif($direct === false){
				if(!($pos instanceof Position)){
					$pos = new Position($pos->x, $pos->y, $pos->z, $this);
				}
				$block->position($pos);
				$i = ($pos->x >> 4).":".($pos->y >> 4).":".($pos->z >> 4);
				if(ADVANCED_CACHE == true){
					Cache::remove("world:{$this->name}:".($pos->x >> 4).":".($pos->z >> 4));
				}
				if(!isset($this->changedBlocks[$i])){
					$this->changedBlocks[$i] = array();
					$this->changedCount[$i] = 0;
				}
				$this->changedBlocks[$i][] = clone $block;
				++$this->changedCount[$i];
			}
		}
		return $ret;
	}
	
	public function setBlock(Vector3 $pos, Block $block, $update = true, $tiles = false, $direct = false){
		if(!isset($this->level) or (($pos instanceof Position) and $pos->level !== $this) or $pos->x < 0 or $pos->y < 0 or $pos->z < 0){
			return false;
		}

		$ret = $this->level->setBlock($pos->x, $pos->y, $pos->z, $block->getID(), $block->getMetadata());
		if($ret === true){
			if(!($pos instanceof Position)){
				$pos = new Position($pos->x, $pos->y, $pos->z, $this);
			}
			$block->position($pos);

			if($direct === true){
				$pk = new UpdateBlockPacket;
				$pk->x = $pos->x;
				$pk->y = $pos->y;
				$pk->z = $pos->z;
				$pk->block = $block->getID();
				$pk->meta = $block->getMetadata();
				$this->server->api->player->broadcastPacket($this->players, $pk);
			}else{
				$i = ($pos->x >> 4).":".($pos->y >> 4).":".($pos->z >> 4);
				if(!isset($this->changedBlocks[$i])){
					$this->changedBlocks[$i] = array();
					$this->changedCount[$i] = 0;
				}
				if(ADVANCED_CACHE == true){
					Cache::remove("world:{$this->name}:".($pos->x >> 4).":".($pos->z >> 4));
				}
				$this->changedBlocks[$i][] = clone $block;
				++$this->changedCount[$i];
			}

			if($update === true){				
				$this->server->api->block->blockUpdateAround($pos, BLOCK_UPDATE_NORMAL, 1);
				$this->server->api->entity->updateRadius($pos, 3);
			}
			if($tiles === true){
				if(($t = $this->server->api->tile->get($pos)) !== false){
					$t->close();
				}
			}
		}
		return $ret;
	}
	
	public function getMiniChunk($X, $Z, $Y){
		if(!isset($this->level)){
			return false;
		}
		return $this->level->getMiniChunk($X, $Z, $Y);
	}
	
	public function setMiniChunk($X, $Z, $Y, $data){
		if(!isset($this->level)){
			return false;
		}
		$this->changedCount[$X.":".$Y.":".$Z] = 4096;
		if(ADVANCED_CACHE == true){
			Cache::remove("world:{$this->name}:$X:$Z");
		}
		return $this->level->setMiniChunk($X, $Z, $Y, $data);
	}
	
	public function loadChunk($X, $Z){
		if(!isset($this->level)){
			return false;
		}
		return $this->level->loadChunk($X, $Z);
	}
	
	public function unloadChunk($X, $Z){
		if(!isset($this->level)){
			return false;
		}
		Cache::remove("world:{$this->name}:$X:$Z");
		return $this->level->unloadChunk($X, $Z, $this->server->saveEnabled);
	}

	public function getOrderedChunk($X, $Z, $Yndex){
		if(!isset($this->level)){
			return false;
		}
		if(ADVANCED_CACHE == true and $Yndex == 0xff){
			$identifier = "world:{$this->name}:$X:$Z";
			if(($cache = Cache::get($identifier)) !== false){
				return $cache;
			}
		}
		
		
		$raw = array();
		for($Y = 0; $Y < 8; ++$Y){
			if(($Yndex & (1 << $Y)) > 0){
				$raw[$Y] = $this->level->getMiniChunk($X, $Z, $Y);
			}
		}
		
		$ordered = "";
		$flag = chr($Yndex);
		for($j = 0; $j < 256; ++$j){
			$ordered .= $flag;
			foreach($raw as $mini){
				$ordered .= substr($mini, $j << 5, 24); //16 + 8
			}
		}
		if(ADVANCED_CACHE == true and $Yndex == 0xff){
			Cache::add($identifier, $ordered, 60);
		}		
		return $ordered;
	}

	public function getOrderedFullChunk($X, $Z){
        //todo: make asynchronous
		if(!isset($this->level)){
			return false;
		}
		if(ADVANCED_CACHE == true){
			$identifier = "world:{$this->name}:$X:$Z";
			if(($cache = Cache::get($identifier)) !== false){
				return $cache;
			}
		}

		$orderedIds = "";
		$orderedData = "";
		$orderedSkyLight = str_repeat("\x00", 16*16*64);
		$orderedLight = str_repeat("\x00", 16*16*64);
		$orderedBiomeIds = str_repeat("\x01", 16*16); //all plains, according to PocketMine 1.4 source
		$orderedBiomeColors = str_repeat("\x00\x85\xb2\x4a", 256); // also PM 1.4
		$tileEntities = "";
		$this->level->loadChunk($X, $Z);
		for ($aX = 0; $aX < 16; $aX++) {
			for ($aZ = 0; $aZ < 16; $aZ++) {
				for ($y = 0; $y < 8; $y++) {
					$miniChunk = $this->level->getMiniChunk($X, $Z, $y);
					$miniChunkIndex = ($aX << 5) + ($aZ << 9);
					$orderedIds .= substr($miniChunk, $miniChunkIndex, 16);
					$miniChunkIndex += 2048; // "4096 + 2048 + 2048, Block Data, Meta, Light" - @shoghicp :D
					$orderedData .= substr($miniChunk, $miniChunkIndex, 8);
					//$miniChunkIndex += 2048;
					//$orderedLight .= substr($miniChunk, $miniChunkIndex, 8);
				}
			}
		}
		$orderedUncompressed = Utils::writeLInt($X) . Utils::writeLInt($Z) .
			$orderedIds . $orderedData . $orderedSkyLight . $orderedLight .
			$orderedBiomeIds . $orderedBiomeColors . $tileEntities;
		$ordered = zlib_encode($orderedUncompressed, ZLIB_ENCODING_DEFLATE, 1);
		if(ADVANCED_CACHE == true){
			Cache::add($identifier, $ordered, 60);
		}
		return $ordered;
	}

    public function getOptimizedChunk($X, $Z, $gen_only = false, $suppress = false) {
        if($X > 15 or $Z > 15) {
            return array("",""); // return empty string = client can't walk there
        }
        // TODO: Make conversion asynchronous. When it is async, make it return empty strings to the player in the meantime so they can't move
        // TODO: Recalculate in the background periodically on level save if blocks were broken and save is enabled, asynchronously & on server shutdown
        if(isset($this->ochunkCache[$X.",".$Z])) {
        } elseif(file_exists(FILE_PATH."/ochunks/".$this->name."-".$X."-".$Z)) {
            if($gen_only) return;
            $this->level->loadChunk($X,$Z);
            if(!LOAD_OCHUNKS_IN_RAM or MAX_OCHUNKS_PER_LEVEL > count($this->ochunkCache)) {
                $res = json_decode(file_get_contents(FILE_PATH."/ochunks/".$this->name."-".$X."-".$Z));
                return $res;
            } else {
                $this->ochunkCache[$X.",".$Z] = json_decode(file_get_contents(FILE_PATH."/ochunks/".$this->name."-".$X."-".$Z));
            }
        } else {
            $orderedIds = "";
            $orderedData = "";
	
            //$num_air = 0;
            $this->level->loadChunk($X,$Z);
            $max_x = ($X * 16) + 16;
            $max_z = ($Z * 16) + 16;
            for($send_x = 0; $send_x <= $max_x; $send_x++) {
                for($send_z = 0; $send_z <= $max_z; $send_z++) {
                    for($send_y = 0; $send_y <= 127; $send_y++) {
                        $block = $this->level->getBlock($send_x,$send_y,$send_z);
                        $orderedIds .= chr($block[0]);
                        $orderedData .= chr($block[1]);
                        /*
                        echo "Scanning block, block ID: " . $block[0] . " | ";
                        if($block[0] === 0){
                        	$num_air++;
                        }
                        */
                    }
                }
            }
            /*
            if($num_air >= 32768) {
                echo "Chunk $X:$Z is air, ignoring, airblocks: $num_air | ";
                $orderedData = "air";
                $orderedIds = "air";
            }
            */
            if (!file_exists(FILE_PATH."/ochunks")) {
                mkdir(FILE_PATH."/ochunks", 777, true);
            }
            file_put_contents(FILE_PATH."/ochunks/".$this->name."-".$X."-".$Z,json_encode(array($orderedIds,$orderedData)));

            $this->ochunkCache[$X.",".$Z] = array($orderedIds,$orderedData);
        }

        if($this->ochunkCache[$X.",".$Z] === array("air","air")) {
            return array(str_repeat(chr(0),32768));
        }
        return $this->ochunkCache[$X.",".$Z];
    }

	public function getOrderedMiniChunk($X, $Z, $Y){
		if(!isset($this->level)){
			return false;
		}
		$raw = $this->level->getMiniChunk($X, $Z, $Y);
		$ordered = "";
		$flag = chr(1 << $Y);
		for($j = 0; $j < 256; ++$j){
			$ordered .= $flag . substr($raw, $j << 5, 24); //16 + 8
		}
		return $ordered;
	}
	
	public function getSpawn(){
		if(!isset($this->level)){
			return false;
		}
		return new Position($this->level->getData("spawnX"), $this->level->getData("spawnY"), $this->level->getData("spawnZ"), $this);
	}
	
	public function getSafeSpawn($spawn = false){
		if($spawn === false){
			$spawn = $this->getSpawn();
		}
		if($spawn instanceof Vector3){
			$x = (int) round($spawn->x);
			$y = (int) round($spawn->y);
			$z = (int) round($spawn->z);
			for(; $y > 0; --$y){
				$v = new Vector3($x, $y, $z);
				$b = $this->getBlock($v->getSide(0));
				if($b === false){
					return $spawn;
				}elseif(!($b instanceof AirBlock)){
					break;
				}
			}
			for(; $y < 128; ++$y){
				$v = new Vector3($x, $y, $z);
				if($this->getBlock($v->getSide(1)) instanceof AirBlock){
					if($this->getBlock($v) instanceof AirBlock){
						return new Position($x, $y, $z, $this);
					}
				}else{
					++$y;
				}
			}
			return new Position($x, $y, $z, $this);
		}
		return false;
	}
	
	public function setSpawn(Vector3 $pos){
		if(!isset($this->level)){
			return false;
		}
		$this->level->setData("spawnX", $pos->x);
		$this->level->setData("spawnY", $pos->y);
		$this->level->setData("spawnZ", $pos->z);
	}
	
	public function getTime(){
		return (int) ($this->time);
	}
	
	public function getName(){
		return $this->name;//return $this->level->getData("name");
	}
	
	public function setTime($time){
		$this->startTime = $this->time = (int) $time;
		$this->startCheck = microtime(true);
		$this->checkTime();
	}
	
	public function stopTime(){
		$this->stopTime = true;
		$this->startCheck = 0;
		$this->checkTime();
	}
	
	public function startTime(){
		$this->stopTime = false;
		$this->startCheck = microtime(true);
		$this->checkTime();
	}
	
	public function getSeed(){
		if(!isset($this->level)){
			return false;
		}
		return (int) $this->level->getData("seed");
	}
	
	public function setSeed($seed){
		if(!isset($this->level)){
			return false;
		}
		$this->level->setData("seed", (int) $seed);
	}
	
	public function scheduleBlockUpdate(Position $pos, $delay, $type = BLOCK_UPDATE_SCHEDULED){
		if(!isset($this->level)){
			return false;
		}
		return $this->server->api->block->scheduleBlockUpdate($pos, $delay, $type);
	}
}
