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

class Attribute{

	const ABSORPTION = 0;
	const SATURATION = 1;
	const EXHAUSTION = 2;
	const KNOCKBACK_RESISTANCE = 3;
	const HEALTH = 4;
	const MOVEMENT_SPEED = 5;
	const FOLLOW_RANGE = 6;
	const HUNGER = 7;
	const FOOD = 7;
	const ATTACK_DAMAGE = 8;
	const EXPERIENCE_LEVEL = 9;
	const EXPERIENCE = 10;

	private $id;
	protected $minValue;
	protected $maxValue;
	protected $defaultValue;
	protected $currentValue;
	protected $name;
	protected $shouldSend;

	protected $desynchronized = true;

	/** @var Attribute[] */
	protected static $attributes = [];

	public static function init(){
		self::addAttribute(self::ABSORPTION, "generic.absorption", 0.00, 340282346638528859811704183484516925440.00, 0.00);
		self::addAttribute(self::SATURATION, "player.saturation", 0.00, 20.00, 5.00);
		self::addAttribute(self::EXHAUSTION, "player.exhaustion", 0.00, 5.00, 0.41);
		self::addAttribute(self::KNOCKBACK_RESISTANCE, "generic.knockbackResistance", 0.00, 1.00, 0.00);
		self::addAttribute(self::HEALTH, "generic.health", 0.00, 20.00, 20.00);
		self::addAttribute(self::MOVEMENT_SPEED, "generic.movementSpeed", 0.00, 340282346638528859811704183484516925440.00, 0.10);
		self::addAttribute(self::FOLLOW_RANGE, "generic.followRange", 0.00, 2048.00, 16.00, false);
		self::addAttribute(self::HUNGER, "player.hunger", 0.00, 20.00, 20.00);
		self::addAttribute(self::ATTACK_DAMAGE, "generic.attackDamage", 0.00, 340282346638528859811704183484516925440.00, 1.00, false);
		self::addAttribute(self::EXPERIENCE_LEVEL, "player.level", 0.00, 24791.00, 0.00);
		self::addAttribute(self::EXPERIENCE, "player.experience", 0.00, 1.00, 0.00);
	}

	/**
	 * @param int    $id
	 * @param string $name
	 * @param float  $minValue
	 * @param float  $maxValue
	 * @param float  $defaultValue
	 * @param bool   $shouldSend
	 *
	 * @return Attribute
	 */
	public static function addAttribute($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend = true){
		if($minValue > $maxValue or $defaultValue > $maxValue or $defaultValue < $minValue){
			throw new \InvalidArgumentException("Invalid ranges: min value: $minValue, max value: $maxValue, $defaultValue: $defaultValue");
		}

		return self::$attributes[(int) $id] = new Attribute($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend);
	}

	/**
	 * @param $id
	 *
	 * @return null|Attribute
	 */
	public static function getAttribute($id){
		return isset(self::$attributes[$id]) ? clone self::$attributes[$id] : null;
	}

	/**
	 * @param $name
	 *
	 * @return null|Attribute
	 */
	public static function getAttributeByName($name){
		foreach(self::$attributes as $a){
			if($a->getName() === $name){
				return clone $a;
			}
		}

		return null;
	}

	private function __construct($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend = true){
		$this->id = (int) $id;
		$this->name = (string) $name;
		$this->minValue = (float) $minValue;
		$this->maxValue = (float) $maxValue;
		$this->defaultValue = (float) $defaultValue;
		$this->shouldSend = (bool) $shouldSend;

		$this->currentValue = $this->defaultValue;
	}

	public function getMinValue(){
		return $this->minValue;
	}

	public function setMinValue($minValue){
		if($minValue > $this->getMaxValue()){
			throw new \InvalidArgumentException("Value $minValue is bigger than the maxValue!");
		}

		if($this->minValue != $minValue){
			$this->desynchronized = true;
			$this->minValue = $minValue;
		}
		return $this;
	}

	public function getMaxValue(){
		return $this->maxValue;
	}

	public function setMaxValue($maxValue){
		if($maxValue < $this->getMinValue()){
			throw new \InvalidArgumentException("Value $maxValue is bigger than the minValue!");
		}

		if($this->maxValue != $maxValue){
			$this->desynchronized = true;
			$this->maxValue = $maxValue;
		}
		return $this;
	}

	public function getDefaultValue(){
		return $this->defaultValue;
	}

	public function setDefaultValue($defaultValue){
		if($defaultValue > $this->getMaxValue() or $defaultValue < $this->getMinValue()){
			throw new \InvalidArgumentException("Value $defaultValue exceeds the range!");
		}

		if($this->defaultValue !== $defaultValue){
			$this->desynchronized = true;
			$this->defaultValue = $defaultValue;
		}
		return $this;
	}

	public function getValue(){
		return $this->currentValue;
	}

	public function setValue($value, $fit = false){
		if($value > $this->getMaxValue() or $value < $this->getMinValue()){
			if(!$fit){
				throw new \InvalidArgumentException("Value $value exceeds the range!");
			}
			$value = min(max($value, $this->getMinValue()), $this->getMaxValue());
		}

		if($this->currentValue != $value){
			$this->desynchronized = true;
			$this->currentValue = $value;
		}
		return $this;
	}

	public function getName(){
		return $this->name;
	}

	public function getId(){
		return $this->id;
	}

	public function isSyncable(){
		return $this->shouldSend;
	}

	public function isDesynchronized() : bool{
		return $this->shouldSend and $this->desynchronized;
	}

	public function markSynchronized(bool $synced = true){
		$this->desynchronized = !$synced;
	}
}
