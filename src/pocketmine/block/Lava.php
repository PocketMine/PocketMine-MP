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

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityCombustByBlockEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;

class Lava extends Liquid{

	protected $id = self::LAVA;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getLightLevel(){
		return 15;
	}

	public function getName(){
		return "Lava";
	}

	public function getHardness(){
		return 0;
	}

	public function onEntityCollide(Entity $entity){
		$entity->fallDistance *= 0.5;
		$damage = [EntityDamageEvent::MODIFIER_BASE => 1];
		if($entity instanceof InventoryHolder){
			$inventory = $entity->getInventory();
			if($inventory instanceof PlayerInventory){
				$damage[EntityDamageEvent::MODIFIER_ARMOR] = -floor($inventory->getArmorPoints() * 0.04);
			}
		}
		$ev = new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_LAVA, $damage);
		$entity->attack($ev->getFinalDamage(), $ev);

		$ev = new EntityCombustByBlockEvent($this, $entity, 15);
		Server::getInstance()->getPluginManager()->callEvent($ev);
		if(!$ev->isCancelled()){
			$entity->setOnFire($ev->getDuration());
		}

		if($entity instanceof Player){
			$entity->onGround = true;
		}
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$ret = $this->getLevel()->setBlock($this, $this, true, false);
		$this->getLevel()->scheduleUpdate($this, $this->tickRate());

		return $ret;
	}

}
