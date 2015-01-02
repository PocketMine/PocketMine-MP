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

 *
 *
*/

namespace pocketmine\plugin;

use pocketmine\scheduler\PluginTask;

class PluginScheduleError implements PluginError{
	private $ex;
	private $task;
	public function __construct(\Exception $ex, PluginTask $task){
		$this->ex = $ex;
		$this->task = $task;
	}
	public function getException(){
		return $this->ex;
	}
	/**
	 * @return PluginTask
	 */
	public function getTask(){
		return $this->task;
	}
}
