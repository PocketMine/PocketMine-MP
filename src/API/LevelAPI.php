<?php

/**
 *
 *  ____			_		_   __  __ _				  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___	  |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|	 |_|  |_|_| 
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

class LevelAPI{
	private $server, $levels, $default;
	public function __construct(){
		$this->server = ServerAPI::request();
		$this->levels = array();
	}
	
	public function get($name){
		if(isset($this->levels[$name])){
			return $this->levels[$name];
		}
		return false;
	}
	
	public function getDefault(){
		return $this->levels[$this->default];
	}

	public function init(){
		$this->server->api->console->register("seed", "[world]", array($this, "commandHandler"));
		$this->server->api->console->register("save-all", "", array($this, "commandHandler"));
		$this->server->api->console->register("save-on", "", array($this, "commandHandler"));
		$this->server->api->console->register("save-off", "", array($this, "commandHandler"));
		$this->default = $this->server->api->getProperty("level-name");
		if($this->loadLevel($this->default) === false){
			$this->generateLevel($this->default, $this->server->seed);
			$this->loadLevel($this->default);
		}
		$this->server->spawn = $this->getDefault()->getSafeSpawn();
	}
	
	public function commandHandler($cmd, $params, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "save-all":
				$save = $this->server->saveEnabled;
				$this->server->saveEnabled = true;
				$this->saveAll();
				$this->server->saveEnabled = $save;
				break;
			case "save-on":
				$this->server->saveEnabled = true;
				break;
			case "save-off":
				$this->server->saveEnabled = false;
				break;
			case "seed":
				if(!isset($params[0]) and ($issuer instanceof Player)){
					$output .= "Seed: ".$issuer->level->getSeed()."\n";
				}elseif(isset($params[0])){
					if(($lv = $this->server->api->level->get(trim(implode(" ", $params)))) !== false){
						$output .= "Seed: ".$lv->getSeed()."\n";
					}
				}else{
					$output .= "Seed: ".$this->server->api->level->getDefault()->getSeed()."\n";
				}
		}
		return $output;			
	}
	
	public function generateLevel($name, $seed = false, $generator = false){
		if($this->levelExists($name)){
			return false;
		}
		$options = array();
		if($this->server->api->getProperty("generator-settings") !== false and trim($this->server->api->getProperty("generator-settings")) != ""){
			$options["preset"] = $this->server->api->getProperty("generator-settings");
		}

		if($generator !== false and class_exists($generator)){
			$generator = new $generator($options);
		}else{
			if(strtoupper($this->server->api->getProperty("level-type")) == "FLAT"){
				$generator = new SuperflatGenerator($options);
			}else{
				$generator = new NormalGenerator($options);
			}
		}
		$gen = new WorldGenerator($generator, $name, $seed === false ? Utils::readInt(Utils::getRandomBytes(4, false)):(int) $seed);
		$gen->generate();
		$gen->close();
		return true;
	}
	
	public function levelExists($name){
		if($name === ""){
			return false;
		}
		$path = DATA_PATH."worlds/".$name."/";
		if($this->get($name) === false and !file_exists($path."level.pmf")){
			$level = new LevelImport($path);
			if($level->import() === false){
				return false;
			}
		}
		return true;
	}
	
	public function unloadLevel(Level $level, $force = false){
		$name = $level->getName();
		if($name === $this->default and $force !== true){
			return false;
		}
		console("[INFO] Unloading level \"".$name."\"");
		$level->nextSave = PHP_INT_MAX;
		$level->save();
		foreach($this->server->api->player->getAll($level) as $player){
			$player->teleport($this->server->spawn);
		}
		/*foreach($this->server->api->entity->getAll($level) as $entity){
			if($entity->class !== ENTITY_PLAYER){
				$entity->close();
			}
		}*/
		/*foreach($this->server->api->tile->getAll($level) as $tile){
			$tile->close();
		}*/
		$level->close();
		unset($this->levels[$name]);
		return true;
	}
	
	public function loadLevel($name){
		if($this->get($name) !== false){
			return true;
		}elseif($this->levelExists($name) === false){
			console("[NOTICE] Level \"".$name."\" not found");
			return false;
		}
		$path = DATA_PATH."worlds/".$name."/";
		console("[INFO] Preparing level \"".$name."\"");
		$level = new PMFLevel($path."level.pmf");
		if(!$level->isLoaded){
			console("[ERROR] Could not load level \"".$name."\"");
			return false;
		}
		$entities = new Config($path."entities.yml", Config::YAML);
		if(file_exists($path."tileEntities.yml")){
			@rename($path."tileEntities.yml", $path."tiles.yml");
		}
		$blockUpdates = new Config($path."bupdates.yml", Config::YAML);
		$this->levels[$name] = new Level($level, $name);
		foreach($entities->getAll() as $entity){
			if(!isset($entity["id"])){
				break;
			}
			if($entity["id"] === 64){ //Item Drop
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_ITEM, $entity["Item"]["id"], array(
					"meta" => $entity["Item"]["Damage"],
					"stack" => $entity["Item"]["Count"],
					"x" => $entity["Pos"][0],
					"y" => $entity["Pos"][1],
					"z" => $entity["Pos"][2],
					"yaw" => $entity["Rotation"][0],
					"pitch" => $entity["Rotation"][1],
				));
			}elseif($entity["id"] === FALLING_SAND){
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_FALLING, $entity["id"], $entity);
				$e->setPosition(new Vector3($entity["Pos"][0], $entity["Pos"][1], $entity["Pos"][2]), $entity["Rotation"][0], $entity["Rotation"][1]);
				$e->setHealth($entity["Health"]);
			}elseif($entity["id"] === OBJECT_PAINTING or $entity["id"] === OBJECT_ARROW){ //Painting
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_OBJECT, $entity["id"], $entity);
				$e->setPosition(new Vector3($entity["Pos"][0], $entity["Pos"][1], $entity["Pos"][2]), $entity["Rotation"][0], $entity["Rotation"][1]);
				$e->setHealth(1);
			}else{
				$e = $this->server->api->entity->add($this->levels[$name], ENTITY_MOB, $entity["id"], $entity);
				$e->setPosition(new Vector3($entity["Pos"][0], $entity["Pos"][1], $entity["Pos"][2]), $entity["Rotation"][0], $entity["Rotation"][1]);
				$e->setHealth($entity["Health"]);
			}
		}
			
		if(file_exists($path ."tiles.yml")){
			$tiles = new Config($path."tiles.yml", Config::YAML);
			foreach($tiles->getAll() as $tile){
				if(!isset($tile["id"])){
					continue;
				}
				$this->levels[$name]->loadChunk($tile["x"] >> 4, $tile["z"] >> 4);
			
				$nbt = new NBTTag_Compound(false, array());
				foreach($tile as $index => $data){
					switch($index){
						case "Items":
							$tag = new NBTTag_List("Items", array());
							$tag->setTagType(NBTTag::TAG_Compound);
							foreach($data as $slot => $fields){								
								$tag->{$slot} = new NBTTag_Compound(false, array(
									"Count" => new NBTTag_Byte("Count", $fields["Count"]),
									"Slot" => new NBTTag_Short("Slot", $fields["Slot"]),
									"Damage" => new NBTTag_Short("Damage", $fields["Damage"]),
									"id" => new NBTTag_String("id", $fields["id"])
								));
							}
							$nbt["Items"] = $tag;
							break;
							
						case "id":
						case "Text1":
						case "Text2":
						case "Text3":
						case "Text4":
							$nbt[$index] = new NBTTag_String($index, $data);
							break;
							
						case "x":
						case "y":
						case "z":
						case "pairx":
						case "pairz":
							$nbt[$index] = new NBTTag_Int($index, $data);
							break;
							
						case "BurnTime":
						case "CookTime":
						case "MaxTime":
							$nbt[$index] = new NBTTag_Short($index, $data);
							break;
					}
				}
				switch($tile["id"]){
					case Tile::FURNACE:
						new FurnaceTile($this->levels[$name], $nbt);
						break;
					case Tile::CHEST:
						new ChestTile($this->levels[$name], $nbt);
						break;
					case Tile::SIGN:
						new SignTile($this->levels[$name], $nbt);
						break;
				}
			}
			unlink($path ."tiles.yml");
			$this->levels[$name]->save(true, true);
		}
		
		foreach($blockUpdates->getAll() as $bupdate){
			$this->server->api->block->scheduleBlockUpdate(new Position((int) $bupdate["x"],(int) $bupdate["y"],(int) $bupdate["z"], $this->levels[$name]), (float) $bupdate["delay"], (int) $bupdate["type"]);
		}
		return true;
	}

	public function handle($data, $event){
		switch($event){
		}
	}
	
	public function saveAll(){
		foreach($this->levels as $level){
			$level->save();
		}
	}
	
	public function __destruct(){
		$this->saveAll();
		foreach($this->levels as $level){
			$this->unloadLevel($level, true);
		}
	}

	public function getSpawn(){
		return $this->server->spawn;
	}
	
	public function getAll(){
		return $this->levels;
	}
	
}
