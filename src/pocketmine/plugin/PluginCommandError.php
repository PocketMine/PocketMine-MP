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

namespace pocketmine\plugin;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

class PluginCommandError implements PluginError{

	/** @var \Exception */
	private $exception;
	/** @var CommandSender */
	private $sender;
	/** @var \pocketmine\command\Command|PluginIdentifiableCommand */
	private $cmd;
	/** @var string */
	private $label;
	/** @var string[] */
	private $args;
	/**
	 * @param \Exception                $exception
	 * @param CommandSender             $sender
	 * @param PluginIdentifiableCommand $cmd
	 * @param string                    $label
	 * @param string[]                  $args
	 */
	public function __construct(\Exception $exception, CommandSender $sender, PluginIdentifiableCommand $cmd, $label, array $args){
		$this->exception = $exception;
		$this->sender = $sender;
		$this->cmd = $cmd;
		$this->label = $label;
		$this->args = $args;
	}

	/**
	 * @return \Exception
	 */
	public function getException(){
		return $this->exception;
	}
	/**
	 * @return CommandSender
	 */
	public function getCommandSender(){
		return $this->sender;
	}
	/**
	 * @return \pocketmine\command\Command|PluginIdentifiableCommand
	 */
	public function getCommand(){
		return $this->cmd;
	}
	/**
	 * @return string
	 */
	public function getLabel(){
		return $this->label;
	}
	/**
	 * @return string[]
	 */
	public function getArgs(){
		return $this->args;
	}

}
