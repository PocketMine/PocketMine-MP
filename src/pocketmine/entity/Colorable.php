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


interface Colorable{

    /*
     * All the colors
     */

    const INK_SAC = 0;
    const ROSE_RED = 1;
    const CACTUS_GREEN = 2;
    const COCOA_BEANS = 3;
    const LAPIS_LAZULI = 4;
    const PURPLE = 5;
    const CYAN = 6;
    const LIGHT_GRAY = 7;
    const GRAY = 8;
    const PINK = 9;
    const LIME = 10;
    const DANDELION_YELLOW = 11;
    const LIGHT_BLUE = 12;
    const MAGENTA = 13;
    const ORANGE = 14;
    const BONE_MEAL = 15;

    /*
     * @param int $color
     *
     * @return bool
     */
    public function setColor($color);

    /*
     * @return int
     */
    public function getColor();

}