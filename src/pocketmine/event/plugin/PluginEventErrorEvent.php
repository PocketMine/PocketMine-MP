<?php

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
