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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\vehicle;

use pocketmine\entity\Entity;
use pocketmine\entity\Vehicle;
use pocketmine\event\Cancellable;
use pocketmine\event\HandlerList;

class VehicleEnterEvent extends VehicleEvent implements Cancellable {
    private static $handlers = HandlerList;
    private $cancelled;
    private $entered;
    
    public function __construct(Vehicle $vehicle, Entity $entered) {
        parent::__construct($vehicle);
        $this->entered = $entered;
    }
    
    public function getEntered() {
        return $this->entered;
    }
    
    public function isCancelled() {
        return $this->cancelled;
    }
    
    public function setCancelled() {
        $this->cancelled = true;
    }
    
    public function getHandlers() {
        return $this->handlers;
    }
    
    public static function getHandlerList() {
        return $this->handlers;
    }
}
