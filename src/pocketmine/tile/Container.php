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

namespace PocketMine\Tile;

use PocketMine\Event\Event as Event;
use PocketMine\Event\EventHandler as EventHandler;
use PocketMine\Event\Tile\TileInventoryChangeEvent as TileInventoryChangeEvent;
use PocketMine\Item\Item as Item;
use PocketMine\NBT\Tag\Byte as Byte;
use PocketMine\NBT\Tag\Compound as Compound;
use PocketMine\NBT\Tag\Short as Short;
use PocketMine\Player as Player;
use PocketMine;

trait Container{
	public function openInventory(Player $player){
		if($this instanceof Chest){
			$player->windowCnt++;
			$player->windowCnt = $id = max(2, $player->windowCnt % 99);
			if(($pair = $this->getPair()) !== false){
				if(($pair->x + ($pair->z << 13)) > ($this->x + ($this->z << 13))){ //Order them correctly
					$player->windows[$id] = array(
						$pair,
						$this
					);
				} else{
					$player->windows[$id] = array(
						$this,
						$pair
					);
				}
			} else{
				$player->windows[$id] = $this;
			}

			$pk = new ContainerOpenPacket;
			$pk->windowid = $id;
			$pk->type = WINDOW_CHEST;
			$pk->slots = is_array($player->windows[$id]) ? Chest::SLOTS << 1 : Chest::SLOTS;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$player->dataPacket($pk);
			$slots = array();

			if(is_array($player->windows[$id])){
				$all = $this->level->getPlayers();
				foreach($player->windows[$id] as $ob){
					$pk = new TileEventPacket;
					$pk->x = $ob->x;
					$pk->y = $ob->y;
					$pk->z = $ob->z;
					$pk->case1 = 1;
					$pk->case2 = 2;
					Player::broadcastPacket($all, $pk);
					for($s = 0; $s < Chest::SLOTS; ++$s){
						$slot = $ob->getSlot($s);
						if($slot->getID() > AIR and $slot->getCount() > 0){
							$slots[] = $slot;
						} else{
							$slots[] = Item::get(AIR, 0, 0);
						}
					}
				}
			} else{
				$pk = new TileEventPacket;
				$pk->x = $this->x;
				$pk->y = $this->y;
				$pk->z = $this->z;
				$pk->case1 = 1;
				$pk->case2 = 2;
				Player::broadcastPacket($this->level->getPlayers(), $pk);
				for($s = 0; $s < Chest::SLOTS; ++$s){
					$slot = $this->getSlot($s);
					if($slot->getID() > AIR and $slot->getCount() > 0){
						$slots[] = $slot;
					} else{
						$slots[] = Item::get(AIR, 0, 0);
					}
				}
			}

			$pk = new ContainerSetContentPacket;
			$pk->windowid = $id;
			$pk->slots = $slots;
			$player->dataPacket($pk);

			return true;
		} elseif($this instanceof Furnace){
			$player->windowCnt++;
			$player->windowCnt = $id = max(2, $player->windowCnt % 99);
			$player->windows[$id] = $this;

			$pk = new ContainerOpenPacket;
			$pk->windowid = $id;
			$pk->type = WINDOW_FURNACE;
			$pk->slots = Furnace::SLOTS;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$player->dataPacket($pk);

			$slots = array();
			for($s = 0; $s < Furnace::SLOTS; ++$s){
				$slot = $this->getSlot($s);
				if($slot->getID() > AIR and $slot->getCount() > 0){
					$slots[] = $slot;
				} else{
					$slots[] = Item::get(AIR, 0, 0);
				}
			}
			$pk = new ContainerSetContentPacket;
			$pk->windowid = $id;
			$pk->slots = $slots;
			$player->dataPacket($pk);

			return true;
		}
	}

	public function getSlotIndex($s){
		foreach($this->namedtag->Items as $i => $slot){
			if($slot->Slot === $s){
				return $i;
			}
		}

		return -1;
	}

	public function getSlot($s){
		$i = $this->getSlotIndex($s);
		if($i === false or $i < 0){
			return Item::get(AIR, 0, 0);
		} else{
			return Item::get($this->namedtag->Items[$i]->id, $this->namedtag->Items[$i]->Damage, $this->namedtag->Items[$i]->Count);
		}
	}

	public function setSlot($s, Item $item, $update = true, $offset = 0){
		$i = $this->getSlotIndex($s);

		if($i === false or EventHandler::callEvent($ev = new TileInventoryChangeEvent($this, $this->getSlot($s), $item, $s, $offset)) === Event::DENY){
			return false;
		}

		$item = $ev->getNewItem();
		$d = new Compound(false, array(
			"Count" => new Byte("Count", $item->getCount()),
			"Slot" => new Byte("Slot", $s),
			"id" => new Short("id", $item->getID()),
			"Damage" => new Short("Damage", $item->getMetadata()),
		));

		if($item->getID() === AIR or $item->getCount() <= 0){
			if($i >= 0){
				unset($this->namedtag->Items[$i]);
			}
		} elseif($i < 0){
			$this->namedtag->Items[] = $d;
		} else{
			$this->namedtag->Items[$i] = $d;
		}

		if($update === true){
			$this->scheduleUpdate();
		}

		return true;
	}
}