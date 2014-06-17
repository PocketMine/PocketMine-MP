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

namespace pocketmine\level\format\anvil;

use pocketmine\level\format\generic\BaseLevelProvider;
use pocketmine\level\format\SimpleChunk;
use pocketmine\level\generator\Generator;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\ByteArray;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\Long;
use pocketmine\nbt\tag\String;
use pocketmine\Player;

class Anvil extends BaseLevelProvider{

	/** @var RegionLoader[] */
	protected $regions = [];

	/** @var Chunk[] */
	protected $chunks = [];


	public static function isValid($path){
		return file_exists($path . "/level.dat") and is_dir($path . "/region/");
	}

	public static function generate($path, $name, $seed, $generator, array $options = []){
		@mkdir($path, 0777, true);
		@mkdir($path . "/region", 0777);
		//TODO, add extra details
		$levelData = new Compound("Data", [
			"hardcore" => new Byte("hardcore", 0),
			"initialized" => new Byte("initialized", 1),
			"GameType" => new Int("GameType", 0),
			"generatorVersion" => new Int("generatorVersion", 1), //2 in MCPE
			"SpawnX" => new Int("SpawnX", 128),
			"SpawnY" => new Int("SpawnY", 70),
			"SpawnZ" => new Int("SpawnZ", 128),
			"version" => new Int("version", 19133),
			"DayTime" => new Int("DayTime", 0),
			"LastPlayed" => new Long("LastPlayed", microtime(true) * 1000),
			"RandomSeed" => new Long("RandomSeed", $seed),
			"SizeOnDisk" => new Long("SizeOnDisk", 0),
			"Time" => new Long("Time", 0),
			"generatorName" => new String("generatorName", Generator::getGeneratorName($generator)),
			"generatorOptions" => new String("generatorOptions", isset($options["preset"]) ? $options["preset"] : ""),
			"LevelName" => new String("LevelName", $name),
			"GameRules" => new Compound("GameRules", [])
		]);
		$nbt = new NBT(NBT::BIG_ENDIAN);
		$nbt->setData(new Compound(null, [
			"Data" => $levelData
		]));
		$buffer = $nbt->writeCompressed();
		@file_put_contents($path . "level.dat", $buffer);
	}

	public static function getRegionIndex($chunkX, $chunkZ, &$x, &$z){
		$x = $chunkX >> 5;
		$z = $chunkZ >> 5;
	}

	public function unloadChunks(){
		$this->chunks = [];
	}

	public function getGenerator(){
		return $this->levelData["generatorName"];
	}

	public function getGeneratorOptions(){
		return ["preset" => $this->levelData["generatorOptions"]];
	}

	public function getLoadedChunks(){
		return $this->chunks;
	}

	public function isChunkLoaded($x, $z){
		return isset($this->chunks[Level::chunkHash($x, $z)]);
	}

	public function saveChunks(){
		foreach($this->chunks as $chunk){
			$this->saveChunk($chunk->getX(), $chunk->getZ());
		}
	}

	public function loadChunk($chunkX, $chunkZ, $create = false){
		$index = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$index])){
			return true;
		}
		$regionX = $regionZ = null;
		self::getRegionIndex($chunkX, $chunkZ, $regionX, $regionZ);
		$this->loadRegion($regionX, $regionZ);
		$chunk = $this->getRegion($regionX, $regionZ)->readChunk($chunkX - $regionX * 32, $chunkZ - $regionZ * 32, $create); //generate empty chunk if not loaded

		if($chunk instanceof Chunk){
			$this->chunks[$index] = $chunk;
		}else{
			return false;
		}
	}

	public function unloadChunk($x, $z, $safe = true){
		if($safe === true and $this->isChunkLoaded($x, $z)){
			$chunk = $this->getChunk($x, $z);
			foreach($chunk->getEntities() as $entity){
				if($entity instanceof Player){
					return false;
				}
			}
		}

		unset($this->chunks[Level::chunkHash($x, $z)]);

		return true;
	}

	public function saveChunk($x, $z){
		if($this->isChunkLoaded($x, $z)){
			$this->getRegion($x >> 5, $z >> 5)->writeChunk($this->getChunk($x, $z));

			return true;
		}

		return false;
	}

	/**
	 * @param $x
	 * @param $z
	 *
	 * @return RegionLoader
	 */
	protected function getRegion($x, $z){
		$index = $x . ":" . $z;

		return isset($this->regions[$index]) ? $this->regions[$index] : null;
	}

	/**
	 * @param int  $chunkX
	 * @param int  $chunkZ
	 * @param bool $create
	 *
	 * @return Chunk
	 */
	public function getChunk($chunkX, $chunkZ, $create = false){
		$index = Level::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$index])){
			return $this->chunks[$index];
		}else{
			$this->loadChunk($chunkX, $chunkZ, $create);
			return isset($this->chunks[$index]) ? $this->chunks[$index] : null;
		}
	}

	public function setChunk($chunkX, $chunkZ, SimpleChunk $chunk){
		if($chunk->isGenerated() === false){
			$this->unloadChunk($chunkX, $chunkZ, false);
			$this->loadRegion($chunkX >> 4, $chunkZ >> 4);
			$region = $this->getRegion($chunkX >> 4, $chunkZ >> 4);
			$region->removeChunk($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32);
			$this->loadChunk($chunkX, $chunkZ);
		}else{
			$newChunk = $this->getChunk($chunkX, $chunkZ, true);
			for($y = 0; $y < 8; ++$y){
				$section = new ChunkSection(new Compound(null, [
					"Y" => new Byte("Y", $y),
					"Blocks" => new ByteArray("Blocks", $chunk->getSectionIds($y)),
					"Data" => new ByteArray("Data", $chunk->getSectionData($y)),
					"SkyLight" => new ByteArray("SkyLight", str_repeat("\xff", 2048)), //TODO
					"BlockLight" => new ByteArray("BlockLight", str_repeat("\x00", 2048)) //TODO
				]));
				$newChunk->setSection($y, $section);
			}
			if($chunk->isPopulated()){
				$newChunk->setPopulated(1);
			}
			$this->chunks[Level::chunkHash($chunkX, $chunkZ)] = $newChunk;
			$this->saveChunk($chunkX, $chunkZ);
		}
	}

	public function isChunkGenerated($chunkX, $chunkZ){
		if(($region = $this->getRegion($chunkX >> 5, $chunkZ >> 5)) instanceof RegionLoader){
			return $region->chunkExists($chunkX - $region->getX() * 32, $chunkZ - $region->getZ() * 32);
		}

		return false;
	}

	public function isChunkPopulated($chunkX, $chunkZ){
		$chunk = $this->getChunk($chunkX, $chunkZ);
		if($chunk instanceof Chunk){
			return $chunk->isPopulated();
		}else{
			return false;
		}
	}

	protected function loadRegion($x, $z){
		$index = $x . ":" . $z;
		if(isset($this->regions[$index])){
			return true;
		}

		$this->regions[$index] = new RegionLoader($this, $x, $z);

		return true;
	}

	public function close(){
		$this->unloadChunks();
		foreach($this->regions as $index => $region){
			$region->close();
			unset($this->regions[$index]);
		}
	}
}