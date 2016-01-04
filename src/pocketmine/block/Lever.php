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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class Lever extends Flowable implements Redstone,RedstoneSwitch{

	protected $id = self::LEVER;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Lever";
	}

	public function isRedstone(){
		return true;
	}
	
	public function canBeActivated(){
		return true;
	}
	
	public function getPower(){
		if($this->meta < 7){
			return 0;
		}
		return 15;
	}

	public function onUpdate($type){
		/*if($type === Level::BLOCK_UPDATE_NORMAL){
			$below = $this->getSide(0);
			$faces = [
				0 => 1,
				1 => 0,
				2 => 3,
				3 => 2,
				4 => 5,
				5 => 4,
			];
			if($this->getSide($faces[$this->meta])->isTransparent() === true){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}
		return true;*/
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){

		if($target->isTransparent() === false){
			$faces = [
				3 => 3,
				2 => 4,
				4 => 2,
				5 => 1,
			];
			if($face === 0){
				$to = $player instanceof Player?$player->getDirection():0;
				$this->meta = ($to ^ 0x01 === 0x01?0:7);
			}
			elseif($face === 1){
				$to = $player instanceof Player?$player->getDirection():0;
				$this->meta = ($to ^ 0x01 === 0x01?6:5);
			}
			else{
				$this->meta = $faces[$face];
			}
			$this->getLevel()->setBlock($block, $this, true, true);

			return true;
		}

		return false;
	}
	
	public function BroadcastRedstoneUpdate($type,$power){
		if($this->meta > 7){
			$pb = $this->meta ^ 0x08;
		}else{
			$pb = $this->meta;
		}
		switch($pb){
			case 4:
				$pb=3;
				break;
			case 2:
				$pb=5;
				break;
			case 3:
				$pb=2;
				break;
			case 1:
				$pb=4;
				break;
			case 0:
			case 7:
				$pb = 1;
				break;
			case 6:
			case 5:
				$pb = 0;
				break;
		}
		for($side = 0; $side <= 5; $side++){
			$around=$this->getSide($side);
			$this->getLevel()->setRedstoneUpdate($around,Block::REDSTONEDELAY,$type,$power);
			if($side == $pb){
				for($side2 = 0; $side2 <= 5; $side2++){
					$around2=$around->getSide($side2);
					$this->getLevel()->setRedstoneUpdate($around2,Block::REDSTONEDELAY,$type,$power);
				}
			}
		}
	}
	
	public function onActivate(Item $item, Player $player = null){
		if($this->meta <= 7 ){
			$type = Level::REDSTONE_UPDATE_NORMAL;
		}else{
			$type = Level::REDSTONE_UPDATE_BREAK;
		}
		$this->meta ^= 0x08;
		$this->getLevel()->setBlock($this, $this ,true ,true);
		$this->BroadcastRedstoneUpdate($type,15);
	}
	
	

	public function getDrops(Item $item){
		return [[$this->id,0,1]];
	}
	
	public function onBreak(Item $item){
		$oBreturn = $this->getLevel()->setBlock($this, new Air(), true, true);
		$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_BREAK,$this->getPower());
		return $oBreturn;
	}
	
}
