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

#ifndef COMPILE
use pocketmine\utils\Binary;
#endif

use pocketmine\item\Item;


abstract class DataPacket extends \stdClass{

	const NETWORK_ID = 0;

	public $offset = 0;
	public $buffer = "";
	public $isEncoded = false;
	private $channel = 0;

	public function pid(){
		return $this::NETWORK_ID;
	}

	abstract public function encode();

	abstract public function decode();

	protected function reset(){
		$this->buffer = chr($this::NETWORK_ID);
		$this->offset = 0;
	}

	public function setChannel($channel){
		$this->channel = (int) $channel;
		return $this;
	}

	public function getChannel(){
		return $this->channel;
	}

	public function setBuffer($buffer = null, $offset = 0){
		$this->buffer = $buffer;
		$this->offset = (int) $offset;
	}

	public function getOffset(){
		return $this->offset;
	}

	public function getBuffer(){
		return $this->buffer;
	}

	protected function get($len){
		if($len < 0){
			$this->offset = strlen($this->buffer) - 1;
			return "";
		}elseif($len === true){
			return substr($this->buffer, $this->offset);
		}

		return $len === 1 ? $this->buffer{$this->offset++} : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	protected function put($str){
		$this->buffer .= $str;
	}

	protected function getLong(){
		return Binary::readLong($this->get(8));
	}

	protected function putLong($v){
		$this->buffer .= Binary::writeLong($v);
	}

	protected function getInt(){
		return Binary::readInt($this->get(4));
	}

	protected function putInt($v){
		$this->buffer .= Binary::writeInt($v);
	}

	protected function getShort($signed = true){
		return $signed ? Binary::readSignedShort($this->get(2)) : Binary::readShort($this->get(2));
	}

	protected function putShort($v){
		$this->buffer .= Binary::writeShort($v);
	}

	protected function getFloat(){
		return Binary::readFloat($this->get(4));
	}

	protected function putFloat($v){
		$this->buffer .= Binary::writeFloat($v);
	}

	protected function getTriad(){
		return Binary::readTriad($this->get(3));
	}

	protected function putTriad($v){
		$this->buffer .= Binary::writeTriad($v);
	}


	protected function getLTriad(){
		return Binary::readLTriad($this->get(3));
	}

	protected function putLTriad($v){
		$this->buffer .= Binary::writeLTriad($v);
	}

	protected function getByte(){
		return ord($this->buffer{$this->offset++});
	}

	protected function putByte($v){
		$this->buffer .= chr($v);
	}

	protected function getDataArray($len = 10){
		$data = [];
		for($i = 1; $i <= $len and !$this->feof(); ++$i){
			$data[] = $this->get($this->getTriad());
		}

		return $data;
	}

	protected function putDataArray(array $data = []){
		foreach($data as $v){
			$this->putTriad(strlen($v));
			$this->put($v);
		}
	}

	protected function getSlot(){
		$id = $this->getShort();
		$cnt = $this->getByte();

		return Item::get(
			$id,
			$this->getShort(),
			$cnt
		);
	}

	protected function putSlot(Item $item){
		$this->putShort($item->getId());
		$this->putByte($item->getCount());
		$this->putShort($item->getDamage());
	}

	protected function getString(){
		return $this->get($this->getShort());
	}

	protected function putString($v){
		$this->putShort(strlen($v));
		$this->put($v);
	}

	protected function feof(){
		return !isset($this->buffer{$this->offset});
	}

	public function clean(){
		$this->buffer = null;
		$this->isEncoded = false;
		$this->offset = 0;
		return $this;
	}
}