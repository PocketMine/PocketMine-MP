<?php

/*
 * PocketMine-MP
 *
 * Copyright (C) 2015 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace pocketmine\block;

use pocketmine\level\Level;

class LitRedstoneLamp extends RedstoneLamp{

	protected $id = self::LIT_REDSTONE_LAMP;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if(($type === Level::BLOCK_UPDATE_NORMAL or Level::BLOCK_UPDATE_SCHEDULED or $type === Level::BLOCK_UPDATE_REDSTONE) and !$this->isRedstoneActivated()){
			$this->getLevel()->setBlock($this, new RedstoneLamp());
		}
	}
}
