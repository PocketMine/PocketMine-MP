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

namespace pocketmine\block;


abstract class Fertilisable extends Plant{
	protected $fertilisedCount = 0;

	public function getFertilisedCount(){
		return $this->fertilisedCount;
	}

	public function useFertiliser(){
		$this->fertilisedCount++;
		$this->checkFertiliseActivationLimit();

		return true;
	}

	public function checkFertiliseActivationLimit(){
		if(defined("static::FERTILISE_ACTIVATION_LIMIT")){ //Late static binding
			if($this->fertilisedCount == static::FERTILISE_ACTIVATION_LIMIT){//Constant not defined warning... sure... right after I've used defined()
				if(method_exists(get_called_class(), "fertilise")){//get_called_class() gets the Late Static Binding class name
					static::fertilise();//Seriously PHPStorm? I just confirmed that the class exists! Squelch warning

					return true;
				}else{
					throw new \Exception("Child block does not have the fertilise function");
				}
			}
		}else{
			throw new \Exception("Cannot check fertilise activation limit in child class");
		}

		return false;
	}
}