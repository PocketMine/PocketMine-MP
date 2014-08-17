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

class Enum extends NamedTag implements \ArrayAccess, \Countable{

	private $tagType;

	public function __construct($name = "", $value = []){
		$this->name = $name;
		foreach($value as $k => $v){
			$this->{$k} = $v;
		}
	}

	public function &getValue(){
		$value = [];
		foreach($this as $k => $v){
			if($v instanceof Tag){
				$value[$k] = $v;
			}
		}
		return $value;
	}

	public function offsetExists($offset){
		return isset($this->{$offset});
	}

	public function offsetGet($offset){
		if($this->{$offset} instanceof Tag){
			if($this->{$offset} instanceof \ArrayAccess){
				return $this->{$offset};
			}else{
				return $this->{$offset}->getValue();
			}
		}

		return null;
	}

	public function offsetSet($offset, $value){
		if($value instanceof Tag){
			$this->{$offset} = $value;
		}elseif($this->{$offset} instanceof Tag){
			$this->{$offset}->setValue($value);
		}
	}

	public function offsetUnset($offset){
		unset($this->{$offset});
	}

	public function count($mode = COUNT_NORMAL){
		for($i = 0; true; $i++){
			if(!isset($this->{$i})){
				return $i;
			}
			if($mode === COUNT_RECURSIVE){
				if($this->{$i} instanceof \Countable){
					$i += count($this->{$i});
				}
			}
		}
	}

	public function getType(){
		return NBT_new::TAG_Enum;
	}

	public function setTagType($type){
		$this->tagType = $type;
	}

	public function getTagType(){
		return $this->tagType;
	}

	public function read(NBT_new $NBT_new){
		$this->value = [];
		$this->tagType = $NBT_new->getByte();
		$size = $NBT_new->getInt();
		for($i = 0; $i < $size and !$NBT_new->feof(); ++$i){
			switch($this->tagType){
				case NBT_new::TAG_Byte:
					$tag = new Byte(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_Short:
					$tag = new Short(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_Int:
					$tag = new Int(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_Long:
					$tag = new Long(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_Float:
					$tag = new Float(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_Double:
					$tag = new Double(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_ByteArray:
					$tag = new ByteArray(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_String:
					$tag = new String(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_Enum:
					$tag = new $this(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_Compound:
					$tag = new Compound(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
				case NBT_new::TAG_IntArray:
					$tag = new IntArray(false);
					$tag->read($NBT_new);
					$this->{$i} = $tag;
					break;
			}
		}
	}

	public function write(NBT_new $NBT_new){
		if(!isset($this->tagType)){
			foreach($this as $tag){
				if($tag instanceof Tag){
					if(!isset($id)){
						$id = $tag->getType();
					}elseif($id !== $tag->getType()){
						return false;
					}
				}
			}
			$this->tagType = @$id;
		}

		$NBT_new->putByte($this->tagType);

		/** @var Tag[] $tags */
		$tags = [];
		foreach($this as $tag){
			if($tag instanceof Tag){
				$tags[] = $tag;
			}
		}
		$NBT_new->putInt(count($tags));
		foreach($tags as $tag){
			$tag->write($NBT_new);
		}
	}
}
