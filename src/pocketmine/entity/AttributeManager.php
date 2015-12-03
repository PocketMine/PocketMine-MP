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

namespace pocketmine\entity;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\entity\Attribute;
use pocketmine\network\Network;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;


class AttributeManager{

    const MAX_HEALTH = 0;
    const MAX_HUNGER = 1;

    const EXPERIENCE = 2;
    const EXPERIENCE_LEVEL = 3;

    /** @var Attribute[] */
    protected $attributes = [];

    /** @var Player */
    protected $player;

    public function __construct($player){
        $this->player = $player;
    }

    public function init(){
        self::addAttribute(self::MAX_HEALTH, "generic.health", 0, 20, 20, true);
        self::addAttribute(self::MAX_HUNGER, "player.hunger", 0, 20, 20, true);
        self::addAttribute(self::EXPERIENCE, "player.experience", 0, 1, 0, true);
        self::addAttribute(self::EXPERIENCE_LEVEL, "player.level", 0, 24791, 0, true);
    }

    public function getPlayer() {
        return $this->getPlayer();
    }

    /**
     * @param int    $id
     * @param string $name
     * @param float  $minValue
     * @param float  $maxValue
     * @param float  $defaultValue
     * @param bool   $shouldSend
     * @return Attribute
     */
    public function addAttribute($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend = false){
        if($minValue > $maxValue or $defaultValue > $maxValue or $defaultValue < $minValue){
            throw new \InvalidArgumentException("Invalid ranges: min value: $minValue, max value: $maxValue, $defaultValue: $defaultValue");
        }

        return $this->attributes[(int) $id] = new Attribute($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend, $this->player);
    }

    /**
     * @param $id
     * @return null|Attribute
     */
    public function getAttribute($id){
        return isset($this->attributes[$id]) ? clone $this->attributes[$id] : null;
    }

    /**
     * @param $name
     * @return null|Attribute
     */
    public function getAttributeByName($name){
        foreach($this->attributes as $a){
            if($a->getName() === $name){
                return clone $a;
            }
        }

        return null;
    }

    public function sendAll() {
        foreach($this->attributes as $attribute) {
            $attribute->send();
        }
    }

    public function resetAll() {
        foreach($this->attributes as $attribute) {
            $attribute->setValue($attribute->getDefaultValue());
        }
    }
}