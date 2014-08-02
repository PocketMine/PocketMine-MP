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

namespace PocketMine\Level\Generator;

use PocketMine\Block\CoalOre as CoalOre;
use PocketMine\Block\DiamondOre as DiamondOre;
use PocketMine\Block\Dirt as Dirt;
use PocketMine\Block\GoldOre as GoldOre;
use PocketMine\Block\Gravel as Gravel;
use PocketMine\Block\IronOre as IronOre;
use PocketMine\Block\LapisOre as LapisOre;
use PocketMine\Block\RedstoneOre as RedstoneOre;
use PocketMine\Level\Generator\Noise\Simplex as Simplex;
use PocketMine\Level\Generator\Object\OreType as OreType;
use PocketMine\Level\Generator\Populator\Ore as Ore;
use PocketMine\Level\Generator\Populator\TallGrass as TallGrass;
use PocketMine\Level\Generator\Populator\Tree as Tree;
use PocketMine\Level\Level as Level;
use PocketMine\Math\Vector3 as Vector3;
use PocketMine\Utils\Random as Random;
use PocketMine;

class Normal extends Generator{

	private $populators = array();
	private $level;
	private $random;
	private $worldHeight = 65;
	private $waterHeight = 63;
	private $noiseHills;
	private $noisePatches;
	private $noisePatchesSmall;
	private $noiseBase;

	public function __construct(array $options = array()){

	}

	public function getName(){
		return "normal";
	}

	public function getSettings(){
		return array();
	}

	public function init(Level $level, Random $random){
		$this->level = $level;
		$this->random = $random;
		$this->random->setSeed($this->level->getSeed());
		$this->noiseHills = new Simplex($this->random, 3);
		$this->noisePatches = new Simplex($this->random, 2);
		$this->noisePatchesSmall = new Simplex($this->random, 2);
		$this->noiseBase = new Simplex($this->random, 16);


		$ores = new Ore();
		$ores->setOreTypes(array(
			new OreType(new CoalOre(), 20, 16, 0, 128),
			new OreType(New IronOre(), 20, 8, 0, 64),
			new OreType(new RedstoneOre(), 8, 7, 0, 16),
			new OreType(new LapisOre(), 1, 6, 0, 32),
			new OreType(new GoldOre(), 2, 8, 0, 32),
			new OreType(new DiamondOre(), 1, 7, 0, 16),
			new OreType(new Dirt(), 20, 32, 0, 128),
			new OreType(new Gravel(), 10, 16, 0, 128),
		));
		$this->populators[] = $ores;

		$trees = new Tree();
		$trees->setBaseAmount(3);
		$trees->setRandomAmount(0);
		$this->populators[] = $trees;

		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(5);
		$tallGrass->setRandomAmount(0);
		$this->populators[] = $tallGrass;
	}

	public function generateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		$hills = array();
		$patches = array();
		$patchesSmall = array();
		$base = array();
		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$i = ($z << 4) + $x;
				$hills[$i] = $this->noiseHills->noise2D($x + ($chunkX << 4), $z + ($chunkZ << 4), 0.11, 12, true);
				$patches[$i] = $this->noisePatches->noise2D($x + ($chunkX << 4), $z + ($chunkZ << 4), 0.03, 16, true);
				$patchesSmall[$i] = $this->noisePatchesSmall->noise2D($x + ($chunkX << 4), $z + ($chunkZ << 4), 0.5, 4, true);
				$base[$i] = $this->noiseBase->noise2D($x + ($chunkX << 4), $z + ($chunkZ << 4), 0.7, 16, true);

				if($base[$i] < 0){
					$base[$i] *= 0.5;
				}
			}
		}

		for($chunkY = 0; $chunkY < 8; ++$chunkY){
			$chunk = "";
			$startY = $chunkY << 4;
			$endY = $startY + 16;
			for($z = 0; $z < 16; ++$z){
				for($x = 0; $x < 16; ++$x){
					$i = ($z << 4) + $x;
					$height = $this->worldHeight + $hills[$i] * 14 + $base[$i] * 7;
					$height = (int) $height;

					for($y = $startY; $y < $endY; ++$y){
						$diff = $height - $y;
						if($y <= 4 and ($y === 0 or $this->random->nextFloat() < 0.75)){
							$chunk .= "\x07"; //bedrock
						} elseif($diff > 2){
							$chunk .= "\x01"; //stone
						} elseif($diff > 0){
							if($patches[$i] > 0.7){
								$chunk .= "\x01"; //stone
							} elseif($patches[$i] < -0.8){
								$chunk .= "\x0d"; //gravel
							} else{
								$chunk .= "\x03"; //dirt
							}
						} elseif($y <= $this->waterHeight){
							if(($this->waterHeight - $y) <= 1 and $diff === 0){
								$chunk .= "\x0c"; //sand
							} elseif($diff === 0){
								if($patchesSmall[$i] > 0.3){
									$chunk .= "\x0d"; //gravel
								} elseif($patchesSmall[$i] < -0.45){
									$chunk .= "\x0c"; //sand
								} else{
									$chunk .= "\x03"; //dirt
								}
							} else{
								$chunk .= "\x09"; //still_water
							}
						} elseif($diff === 0){
							if($patches[$i] > 0.7){
								$chunk .= "\x01"; //stone
							} elseif($patches[$i] < -0.8){
								$chunk .= "\x0d"; //gravel
							} else{
								$chunk .= "\x02"; //grass
							}
						} else{
							$chunk .= "\x00";
						}
					}
					$chunk .= "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
				}
			}
			$this->level->setMiniChunk($chunkX, $chunkZ, $chunkY, $chunk);
		}

	}

	public function populateChunk($chunkX, $chunkZ){
		$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
		foreach($this->populators as $populator){
			$this->random->setSeed(0xdeadbeef ^ ($chunkX << 8) ^ $chunkZ ^ $this->level->getSeed());
			$populator->populate($this->level, $chunkX, $chunkZ, $this->random);
		}
	}

	public function getSpawn(){
		return $this->level->getSafeSpawn(new Vector3(127.5, 128, 127.5));
	}

}

Generator::addGenerator(__NAMESPACE__ . "\\Normal", "normal");
Generator::addGenerator(__NAMESPACE__ . "\\Normal", "default");