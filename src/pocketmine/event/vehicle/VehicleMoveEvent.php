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

use pocketmine\level\Location;
use pocketmine\entity\Vehicle;
use pocketmine\event\HandlerList;

class VehicleMoveEvent extends VehicleEvent {
    private static $handlers = HandlerList;
    private $from = Location;
    private $to = Location;
    
    public function __construct(Vehicle $vehicle, Location $from, Location $to) {
        parent::__construct($vehicle);
        $this->from = $from;
        $this->to = $to;
    }
    
    public function getFrom() {
        return $this->from;
    }
    
    public function getTo() {
        return $this->to;
    }
    
    public function getHandlers() {
        return $this->handlers;
    }
    
    public static function getHandlerList() {
        return $this->handlers;
    }
}
