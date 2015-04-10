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

use pocketmine\plugin\Plugin;
use pocketmine\utils\MainLogger;

/**
 * Triggered when a plugin throws an exception that reaches PocketMine-MP.
 */
abstract class PluginErrorEvent extends PluginEvent{

	public static $handlerList = null;

	/** @var \Exception */
	private $ex;
	/** @var null|MainLogger */
	private $logger = null;

	/**
	 * @param Plugin     $plugin
	 * @param \Exception $ex
	 */
	public function __construct(Plugin $plugin, \Exception $ex){
		parent::__construct($plugin);
		$this->ex = $ex;
		$logger = $plugin->getServer()->getLogger();
		if($logger instanceof MainLogger){
			$this->logger = $logger;
		}
	}

	/**
	 * @return null|MainLogger
	 */
	public function getLogger(){
		return $this->logger;
	}
	/**
	 * @param MainLogger $logger
	 */
	public function setLogger(MainLogger $logger){
		$this->logger = $logger;
	}
	public function unsetLogger(){
		$this->logger = null;
	}

	public function log($trace = null){
		if($this->logger instanceof MainLogger){
			$this->logger->logException($this->ex, $trace);
		}
	}
}
