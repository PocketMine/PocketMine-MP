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
namespace pocketmine\block;

use pocketmine\item\Item;

class NetherWart extends NetherCrops{
	protected $id = self::NETHER_WART;
	public function __construct($meta = 0){
		$this->meta = $meta;
	}
	public function getName(){
		return "Nether Wart";
	}

    public function getDrops(Item $item){
        $drops = [];
        if($this->meta >= 0x07){
            $drops[] = [Item::NETHER_WART, 0, mt_rand(2, 4)];
        }else{
            $drops[] = [Item::NETHER_WART, 0, 1];
        }

        return $drops;
    }
}
