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

/**
 * RakNet UDP library implementation
 */
namespace pocketmine\network\raknet;


abstract class Info{
	const STRUCTURE = 5;
	const MAGIC = "\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78";
	const UNCONNECTED_PING = 0x01;
	const UNCONNECTED_PING_OPEN_CONNECTIONS = 0x02;

	const OPEN_CONNECTION_REQUEST_1 = 0x05;
	const OPEN_CONNECTION_REPLY_1 = 0x06;
	const OPEN_CONNECTION_REQUEST_2 = 0x07;
	const OPEN_CONNECTION_REPLY_2 = 0x08;

	const INCOMPATIBLE_PROTOCOL_VERSION = 0x1a; //CHECK THIS

	const UNCONNECTED_PONG = 0x1c;
	const ADVERTISE_SYSTEM = 0x1d;

	const DATA_PACKET_0 = 0x80;
	const DATA_PACKET_1 = 0x81;
	const DATA_PACKET_2 = 0x82;
	const DATA_PACKET_3 = 0x83;
	const DATA_PACKET_4 = 0x84;
	const DATA_PACKET_5 = 0x85;
	const DATA_PACKET_6 = 0x86;
	const DATA_PACKET_7 = 0x87;
	const DATA_PACKET_8 = 0x88;
	const DATA_PACKET_9 = 0x89;
	const DATA_PACKET_A = 0x8a;
	const DATA_PACKET_B = 0x8b;
	const DATA_PACKET_C = 0x8c;
	const DATA_PACKET_D = 0x8d;
	const DATA_PACKET_E = 0x8e;
	const DATA_PACKET_F = 0x8f;

	const NACK = 0xa0;
	const ACK = 0xc0;


	public static function isValid($pid){
		switch((int) $pid){
			case self::UNCONNECTED_PING:
			case self::UNCONNECTED_PING_OPEN_CONNECTIONS:
			case self::OPEN_CONNECTION_REQUEST_1:
			case self::OPEN_CONNECTION_REPLY_1:
			case self::OPEN_CONNECTION_REQUEST_2:
			case self::OPEN_CONNECTION_REPLY_2:
			case self::INCOMPATIBLE_PROTOCOL_VERSION:
			case self::UNCONNECTED_PONG:
			case self::ADVERTISE_SYSTEM:
			case self::DATA_PACKET_0:
			case self::DATA_PACKET_1:
			case self::DATA_PACKET_2:
			case self::DATA_PACKET_3:
			case self::DATA_PACKET_4:
			case self::DATA_PACKET_5:
			case self::DATA_PACKET_6:
			case self::DATA_PACKET_7:
			case self::DATA_PACKET_8:
			case self::DATA_PACKET_9:
			case self::DATA_PACKET_A:
			case self::DATA_PACKET_B:
			case self::DATA_PACKET_C:
			case self::DATA_PACKET_D:
			case self::DATA_PACKET_E:
			case self::DATA_PACKET_F:
			case self::NACK:
			case self::ACK:
				return true;
			default:
				return false;
		}
	}
}