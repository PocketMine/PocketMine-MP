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

namespace PocketMine\NBT\Tag;

use PocketMine;
use PocketMine\NBT\NBT as NBT;

class Compound extends NamedTag implements \ArrayAccess, \Iterator{

	public function __construct($name = "", $value = array()){
		$this->name = $name;
		$this->value = $value;
	}

	public function getType(){
		return NBT::TAG_Compound;
	}

	public function rewind(){
		reset($this->value);
	}

	public function current(){
		return current($this->value);
	}

	public function key(){
		return key($this->value);
	}

	public function next(){
		return next($this->value);
	}

	public function valid(){
		$key = key($this->value);

		return $key !== null and $key !== false;
	}

	public function offsetExists($name){
		return $this->__isset($name);
	}

	public function &offsetGet($name){
		return $this->__get($name);
	}

	public function offsetSet($name, $value){
		$this->__set($name, $value);
	}

	public function offsetUnset($name){
		$this->__unset($name);
	}

	public function &__get($name){
		$ret = isset($this->value[$name]) ? $this->value[$name] : false;
		if(!is_object($ret) or $ret instanceof \ArrayAccess){
			return $ret;
		} else{
			return $ret->getValue();
		}
	}

	public function __set($name, $value){
		if($value instanceof Tag){
			$this->value[$name] = $value;
		} elseif(isset($this->value[$name])){
			if($value instanceof NamedTag and $value->getName() !== "" and $value->getName() !== false){
				$this->value[$value->getName()]->setValue($value);
			} else{
				$this->value[$name]->setValue($value);
			}
		}
	}

	public function __isset($name){
		return isset($this->value[$name]);
	}

	public function __unset($name){
		unset($this->value[$name]);
	}

	public function read(NBT $nbt){
		$this->value = array();
		do{
			$tag = $nbt->readTag();
			if($tag instanceof NamedTag and $tag->getName() !== ""){
				$this->value[$tag->getName()] = $tag;
			} elseif(!($tag instanceof End)){
				$this->value[] = $tag;
			}
		} while(!($tag instanceof End) and !$nbt->feof());
	}

	public function write(NBT $nbt){
		foreach($this->value as $tag){
			if(!($tag instanceof End)){
				$nbt->writeTag($tag);
			}
		}
		$nbt->writeTag(new End);
	}
}