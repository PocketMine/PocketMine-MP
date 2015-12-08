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

namespace pocketmine\utils;

// TODO: Let InventoryType and Effect inherit this
abstract class ModelledEnum{
	/** @var static[] */
	private static $pool = [];

	/** @var int */
	private $id;
	/** @var string */
	private $name;

	protected static function addEnumEntry(ModelledEnum $entry){
		self::$pool[$entry->getId()] = $entry;
	}
	public static function get($id){
		return isset(self::$pool[$id]) ? clone self::$pool[$id] : null;
	}

	public static function getByName($name){
		foreach(self::$pool as $element){
			if($element->getName() === $name){
				return clone $element;
			}
		}
		return null;
	}

	protected function __construct($id, $name){
		$this->id = $id;
		$this->name = $name;
	}

	public final function getId(){
		return $this->id;
	}

	public final function getName(){
		return $this->name;
	}
}
