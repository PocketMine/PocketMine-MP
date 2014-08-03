<?php

/**
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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\player;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;

class PlayerDeathEvent extends EntityDeathEvent{
	public static $handlerList = null;

	private $deathMessage;
    private $cause;

	/**
	 * @param Player $entity
	 * @param Item[] $drops
	 * @param string $deathMessage
     * @param EntityDamageEvent $cause
	 */
	public function __construct(Player $entity, array $drops, $deathMessage, $cause){
		parent::__construct($entity, $drops);
		$this->deathMessage = $deathMessage;
        $this->cause = $cause;
	}

	/**
	 * @return Player
	 */
	public function getEntity(){
		return $this->entity;
	}

    /**
     * @return EntityDamageEvent
     */
    public function getCause(){
        return $this->cause;
    }

	public function getDeathMessage(){
		return $this->deathMessage;
	}

	public function setDeathMessage($deathMessage){
		$this->deathMessage = $deathMessage;
	}

}