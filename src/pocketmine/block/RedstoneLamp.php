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

use pocketmine\item\Item;
use pocketmine\level\Level;

class RedstoneLamp extends Solid implements RedstoneSensitiveAppliance{

	protected $id = self::REDSTONE_LAMP;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Redstone Lamp";
	}

	public function getHardness(){
		return 0.3;
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if(($type === Level::BLOCK_UPDATE_NORMAL or Level::BLOCK_UPDATE_SCHEDULED or $type === Level::BLOCK_UPDATE_REDSTONE) and $this->isRedstoneActivated()){
			$this->getLevel()->setBlock($this, new LitRedstoneLamp());
		}
	}

	public function getDrops(Item $item){
		return [
			[self::REDSTONE_LAMP, 0, 1]
		];
	}
}
