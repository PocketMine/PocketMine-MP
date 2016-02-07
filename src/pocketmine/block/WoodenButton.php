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
use pocketmine\level\sound\ButtonClickSound;
use pocketmine\level\sound\ButtonReturnSound;
use pocketmine\Player;

class WoodenButton extends Flowable implements Redstone,RedstoneSwitch{
	
	protected $id = self::WOODEN_BUTTON;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getPower(){
		if($this->meta < 7){
			return 0;
		}
		return 15;
	}
	
	public function canBeActivated(){
		return true;
	}
	
	public function getName(){
		return "Wooden Button";
	}

	public function getHardness(){
		return 0.5;
	}
	
	public function BroadcastRedstoneUpdate($type,$power){
		if($this->meta > 7){
			$pb = $this->meta ^ 0x08;
		}else{
			$pb = $this->meta;
		}
		if($pb%2==0){
			$pb++;
		}else{
			$pb--;
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

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			$this->togglePowered();
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_BREAK,15);
			return;
		}
		return;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($target->isTransparent() === false){
			$this->meta=$face;
			$this->getLevel()->setBlock($block, $this, true, true);
			
			return true;
		}
		
		return false;
	}

	public function onActivate(Item $item, Player $player = null){
		if($this->getPower()>0){
			return;
		}
		if(($player instanceof Player && !$player->isSneaking())||$player===null){
			$this->togglePowered();
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_NORMAL,$this->getPower());
			$this->getLevel()->scheduleUpdate($this, 15);
		}
	}

	public function getDrops(Item $item){
		return [[$this->id,0,1]];
	}

	public function isPowered(){
		return (($this->meta & 0x08) === 0x08);
	}

	/**
	 * Toggles the current state of this button
	 *
	 * @param
	 *        	bool
	 *        	whether or not the button is powered
	 */
	public function togglePowered(){
		$this->meta ^= 0x08;
		if($this->isPowered()){
			$this->getLevel()->addSound(new ButtonClickSound($this));

		}else{
			$this->getLevel()->addSound(new ButtonReturnSound($this, 1000));
		}
		$this->getLevel()->setBlock($this, $this);
	}

	/**
	 * Gets the face that this block is attached on
	 *
	 * @return BlockFace attached to
	 */
	public function getAttachedFace(){
		$data = $this->meta;
		if($this->meta & 0x08 === 0x08) // remove power byte if powered
			$data |= 0x08;
		$faces = [
				5 => 0,
				0 => 1,
				3 => 2,
				4 => 3,
				1 => 4,
				2 => 5,
		];
		return $faces[$data];
	}

	/**
	 * Sets the direction this button is pointing toward
	 */
	public function setFacingDirection($face){
		$data = ($this->meta ^ 0x08);
			$faces = [
				0 => 5,
				1 => 0,
				2 => 3,
				3 => 4,
				4 => 1,
				5 => 2,
			];
			$face-=1;
			if($face<0)
				$face=5;
		$this->setDamage($data |= $faces[$face]);
	}
	
	public function onBreak(Item $item){
		$oBreturn = $this->getLevel()->setBlock($this, new Air(), true, true);
		$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_BREAK,$this->getPower());
		return $oBreturn;
	}
	
	public function __toString(){
		return $this->getName() . " " . ($this->isPowered()?"":"NOT ") . "POWERED";
	}

}
