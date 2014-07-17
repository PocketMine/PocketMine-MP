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

namespace pocketmine\level\format\generic;

use pocketmine\entity\DroppedItem;
use pocketmine\entity\Entity;
use pocketmine\level\format\Chunk;
use pocketmine\level\format\ChunkSection;
use pocketmine\level\format\LevelProvider;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\String;
use pocketmine\tile\Chest;
use pocketmine\tile\Furnace;
use pocketmine\tile\Sign;
use pocketmine\tile\Tile;
use pocketmine\utils\Binary;

abstract class BaseChunk implements Chunk{

	/** @var ChunkSection[] */
	protected $sections = [];

	/** @var Entity[] */
	protected $entities = [];

	/** @var Tile[] */
	protected $tiles = [];

	/** @var string */
	protected $biomeIds;

	/** @var int[256] */
	protected $biomeColors;

	/** @var \WeakRef<LevelProvider> */
	protected $level;

	protected $x;
	protected $z;

	/**
	 * @param LevelProvider  $level
	 * @param int            $x
	 * @param int            $z
	 * @param ChunkSection[] $sections
	 * @param string         $biomeIds
	 * @param int[]          $biomeColors
	 * @param Compound[]     $entities
	 * @param Compound[]     $tiles
	 *
	 * @throws \Exception
	 */
	protected function __construct(LevelProvider $level, $x, $z, array $sections, $biomeIds = null, array $biomeColors = [], array $entities = [], array $tiles = []){
		$this->level = new \WeakRef($level);
		$this->x = (int) $x;
		$this->z = (int) $z;
		foreach($sections as $Y => $section){
			if($section instanceof ChunkSection){
				$this->sections[$Y] = $section;
			}else{
				trigger_error("Received invalid ChunkSection instance", E_USER_ERROR);
				throw new \Exception("Received invalid ChunkSection instance");
			}

			if($Y >= self::SECTION_COUNT){
				throw new \Exception("Invalid amount of chunks");
			}
		}

		if(strlen($biomeIds) === 256){
			$this->biomeIds = $biomeIds;
		}else{
			$this->biomeIds = str_repeat("\x01", 256);
		}

		if(count($biomeColors) === 256){
			$this->biomeColors = $biomeColors;
		}else{
			$this->biomeColors = array_fill(0, 256, Binary::readInt("\x00\x85\xb2\x4a"));
		}

		$this->getLevel()->getLevel()->timings->syncChunkLoadEntitiesTimer->startTiming();
		foreach($entities as $nbt){
			if($nbt instanceof Compound){
				if(!isset($nbt->id)){
					continue;
				}

				if($nbt->id instanceof String){ //New format
					switch($nbt["id"]){
						case "Item":
							(new DroppedItem($this, $nbt))->spawnToAll();
							break;
					}
				}else{ //Old format

				}
			}
		}
		$this->getLevel()->getLevel()->timings->syncChunkLoadEntitiesTimer->stopTiming();

		$this->getLevel()->getLevel()->timings->syncChunkLoadTileEntitiesTimer->startTiming();
		foreach($tiles as $nbt){
			if($nbt instanceof Compound){
				if(!isset($nbt->id)){
					continue;
				}
				switch($nbt["id"]){
					case Tile::CHEST:
						new Chest($this, $nbt);
						break;
					case Tile::FURNACE:
						new Furnace($this, $nbt);
						break;
					case Tile::SIGN:
						new Sign($this, $nbt);
						break;
				}
			}
		}
		$this->getLevel()->getLevel()->timings->syncChunkLoadTileEntitiesTimer->stopTiming();
	}

	public function getX(){
		return $this->x;
	}

	public function getZ(){
		return $this->z;
	}

	/**
	 * @return LevelProvider
	 */
	public function getLevel(){
		return $this->level->valid() ? $this->level->get() : null;
	}

	public function getBlock($x, $y, $z, &$blockId, &$meta = null){
		$this->sections[$y >> 4]->getBlock($x, $y & 0x0f, $z, $blockId, $meta);
	}

	public function setBlock($x, $y, $z, $blockId = null, $meta = null){
		return $this->sections[$y >> 4]->setBlock($x, $y & 0x0f, $z, $blockId & 0xff, $meta & 0x0f);
	}

	public function getBlockId($x, $y, $z){
		return $this->sections[$y >> 4]->getBlockId($x, $y & 0x0f, $z);
	}

	public function setBlockId($x, $y, $z, $id){
		$this->sections[$y >> 4]->setBlockId($x, $y & 0x0f, $z, $id);
	}

	public function getBlockData($x, $y, $z){
		return $this->sections[$y >> 4]->getBlockData($x, $y & 0x0f, $z);
	}

	public function setBlockData($x, $y, $z, $data){
		$this->sections[$y >> 4]->setBlockData($x, $y & 0x0f, $z, $data);
	}

	public function getBlockSkyLight($x, $y, $z){
		return $this->sections[$y >> 4]->getBlockSkyLight($x, $y & 0x0f, $z);
	}

	public function setBlockSkyLight($x, $y, $z, $data){
		$this->sections[$y >> 4]->getBlockSkyLight($x, $y & 0x0f, $z, $data);
	}

	public function getBlockLight($x, $y, $z){
		return $this->sections[$y >> 4]->getBlockSkyLight($x, $y & 0x0f, $z);
	}

	public function setBlockLight($x, $y, $z, $data){
		$this->sections[$y >> 4]->getBlockSkyLight($x, $y & 0x0f, $z, $data);
	}

	public function getBiomeId($x, $z){
		return ord($this->biomeIds{($z << 4) + $x});
	}

	public function setBiomeId($x, $z, $biomeId){
		$this->biomeIds{($z << 4) + $x} = chr($biomeId);
	}

	public function getBiomeColor($x, $z){
		$color = $this->biomeColors[($z << 4) + $x] & 0xFFFFFF;
		return [$color >> 16, ($color >> 8) & 0xFF, $color & 0xFF];
	}

	public function setBiomeColor($x, $z, $R, $G, $B){
		$this->biomeColors[($z << 4) + $x] = 0 | (($R & 0xFF) << 16) | (($G & 0xFF) << 8) | ($B & 0xFF);
	}

	public function getHighestBlockAt($x, $z){
		for($Y = self::SECTION_COUNT - 1; $Y >= 0; --$Y){
			if(!$this->isSectionEmpty($Y)){
				$column = $this->sections[$Y]->getBlockIdColumn($x, $z);
				for($y = 15; $y >= 0; --$y){
					if($column{$y} !== "\x00"){
						return $y + ($Y << 4);
					}
				}
			}
		}

		return 0;
	}

	public function isSectionEmpty($fY){
		return $this->sections[(int) $fY] instanceof EmptyChunkSection;
	}

	public function getSection($fY){
		return $this->sections[(int) $fY];
	}

	public function setSection($fY, ChunkSection $section){
		if(substr_count($section->getIdArray(), "\x00") === 4096 and substr_count($section->getDataArray(), "\x00") === 2048){
			$this->sections[(int) $fY] = new EmptyChunkSection($fY);
		}else{
			$this->sections[(int) $fY] = $section;
		}
	}

	public function addEntity(Entity $entity){
		$this->entities[$entity->getID()] = $entity;
	}

	public function removeEntity(Entity $entity){
		unset($this->entities[$entity->getID()]);
	}

	public function addTile(Tile $tile){
		$this->tiles[$tile->getID()] = $tile;
	}

	public function removeTile(Tile $tile){
		unset($this->tiles[$tile->getID()]);
	}

	public function getEntities(){
		return $this->entities;
	}

	public function getTiles(){
		return $this->tiles;
	}

	public function isLoaded(){
		return $this->getLevel() === null ? false : $this->getLevel()->isChunkLoaded($this->getX(), $this->getZ());
	}

	public function load($generate = true){
		return $this->getLevel() === null ? false : $this->getLevel()->getChunk($this->getX(), $this->getZ(), true) instanceof Chunk;
	}

	public function unload($save = true, $safe = true){
		$level = $this->getLevel();
		if($level === null){
			return true;
		}
		if($save === true){
			$level->saveChunk($this->getX(), $this->getZ());
		}
		if($this->getLevel()->unloadChunk($this->getX(), $this->getZ(), $safe)){
			foreach($this->getEntities() as $entity){
				$entity->close();
			}
			foreach($this->getTiles() as $tile){
				$tile->close();
			}
		}
	}

	/**
	 * @return ChunkSection[]
	 */
	public function getSections(){
		return $this->sections;
	}

	public function getBiomeIdArray(){
		return $this->biomeIds;
	}

	public function getBiomeColorArray(){
		return $this->biomeColors;
	}

}