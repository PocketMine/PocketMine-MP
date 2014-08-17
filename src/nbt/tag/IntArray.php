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

class IntArray extends NamedTag{

	public function getType(){
		return NBT_new::TAG_IntArray;
	}

	public function read(NBT_new $NBT_new){
		$this->value = [];
		$size = $NBT_new->getInt();
		for($i = 0; $i < $size and !$NBT_new->feof(); ++$i){
			$this->value[] = $NBT_new->getInt();
		}
	}

	public function write(NBT_new $NBT_new){
		$NBT_new->putInt(count($this->value));
		foreach($this->value as $v){
			$NBT_new->putInt($v);
		}
	}
}