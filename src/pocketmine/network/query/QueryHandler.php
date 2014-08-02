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
 * Implementation of the UT3 Query Protocol (GameSpot)
 * Source: http://wiki.unrealadmin.org/UT3_query_protocol
*/
namespace PocketMine\Network\Query;

use PocketMine\Player as Player;
use PocketMine\ServerAPI as ServerAPI;
use PocketMine\Utils\Utils as Utils;
use PocketMine;

class QueryHandler{
	private $socket, $server, $lastToken, $token, $longData, $timeout;

	public function __construct(){
		console("[INFO] Starting GS4 status listener");
		$this->server = ServerAPI::request();
		$addr = ($ip = $this->server->api->getProperty("server-ip")) != "" ? $ip : "0.0.0.0";
		$port = $this->server->api->getProperty("server-port");
		console("[INFO] Setting query port to $port");
		/*
		The Query protocol is built on top of the existing Minecraft PE UDP network stack.
		Because the 0xFE packet does not exist in the MCPE protocol,
		we can identify	Query packets and remove them from the packet queue.
		
		Then, the Query class handles itself sending the packets in raw form, because
		packets can conflict with the MCPE ones.
		*/

		$this->server->schedule(20 * 30, array($this, "regenerateToken"), array(), true);
		$this->regenerateToken();
		$this->lastToken = $this->token;
		$this->regenerateInfo();
		console("[INFO] Query running on $addr:$port");
	}

	public function regenerateInfo(){
		$str = "";
		$plist = "PocketMine-MP " . PocketMine\VERSION;
		$pl = $this->server->api->plugin->getList();
		if(count($pl) > 0){
			$plist .= ":";
			foreach($pl as $p){
				$plist .= " " . str_replace(array(";", ":", " "), array("", "", "_"), $p["name"]) . " " . str_replace(array(";", ":", " "), array("", "", "_"), $p["version"]) . ";";
			}
			$plist = substr($plist, 0, -1);
		}
		$KVdata = array(
			"splitnum" => chr(128),
			"hostname" => $this->server->name,
			"gametype" => ($this->server->gamemode & 0x01) === 0 ? "SMP" : "CMP",
			"game_id" => "MINECRAFTPE",
			"version" => PocketMine\MINECRAFT_VERSION,
			"server_engine" => "PocketMine-MP " . PocketMine\VERSION,
			"plugins" => $plist,
			"map" => $this->server->api->level->getDefault()->getName(),
			"numplayers" => count(Player::$list),
			"maxplayers" => $this->server->maxClients,
			"whitelist" => $this->server->api->getProperty("white-list") === true ? "on" : "off",
			"hostport" => $this->server->api->getProperty("server-port"),
			//"hostip" => $this->server->api->getProperty("server-ip", "0.0.0.0")
		);
		foreach($KVdata as $key => $value){
			$str .= $key . "\x00" . $value . "\x00";
		}
		$str .= "\x00\x01player_\x00\x00";
		foreach(Player::$list as $player){
			if($player->getUsername() != ""){
				$str .= $player->getUsername() . "\x00";
			}
		}
		$str .= "\x00";
		$this->longData = $str;
		$this->timeout = microtime(true) + 5;
	}

	public function regenerateToken(){
		$this->lastToken = $this->token;
		$this->token = Utils::getRandomBytes(16, false);
	}

	public static function getTokenString($token, $salt){
		return Utils::readInt(substr(hash("sha512", $salt . ":" . $token, true), 7, 4));
	}

	public function handle(QueryPacket $packet){
		$packet->decode();
		switch($packet->packetType){
			case QueryPacket::HANDSHAKE: //Handshake
				$pk = new QueryPacket;
				$pk->ip = $packet->ip;
				$pk->port = $packet->port;
				$pk->packetType = QueryPacket::HANDSHAKE;
				$pk->sessionID = $packet->sessionID;
				$pk->payload = self::getTokenString($this->token, $packet->ip) . "\x00";
				$pk->encode();
				$this->server->send($pk);
				break;
			case QueryPacket::STATISTICS: //Stat
				$token = Utils::readInt(substr($packet->payload, 0, 4));
				if($token !== self::getTokenString($this->token, $packet->ip) and $token !== self::getTokenString($this->lastToken, $packet->ip)){
					break;
				}
				$pk = new QueryPacket;
				$pk->ip = $packet->ip;
				$pk->port = $packet->port;
				$pk->packetType = QueryPacket::STATISTICS;
				$pk->sessionID = $packet->sessionID;
				if(strlen($packet->payload) === 8){
					if($this->timeout < microtime(true)){
						$this->regenerateInfo();
					}
					$pk->payload = $this->longData;
				} else{
					$pk->payload = $this->server->name . "\x00" . (($this->server->gamemode & 0x01) === 0 ? "SMP" : "CMP") . "\x00" . $this->server->api->level->getDefault()->getName() . "\x00" . count(Player::$list) . "\x00" . $this->server->maxClients . "\x00" . Utils::writeLShort($this->server->api->getProperty("server-port")) . $this->server->api->getProperty("server-ip", "0.0.0.0") . "\x00";
				}
				$pk->encode();
				$this->server->send($pk);
				break;
		}
	}

}
