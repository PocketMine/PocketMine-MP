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

namespace pocketmine\level\generator;


use pocketmine\level\format\FullChunk;

use pocketmine\level\Level;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;


class LightPopulationTask extends AsyncTask{

	public $levelId;
	public $chunk;
	public $chunkClass;

	public function __construct(Level $level, FullChunk $chunk){
		$this->levelId = $level->getId();
		$this->chunk = $chunk->toFastBinary();
		$this->chunkClass = get_class($chunk);
	}

	public function onRun(){
		/** @var FullChunk $chunk */
		$chunk = $this->chunkClass;
		$chunk = $chunk::fromFastBinary($this->chunk);
		if($chunk === null){
			//TODO error
			return;
		}

		$chunk->recalculateHeightMap();
		$chunk->populateSkyLight();
		$chunk->setLightPopulated();

		$this->chunk = $chunk->toFastBinary();
	}

	public function onCompletion(Server $server){
		$level = $server->getLevel($this->levelId);
		if($level !== null){
			/** @var FullChunk $chunk */
			$chunk = $this->chunkClass;
			$chunk = $chunk::fromFastBinary($this->chunk, $level->getProvider());
			if($chunk === null){
				//TODO error
				return;
			}
			$level->generateChunkCallback($chunk->getX(), $chunk->getZ(), $chunk);
		}
	}
}
