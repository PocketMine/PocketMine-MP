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

namespace pocketmine\item;

use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\Player;
use pocketmine\Server;

class Potion extends Item{

	//All Potion metadata
	const WATER_BOTTLE = 0;
	const MUNDANE = 1;
	const MUNDANE_EXTENDED = 2;
	const THICK = 3;
	const AWKWARD = 4;

	const NIGHT_VISION = 5;
	const NIGHT_VISION_LONG = 6;

	const INVISIBILITY = 7;
	const INVISIBILITY_LONG = 8;

	const LEAPING = 9;
	const LEAPING_LONG = 10;
	const LEAPING_TWO = 11;

	const FIRE_RESISTANCE = 12;
	const FIRE_RESISTANCE_LONG = 13;

	const SPEED = 14;
	const SPEED_LONG = 15;
	const SPEED_TWO = 16;

	const SLOWNESS = 17;
	const SLOWNESS_LONG = 18;

	const WATER_BREATHING = 19;
	const WATER_BREATHING_LONG = 20;

	const HEALING = 21;
	const HEALING_TWO = 22;

	const HARMING = 23;
	const HARMING_TWO = 24;

	const POISON = 25;
	const POISON_LONG = 26;
	const POISON_TWO = 27;

	const REGENERATION = 28;
	const REGENERATION_LONG = 29;
	const REGENERATION_TWO = 30;

	const STRENGTH = 31;
	const STRENGTH_LONG = 32;
	const STRENGTH_TWO = 33;

	const WEAKNESS = 34;
	const WEAKNESS_LONG = 35;

	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::POTION, $meta, $count, "Potion");
	}

	public static function getEffect(int $meta){
		switch($meta){
			case self::INVISIBILITY:
				return Effect::getEffect(Effect::INVISIBILITY)->setDuration(20*180);
			case self::INVISIBILITY_LONG:
				return Effect::getEffect(Effect::INVISIBILITY)->setDuration(20*480);
				
			case self::LEAPING:
				return Effect::getEffect(Effect::JUMP)->setDuration(20*180);
			case self::LEAPING_LONG:
				return Effect::getEffect(Effect::JUMP)->setDuration(20*480);
			case self::LEAPING_TWO:
				return Effect::getEffect(Effect::JUMP)->setAmplifier(2)->setDuration(20*90);
			
			case self::FIRE_RESISTANCE:
				return Effect::getEffect(Effect::FIRE_RESISTANCE)->setDuration(20*180);
			case self::FIRE_RESISTANCE_LONG:
				return Effect::getEffect(Effect::FIRE_RESISTANCE)->setDuration(20*480);
				
			case self::SPEED:
				return Effect::getEffect(Effect::SPEED)->setDuration(20*180);
			case self::SPEED_LONG:
				return Effect::getEffect(Effect::SPEED)->setDuration(20*480);
			case self::SPEED_TWO:
				return Effect::getEffect(Effect::SPEED)->setAmplifier(2)->setDuration(20*90);
				
			case self::SLOWNESS:
				return Effect::getEffect(Effect::SLOWNESS)->setDuration(20*90);
			case self::SLOWNESS_LONG:
				return Effect::getEffect(Effect::SLOWNESS)->setDuration(20*240);

			case self::WATER_BREATHING:
				return Effect::getEffect(Effect::WATER_BREATHING)->setDuration(20*180);
			case self::WATER_BREATHING_LONG:
				return Effect::getEffect(Effect::WATER_BREATHING)->setDuration(20*480);
				
//			case self::HARMING:
//			case self::HARMING_TWO:

			case self::POISON:
				return Effect::getEffect(Effect::POISON)->setDuration(20*45);
			case self::POISON_LONG:
				return Effect::getEffect(Effect::POISON)->setDuration(20*120);
			case self::POISON_TWO:
				return Effect::getEffect(Effect::POISON)->setAmplifier(2)->setDuration(20*22.5);

//			case self::HEALING:
//			case self::HEALING_TWO:

			case self::NIGHT_VISION:
				return Effect::getEffect(Effect::NIGHT_VISION)->setDuration(20*180);
			case self::NIGHT_VISION_LONG:
				return Effect::getEffect(Effect::NIGHT_VISION)->setDuration(20*480);

			case self::REGENERATION:
				return Effect::getEffect(Effect::REGENERATION)->setDuration(20*45);
			case self::REGENERATION_LONG:
				return Effect::getEffect(Effect::REGENERATION)->setDuration(20*120);
			case self::REGENERATION_TWO:
				return Effect::getEffect(Effect::REGENERATION)->setAmplifier(2)->setDuration(20*22.5);

			case self::STRENGTH:
				return Effect::getEffect(Effect::STRENGTH)->setDuration(20*180);
			case self::STRENGTH_LONG:
				return Effect::getEffect(Effect::STRENGTH)->setDuration(20*480);
			case self::STRENGTH_TWO:
				return Effect::getEffect(Effect::STRENGTH)->setAmplifier(2)->setDuration(20*90);
			
			case self::WEAKNESS:
				return Effect::getEffect(Effect::WEAKNESS)->setDuration(20*90);
			case self::WEAKNESS_LONG:
				return Effect::getEffect(Effect::WEAKNESS)->setDuration(20*240);
			
			
			default:
				return null;
		}
	}

	public function canBeConsumed() : bool{
		return true;
	}

	public function onConsume(Entity $entity){
		$pk = new EntityEventPacket();
		$pk->eid = $entity->getId();
		$pk->event = EntityEventPacket::USE_ITEM;
		if($entity instanceof Player){
			$entity->dataPacket($pk);
		}
		Server::broadcastPacket($entity->getViewers(), $pk);
		
		//effect
		//todo: harming, healing
		$effect = self::getEffect($this->meta);
		if($effect !== null){
			$entity->addEffect($effect);
		}
		
		if($entity instanceof Player){
			$bottle = Item::get(Item::GLASS_BOTTLE);
		
			if($this->getCount() === 1){
				$entity->getInventory()->setItemInHand(Item::get(0));
				$entity->getInventory()->addItem($bottle);
			}else{
				$left = clone $this;
				$left->count--;
				$entity->getInventory()->setItemInHand($left);
				$extraItem = $entity->getInventory()->addItem($bottle);
				
				if(count($extraItem) > 0){		//if the inventory is full, the bottle will be dropped
					$entity->getLevel()->dropItem($entity, $extraItem[0]);
				}
			}
		}
	}
}
