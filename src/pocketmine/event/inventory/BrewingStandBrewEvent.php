<?php

/**
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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\inventory;

use pocketmine\event\block\BlockEvent;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\tile\brewingStand;

class BrewingStandBrewEvent extends BlockEvent implements Cancellable{
	public static $handlerList = null;

	private $brewingStand;
	private $source;
	private $result;

	public function __construct(BrewingStand $brewingStand, Item $source, Item $result){
		parent::__construct($brewingStand->getBlock());
		$this->source = clone $source;
		$this->source->setCount(1);
		$this->result = $result;
		$this->brewingStand = $brewingStand;
	}

	/**
	 * @return brewingStand
	 */
	public function getbrewingStand(){
		return $this->brewingStand;
	}

	/**
	 * @return Item
	 */
	public function getSource(){
		return $this->source;
	}

	/**
	 * @return Item
	 */
	public function getResult(){
		return $this->result;
	}

	/**
	 * @param Item $result
	 */
	public function setResult(Item $result){
		$this->result = $result;
	}
}