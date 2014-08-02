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

namespace PocketMine\Block;

use PocketMine;
use PocketMine\Item\Item as Item;

class SignPost extends Transparent{
	public function __construct($meta = 0){
		parent::__construct(self::SIGN_POST, $meta, "Sign Post");
		$this->isSolid = false;
		$this->isFullBlock = false;
		$this->hardness = 5;
	}

	public function place(Item $item, PocketMine\Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
		if($face !== 0){
			$faces = array(
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5,
			);
			if(!isset($faces[$face])){
				$this->meta = floor((($player->yaw + 180) * 16 / 360) + 0.5) & 0x0F;
				$this->level->setBlock($block, Block::get(SIGN_POST, $this->meta), true, false, true);

				return true;
			} else{
				$this->meta = $faces[$face];
				$this->level->setBlock($block, Block::get(WALL_SIGN, $this->meta), true, false, true);

				return true;
			}
		}

		return false;
	}

	public function onUpdate($type){
		if($type === BLOCK_UPDATE_NORMAL){
			if($this->getSide(0)->getID() === self::AIR){ //Replace with common break method
				//TODO
				//ServerAPI::request()->api->entity->drop($this, Item::get(SIGN, 0, 1));
				$this->level->setBlock($this, new Air(), true, true, true);

				return BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function onBreak(Item $item, PocketMine\Player $player){
		$this->level->setBlock($this, new Air(), true, true, true);

		return true;
	}

	public function getDrops(Item $item, PocketMine\Player $player){
		return array(
			array(Item::SIGN, 0, 1),
		);
	}
}