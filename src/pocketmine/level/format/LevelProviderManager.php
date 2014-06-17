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

namespace pocketmine\level\format;

use pocketmine\Server;

abstract class LevelProviderManager{
	protected static $providers = [];

	/**
	 * @param Server $server
	 * @param string $class
	 *
	 * @throws \Exception
	 */
	public static function addProvider(Server $server, $class){
		if(!is_subclass_of($class, "pocketmine\\level\\format\\LevelProvider")){
			throw new \Exception("Class is not a subclass of LevelProvider");
		}
		self::$providers[strtolower($class)] = $class;
	}

	/**
	 * Returns a LevelProvider class for this path, or null
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function getProvider($path){
		foreach(self::$providers as $provider){
			/** @var $provider LevelProvider */
			if($provider::isValid($path)){
				return $provider;
			}
		}

		return null;
	}
}