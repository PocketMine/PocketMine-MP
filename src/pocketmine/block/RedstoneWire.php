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

class RedstoneWire extends Flowable implements Redstone,RedstoneTransmitter{
	protected $id = self::REDSTONE_WIRE;

	public function isRedstone(){
		return true;
	}
	
	public function __construct($meta = 0){
		$this->meta = $meta;
	}
	
	public function getPower(){
		return $this->meta;
	}
	
	public function setPower($power){
		$this->meta = $power;
	}
	
	public function getHardness(){
		return 0;
	}

	public function isSolid(){
		return true;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$down = $this->getSide(0);
		if($down instanceof Transparent && $down->getId() !== Block::GLOWSTONE_BLOCK) return false;
		else{
			$this->getLevel()->setBlock($block, $this, true, true);
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_PLACE,0,$this);
			return true;
		}
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$down = $this->getSide(0);
			if($down instanceof Transparent){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}
		return true;
	}
	
	public function fetchMaxPower(){
		$power_in_max = 0;
		for($side = 0; $side <= 5; $side++){
			$near = $this->getSide($side);
			if($near instanceof Redstone){
				$power_in = $near->getPower();
				if($power_in >= 15){
					return 15;
				}
				if($power_in > $power_in_max){
					$power_in_max = $power_in;
				}
			}
		}
		for($side = 2;$side<=5;$side++){
			$near = $this->getSide($side);
			$around_down = $near->getSide(0);
			$around_up = $near->getSide(1);
			if($near->id == self::AIR and $around_down instanceof RedstoneTransmitter){
				$power_in = $around_down->getPower();
				if($power_in >= 15){
					return 15;
				}
				if($power_in > $power_in_max){
					$power_in_max = $power_in;
				}
			}
			if(!$near instanceof Transparent and $around_up instanceof RedstoneTransmitter){
				$power_in = $around_up->getPower();
				if($power_in >= 15){
					return 15;
				}
				if($power_in > $power_in_max)
					$power_in_max = $power_in;
			}
		}
		return $power_in_max;
	}
	
	public function BroadcastRedstoneUpdate($type,$power){
		$down = $this->getSide(0);
		$up = $this->getSide(1);
		if($down instanceof Redstone){
			$this->getLevel()->setRedstoneUpdate($down,Block::REDSTONEDELAY,$type,$power);
		}
		if($up instanceof Redstone){
			$this->getLevel()->setRedstoneUpdate($up,Block::REDSTONEDELAY,$type,$power);
		}
		for($side = 2; $side <= 5; $side++){
			$around=$this->getSide($side);
			$this->getLevel()->setRedstoneUpdate($around,Block::REDSTONEDELAY,$type,$power);
			if(!$around instanceof Transparent){
				$up = $around->getSide(1);
				if($up instanceof RedstoneTransmitter){
					$this->getLevel()->setRedstoneUpdate($up,Block::REDSTONEDELAY,$type,$power);
				}
			}else{
				if($around->id==self::AIR){
					$down = $around->getSide(0);
					if($down instanceof Redstone)
						$this->getLevel()->setRedstoneUpdate($down,Block::REDSTONEDELAY,$type,$power);
				}
			}
		}
	}
	
	public function onRedstoneUpdate($type,$power){
		if($type == Level::REDSTONE_UPDATE_PLACE){
			if($this->getPower() > 1 and $power == 0){
				$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_NORMAL,$this->getPower());
				return;
			}
			if($this->getPower()+1 >= $power){
				return;
			}
			$this->setPower($power - 1);
			$this->getLevel()->setBlock($this, $this, true, false);
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_NORMAL,$this->getPower());
			return;
		}
		
		if($type == Level::REDSTONE_UPDATE_NORMAL){
			if($power <= $this->getPower()+1){
				return;
			}
			$this->setPower($power - 1);
			$this->getLevel()->setBlock($this, $this, true, false);
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_NORMAL,$this->getPower());
			return;
		}

		if($type == Level::REDSTONE_UPDATE_LOSTPOWER){
			if($this->getPower()==0){
				return;
			}
			$MaxNearbyPower = $this->fetchMaxPower() -1;
			if($MaxNearbyPower == $this->getPower()){
				return;
			}
			if($MaxNearbyPower<0){
				$this->setPower(0);
			}else{
				$this->setPower($MaxNearbyPower);
			}
			$this->getLevel()->setBlock($this, $this, true, false);
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_LOSTPOWER,$this->getPower());
			return;
		}
		
		if($type == Level::REDSTONE_UPDATE_BREAK){
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_LOSTPOWER,$this->getPower());
			if($this->getPower()==0){
				return;
			}
			if(!($power >= $this->getPower() + 1)){
				return;
			}
			$MaxNearbyPower = $this->fetchMaxPower() -1;
			if($MaxNearbyPower == $this->getPower() or $this->getPower()==0){
				return;
			}
			if($MaxNearbyPower<0){
				$this->setPower(0);
			}else{
				$this->setPower($MaxNearbyPower);
			}
			$this->getLevel()->setBlock($this, $this, true, false);
			$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_LOSTPOWER,$this->getPower());
			return;
		}
	}
	
	public function getName(){
		return "Redstone Wire";
	}

	public function getDrops(Item $item){
		return [[Item::REDSTONE_DUST,0,1]];
	}
	
	public function onBreak(Item $item){
		$oBreturn = $this->getLevel()->setBlock($this, new Air(), true, true);
		$this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_BREAK,$this->getPower());
		return $oBreturn;
	}
	
	public function __toString(){
		return $this->getName() . ($this->getPower() > 0?"":"NOT ") . "POWERED";
	}
}
