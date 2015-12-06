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

use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use pocketmine\utils\ModelledEnum;

class Attribute extends ModelledEnum{

	const HEALTH = 0;
	const MAX_HEALTH = 0;

	const HUNGER = 1;
	const MAX_HUNGER = 1;

	const EXPERIENCE = 2;
	const EXPERIENCE_LEVEL = 3;

	protected $minValue;
	protected $maxValue;
	protected $defaultValue;
	protected $currentValue;
	protected $shouldSend;

	public static function init(){

		self::addEnumEntry(new Attribute(self::HEALTH, "generic.health", 0, 0x7fffffff, 20, true));
		self::addEnumEntry(new Attribute(self::HUNGER, "player.hunger", 0, 20, 20, true));
		self::addEnumEntry(new Attribute(self::EXPERIENCE, "player.experience", 0, 1, 0, true));
		self::addEnumEntry(new Attribute(self::EXPERIENCE_LEVEL, "player.level", 0, 24791, 0, true));
	}

	protected function __construct($id, $name, $minValue, $maxValue, $defaultValue, $shouldSend = false){
		parent::__construct((int) $id, (string) $name);
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

		$this->minValue = $minValue;
		return $this;
	}

	public function getMaxValue(){
		return $this->maxValue;
	}

	public function setMaxValue($maxValue){
		if($maxValue < $this->getMinValue()){
			throw new \InvalidArgumentException("Value $maxValue is bigger than the minValue!");
		}

		$this->maxValue = $maxValue;
		return $this;
	}

	public function getDefaultValue(){
		return $this->defaultValue;
	}

	public function setDefaultValue($defaultValue){
		if($defaultValue > $this->getMaxValue() or $defaultValue < $this->getMinValue()){
			throw new \InvalidArgumentException("Value $defaultValue exceeds the range!");
		}

		$this->defaultValue = $defaultValue;
		return $this;
	}

	public function getValue(){
		return $this->currentValue;
	}

	public function setValue($value){
		$value = max($this->minValue, min($this->maxValue, $value));

		$this->currentValue = $value;

		return $this;
	}

	public function isSyncable(){
		return $this->shouldSend;
	}

	/**
	 * @param Player        $subject
	 * @param Player[]|null $recipients
	 */
	public function send(Player $subject, $recipients = null){
		$pk = new UpdateAttributesPacket;
		$pk->entityId = $subject->getId();
		$pk->entries = [$this];
		foreach($recipients === null ? [$subject] : $recipients as $recipient){
			if($recipient === $subject){
				$packet = clone $pk;
				$packet->entityId = 0;
				$packet->isEncoded = false;
				$recipient->dataPacket($packet);
			}else{
				$recipient->dataPacket($pk);
			}
		}
	}
}
