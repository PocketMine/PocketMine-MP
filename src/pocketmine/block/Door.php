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
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Door extends Transparent implements Attaching{
	const META_UP = 0x08;

	public function canBeActivated(){
		return true;
	}

	public function isSolid(){
		return false;
	}

	private function getFullDamage(){
		$damage = $this->getDamage();
		$isUp = ($damage & 0x08) > 0;

		if($isUp){
			$down = $this->getSide(Vector3::SIDE_DOWN)->getDamage();
			$up = $damage;
		}else{
			$down = $damage;
			$up = $this->getSide(Vector3::SIDE_UP)->getDamage();
		}

		$isRight = ($up & 0x01) > 0;

		return $down & 0x07 | ($isUp ? 8 : 0) | ($isRight ? 0x10 : 0);
	}

	protected function recalculateBoundingBox(){

		$f = 0.1875;
		$damage = $this->getFullDamage();

		$bb = new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 2,
			$this->z + 1
		);

		$j = $damage & 0x03;
		$isOpen = (($damage & 0x04) > 0);
		$isRight = (($damage & 0x10) > 0);

		if($j === 0){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + $f
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z + 1 - $f,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z,
					$this->x + $f,
					$this->y + 1,
					$this->z + 1
				);
			}
		}elseif($j === 1){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x + 1 - $f,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + $f,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z,
					$this->x + 1,
					$this->y + 1,
					$this->z + $f
				);
			}
		}elseif($j === 2){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z + 1 - $f,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + $f
					);
				}
			}else{
				$bb->setBounds(
					$this->x + 1 - $f,
					$this->y,
					$this->z,
					$this->x + 1,
					$this->y + 1,
					$this->z + 1
				);
			}
		}elseif($j === 3){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + $f,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x + 1 - $f,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z + 1 - $f,
					$this->x + 1,
					$this->y + 1,
					$this->z + 1
				);
			}
		}

		return $bb;
	}

	public function getAttachSide(){
		return self::SIDE_DOWN;
	}

	public function canAttachTo(Block $block){
		return !$block->canBeReplaced() and !$block->isTransparent();
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		if($face === 1){
			$blockUp = $this->getSide(1);
			$blockDown = $this->getSide(0);
			if($blockUp->canBeReplaced() === false or $blockDown->isTransparent() === true){
				return false;
			}
			$direction = $player instanceof Player ? $player->getDirection() : 0;
			$face = [
				0 => 3,
				1 => 4,
				2 => 2,
				3 => 5,
			];
			$next = $this->getSide($face[(($direction + 2) % 4)]);
			$next2 = $this->getSide($face[$direction]);
			$metaUp = self::META_UP;
			if($next->getId() === $this->getId() or ($next2->isTransparent() === false and $next->isTransparent() === true)){ //Door hinge
				$metaUp |= 0x01;
			}

			$this->setDamage($player->getDirection() & 0x03);
			$this->getLevel()->setBlock($block, $this, true, true); //Bottom
			$this->getLevel()->setBlock($blockUp, $b = Block::get($this->getId(), $metaUp), true); //Top
			return true;
		}

		return false;
	}

	public function onBreak(Item $item){
		if(($this->getDamage() & 0x08) === 0x08){
			$down = $this->getSide(0);
			if($down->getId() === $this->getId()){
				$this->getLevel()->setBlock($down, new Air(), true);
			}
		}else{
			$up = $this->getSide(1);
			if($up->getId() === $this->getId()){
				$this->getLevel()->setBlock($up, new Air(), true);
			}
		}
		$this->getLevel()->setBlock($this, new Air(), true);

		return true;
	}

	public function onActivate(Item $item, Player $player = null){
		if(($this->getDamage() & 0x08) === 0x08){ //Top
			$down = $this->getSide(0);
			if($down->getId() === $this->getId()){
				$meta = $down->getDamage() ^ 0x04;
				$this->getLevel()->setBlock($down, Block::get($this->getId(), $meta), true);
				$players = $this->getLevel()->getChunkPlayers($this->x >> 4, $this->z >> 4);
				if($player instanceof Player){
					unset($players[$player->getLoaderId()]);
				}

				$this->level->addSound(new DoorSound($this));
				return true;
			}

			return false;
		}else{
			$this->meta ^= 0x04;
			$this->getLevel()->setBlock($this, $this, true);
			$players = $this->getLevel()->getChunkPlayers($this->x >> 4, $this->z >> 4);
			if($player instanceof Player){
				unset($players[$player->getLoaderId()]);
			}
			$this->level->addSound(new DoorSound($this));
		}

		return true;
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$damage = $this->getDamage();
			$activating = $this->isRedstoneActivated();
			if(($damage & 0x08) === 0x08){
				$up = $this;
				$upDamage = $damage;
				$low = $this->getSide(self::SIDE_DOWN);
				$lowDamage = $low->getDamage();
			}else{
				$up = $this->getSide(self::SIDE_UP);
				$upDamage = $up->getDamage();
				$low = $this;
				$lowDamage = $damage;
			}
			$activated = ($upDamage & 0x02) === 0x02;
			$open = ($lowDamage & 0x04) === 0x04;
			$upChanged = false;
			$lowChanged = false;
			if(!$activated and $activating){
				$upDamage |= 0x02;
				$up->setDamage($upDamage);
				$upChanged = true;
				if(!$open){
					$lowDamage |= 0x04;
					$lowChanged = true;
				}
			}elseif($activated and !$activating){
				$upDamage &= ~0x02;
				$up->setDamage($upDamage);
				$upChanged = true;
				if($open){
					$lowDamage &= ~0x04;
					$lowChanged = true;
				}
			}
			if($upChanged){
				$this->getLevel()->setBlock($up, $up, false, true, true);
			}
			if($lowChanged){
				$this->getLevel()->setBlock($low, $low, false, true, true);
				$this->getLevel()->addSound(new DoorSound($low));
			}
		}
	}
}
