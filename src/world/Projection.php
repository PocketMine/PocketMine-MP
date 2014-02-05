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
 * @link http://www.pocketmine.net/
 * 
 *
*/

define ("GRAVITY", -9.8); // TODO testing needed to find the optimal gravity
class Projection {
	private $object;
	private $ticks, $initYaw, $initPitch, $force, $gravity;
	//private $id;
	/**
	 * Create a projection
	 * @param Position $projected Entity to project, or Position to write the result to
	 * @param double $yaw yaw in degrees at the first tick
	 * @param double $pitch=0 pitch in degrees at the first tick
	 * @param double $force=1 force in blocks/tick if there is no air resistance
	 * @param double $airResistence=0 air resistance in percentage (for vertical movement only). Useful for plugins when they want to make a hyper-gravity zone or whatever, or something that floats up from water
	*/
	public function __construct(Position &$projected, $yaw, $pitch=0, $force=1, $airResistance=0 /*, $projectionId*/) { // TODO testing needed to find the optimal force
		$this->object =& $projected;
		$this->initYaw = $yaw;
		$this->initPitch = $pitch;
		$this->force = $force;
		$this->gravity = GRAVITY * (100 - $airResistance) * 0.01;
		ServerAPI::request()->schedule(1, array($this, "tick"));
	}
	public function tick() {
		$planeX = sin($this->initYaw) * $this->strength;
		$planeZ = cos($this->initYaw) * $this->strength;
		$deltaY = sin($this->initPitch) * $this->strength + $this->ticks * $this->gravity;
		$planeX *= ($planePitch = cos($this->initPitch) * $this->strength);
		$planeZ *= $planePitch;
		$server=ServerAPI::request();
		if (($ret = $server->handle("projection.tick", array("projection" => $this, "position" => $this->object)) === true))
			$server->schedule(1, array($this, "tick"));
		/*elseif ($ret === false)
			$server->api->projectile->remove($this->projectileId);*/
	}
}
