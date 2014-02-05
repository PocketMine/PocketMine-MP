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

class ProjectionAPI{
	private $list;
	public function __construct(){
		$this->server=ServerAPI::request();
	}
	public function init(){
		$this->server->addHandler("projectile.tick", array($this, "eventHandler"));
	}
	public function get($param){
		foreach($this->list as $id => $p){
			if(($p instanceof Position) and $p->x === $param->x and $p->y === $param->y and $p->z === $param->z and $p->level->getName() === $param->level->getName())
				return $p;
			elseif(is_numeric((int)$param) and (int)$param === $id){
				return $p;
			}
		}
	}
	public function add(Projection $p){
		$this->list[] = $p;
		return count($this->list) - 1;
	}
	public function remove($id){ // call this on despawn, not pause
		$this->list[$id] = false;
	}
	public function eventHandler(&$data, $event){
		switch($event){
			case "projectile.tick":
				if($data["newpos"]->y < 0){
					$this->remove($data["projectile"]->id);
					return false;
				}
				elseif(($id = $data["position"]->level->getBlock($data["position"])->getID()) !== 0 and !(8 <= $id and $id<=11)) {
					$data["projection"]->pause();
					return false;
				}
				break;
		}
	}
}
