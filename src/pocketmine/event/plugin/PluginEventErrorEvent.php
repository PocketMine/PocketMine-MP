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

use pocketmine\event\Event;
use pocketmine\plugin\RegisteredListener;

class PluginEventErrorEvent extends PluginErrorEvent{

	/** @var RegisteredListener */
	private $listener;
	/** @var Event */
	private $event;

	/**
	 * @param RegisteredListener $listener
	 * @param \Exception         $ex
	 * @param Event              $event
	 */
	public function __construct(RegisteredListener $listener, \Exception $ex, Event $event){
		parent::__construct($listener->getPlugin(), $ex);
		$this->listener = $listener;
		$this->event = $event;
	}

	/**
	 * @return RegisteredListener
	 */
	public function getRegistration(){
		return $this->listener;
	}

	/**
	 * @return \pocketmine\event\Listener
	 */
	public function getListener(){
		return $this->listener->getListener();
	}

	/**
	 * @return Event
	 */
	public function getEvent(){
		return $this->event;
	}
}
