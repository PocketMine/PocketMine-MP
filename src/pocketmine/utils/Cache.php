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

/**
 * @deprecated
 */
class Cache{
	public static $cached = [];

	/**
	 * Adds something to the cache
	 *
	 * @param string    $identifier
	 * @param mixed     $blob
	 * @param float|int $minTTL The data will remain cached for at least $minTTL seconds
	 */
	public static function add($identifier, $blob, $minTTL = 30){
		self::$cached[$identifier] = [$blob, microtime(true) + $minTTL, $minTTL];
	}

	/**
	 * Get something from the cache
	 *
	 * @param $identifier
	 *
	 * @return bool|mixed Returns false if not found, otherwise it returns the data
	 */
	public static function get($identifier){
		if(isset(self::$cached[$identifier])){
			self::$cached[$identifier][1] = microtime(true) + self::$cached[$identifier][2];

			return self::$cached[$identifier][0];
		}

		return false;
	}

	/**
	 * @param $identifier
	 *
	 * @return bool
	 */
	public static function exists($identifier){
		return isset(self::$cached[$identifier]);
	}

	/**
	 * @param $identifier
	 */
	public static function remove($identifier){
		unset(self::$cached[$identifier]);
	}

	/**
	 * Starts a cache cleanup
	 */
	public static function cleanup(){
		$time = microtime(true);
		foreach(self::$cached as $index => $data){
			if($data[1] < $time){
				unset(self::$cached[$index]);
			}
		}
	}

}