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

use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

class PluginCommandErrorEvent extends PluginErrorEvent{

	/** @var \pocketmine\command\Command|PluginIdentifiableCommand */
	private $cmd;
	/** @var CommandSender */
	private $sender;
	/** @var string */
	private $label;
	/** @var string[] */
	private $args;

	/**
	 * @param \Exception                                            $ex
	 * @param \pocketmine\command\Command|PluginIdentifiableCommand $cmd
	 * @param CommandSender                                         $sender
	 * @param string                                                $label
	 * @param string[]                                              $args
	 */
	public function __construct(\Exception $ex, PluginIdentifiableCommand $cmd, CommandSender $sender, $label, array $args){
		parent::__construct($cmd->getPlugin(), $ex);
		$this->cmd = $cmd;
		$this->sender = $sender;
		$this->label = $label;
		$this->args = $args;
	}

	/**
	 * @return \pocketmine\command\Command|PluginIdentifiableCommand
	 */
	public function getCommand(){
		return $this->cmd;
	}

	/**
	 * @return CommandSender
	 */
	public function getCommandSender(){
		return $this->sender;
	}

	public function getLabel(){
		return $this->label;
	}

	/**
	 * @return array|\string[]
	 */
	public function getArgs(){
		return $this->args;
	}
}
