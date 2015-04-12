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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>


class TextPacket extends DataPacket{
	public static $pool = [];
	public static $next = 0;

	const TYPE_RAW = 0;
	const TYPE_CHAT = 1;
	const TYPE_TRANSLATION = 2;
	const TYPE_POPUP = 3;

	public $type;
	public $source;
	public $message;
	public $parameters = [];

	public function pid(){
		return Info::TEXT_PACKET;
	}

	public function decode(){
		$this->type = $this->getByte();
		switch($this->type){
			case self::TYPE_CHAT:
				$this->source = $this->getString();
			case self::TYPE_RAW:
			case self::TYPE_POPUP:
				$this->message = $this->getString();
				break;

			case self::TYPE_TRANSLATION:
				$this->message = $this->getString();
				$count = $this->getByte();
				for($i = 0; $i < $count; ++$count){
					$this->parameters[] = $this->getString();
				}
		}
	}

	public function encode(){
		$this->reset();
		$this->putByte($this->type);
		switch($this->type){
			case self::TYPE_CHAT:
				$this->putString($this->source);
			case self::TYPE_RAW:
			case self::TYPE_POPUP:
				$this->putString($this->message);
				break;

			case self::TYPE_TRANSLATION:
				$this->putString($this->message);
				$this->putByte(count($this->parameters));
				foreach($this->parameters as $p){
					$this->putString($p);
				}
		}
	}

}
