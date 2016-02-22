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

use pocketmine\entity\Effect;

class GoldenApple extends Food{
	public function __construct($meta = 0, $count = 1){
		parent::__construct(self::GOLDEN_APPLE, $meta, $count, ($meta === 1 ? "Enchanted " : "") . "Golden Apple");
	}

	public function getFoodRestore() : int{
		return 4;
	}

	public function getSaturationRestore() : float{
		return 9.6;
	}

	public function getAdditionalEffects() : array{
		return $this->meta === 1 ? [
			Effect::getEffect(Effect::REGENERATION)->setDuration(600)->setAmplifier(4),
			Effect::getEffect(Effect::ABSORPTION)->setDuration(2400),
			Effect::getEffect(Effect::DAMAGE_RESISTANCE)->setDuration(6000),
			Effect::getEffect(Effect::FIRE_RESISTANCE)->setDuration(6000),
		] : [
			Effect::getEffect(Effect::REGENERATION)->setDuration(100)->setAmplifier(1),
			Effect::getEffect(Effect::ABSORPTION)->setDuration(2400)
		];
	}
}

