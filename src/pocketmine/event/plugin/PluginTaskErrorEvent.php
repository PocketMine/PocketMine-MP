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

namespace pocketmine\event\plugin;

use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\TaskHandler;

class PluginTaskErrorEvent extends PluginErrorEvent{

	/** @var TaskHandler */
	private $handler;

	/**
	 * @param TaskHandler $handler
	 * @param \Exception   $ex
	 */
	public function __construct(TaskHandler $handler, \Exception $ex){
		$task = $handler->getTask();
		if(!($task instanceof PluginTask)){
			throw new \RuntimeException("TaskHandler does not contain a PluginTask");
		}
		parent::__construct($task->getOwner(), $ex);
		$this->handler = $handler;
	}

	/**
	 * @return TaskHandler
	 */
	public function getHandler(){
		return $this->handler;
	}
}
