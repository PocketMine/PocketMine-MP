<?php

/*

           -
         /   \
      /         \
   /   PocketMine  \
/          MP         \
|\     @shoghicp     /|
|.   \           /   .|
| ..     \   /     .. |
|    ..    |    ..    |
|       .. | ..       |
\          |          /
   \       |       /
      \    |    /
         \ | /

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.


*/

class PlayerAPI{
	private $server;
	function __construct(PocketMinecraftServer $server){
		$this->server = $server;
	}

	public function init(){
		$this->server->event("server.regeneration", array($this, "handle"));
		$this->server->event("player.login", array($this, "handle"));
		$this->server->api->console->register("list", "Shows connected player list", array($this, "commandHandler"));
		$this->server->api->console->register("kill", "Kills a player", array($this, "commandHandler"));
		$this->server->api->console->register("tppos", "Teleports a player to a position", array($this, "commandHandler"));
		$this->server->api->console->register("tp", "Teleports a player to another player", array($this, "commandHandler"));
		$this->server->api->console->register("ban", "Bans a player", array($this, "commandHandler"));
		$this->server->api->console->register("unban", "Removes a ban from a player", array($this, "commandHandler"));
	}

	public function handle($data, $event){
		switch($event){
			case "server.regeneration":
				$result = $this->server->query("SELECT EID FROM players WHERE EID = (SELECT EID FROM entities WHERE health < 20);");
				if($result !== true and $result !== false){
					while(false !== ($player = $result->fetchArray())){
						if(($player = $this->server->api->player->getByEID($player["EID"])) !== false){
							$player->entity->setHealth(min(20, $player->entity->getHealth() + $data), "regeneration");
						}
					}
				}
				break;
			case "player.login":
				console("player.login called");
				
				$username = $data['username'];
				console("player.login username is ".$username);
				if( $this->checkforban($username) == true ) {
					$this->server->api->player->get($data['username'])->close("You have been banned!");
				}
				break;
				
		}
	}

	public function commandHandler($cmd, $params){
		switch($cmd){
			case "tp":
				$name = array_shift($params);
				$target = array_shift($params);
				if($name == null or $target == null){
					console("[INFO] Usage: /tp <player> <target>");
					break;
				}
				if($this->teleport($name, $target)){
					console("[INFO] \"$name\" teleported to \"$target\"");
				}else{
					console("[ERROR] Couldn't teleport");
				}
				break;
			case "tppos":
				$z = array_pop($params);
				$y = array_pop($params);
				$x = array_pop($params);
				$name = implode(" ", $params);
				if($name == null or $x === null or $y === null or $z === null){
					console("[INFO] Usage: /tp <player> <x> <y> <z>");
					break;
				}
				if($this->tppos($name, $x, $y, $z)){
					console("[INFO] \"$name\" teleported to ($x, $y, $z)");
				}else{
					console("[ERROR] Couldn't teleport");
				}
				break;
			case "kill":
				$player = $this->get(implode(" ", $params));
				if($player !== false){
					$this->server->api->entity->harm($player->eid, 20, "console");
				}else{
					console("[INFO] Usage: /kill <player>");
				}
				break;
			case "list":
				console("[INFO] Player list:");
				foreach($this->server->clients as $c){
					console("[INFO] ".$c->username." (".$c->ip.":".$c->port."), ClientID ".$c->clientID.", (".round($c->entity->x, 2).", ".round($c->entity->y, 2).", ".round($c->entity->z, 2).")");
				}
				break;
			case "ban":
				$player_Ban = array_shift($params);
				console("[INFO] Banning Player: ".$player_Ban);
				$this->ban($player_Ban);
				
				break;
			case "unban":
				$player_unBan = array_shift($params);
				console("[INFO] Un-Banning Player: ".$player_unBan);
				
		}
	}
	
	public function ban($username)
	{
		$fp = fopen("./banned-username.txt", "a");
		if($fp == NULL) {
			console("[INFO] Could not ban: ".$username);
			console("[INFO] Reason: Could not 'open' file 'banned-username.txt'");
			return ;
		}
		
		if( fwrite($fp, $username."\n") == FALSE ) {
			console("[INFO] Could not ban: ".$username);
			console("[INFO] Reason: Could not 'write' file 'banned-username.txt'");
			return ;
		}
		else {
			console("[INFO] Successfully banned: ".$username);
		}
	}
	
	public function checkforban($username)
	{
		console("[DEBUG] username recieved is :".$username.":");
		$fp = fopen("./banned-username.txt", "r");
		if($fp == NULL) {
			console("[INFO] Could not ban: ".$username);
			console("[INFO] Reason: Could not 'open' file 'banned-username.txt'");
			return ;
		}
		
		while(!feof($fp)) {
			$bancheck = fgets($fp, 20);
			$breaks = array("\r\n", "\n", "\r");
			$newbancheck = str_replace($breaks, "", $bancheck);
			console("[DEBUG] Bancheck is :".$bancheck.":");
			if(strcmp($newbancheck, $username) == 0) {
				console("[DEBUG] Bancheck Username is banned");
				return true;
			}
			else {
				console("[DEBUG] Bancheck Username is not banned");
				return false;
			}
		}
	}
		
		

	public function teleport($name, $target){
		$target = $this->get($target);
		if($target !== false){
			return $this->tppos($name, $target->entity->x, $target->entity->y, $target->entity->z);
		}
		return false;
	}

	public function tppos($name, $x, $y, $z){
		$player = $this->get($name);
		if($player !== false){
			$player->dataPacket(MC_MOVE_PLAYER, array(
				"eid" => 0,
				"x" => $x,
				"y" => $y,
				"z" => $z,
				"yaw" => 0,
				"pitch" => 0,
			));
			return true;
		}
		return false;
	}

	public function get($name){
		$CID = $this->server->query("SELECT ip,port FROM players WHERE name = '".str_replace("'", "", $name)."';", true);
		$CID = $this->server->clientID($CID["ip"], $CID["port"]);
		if(isset($this->server->clients[$CID])){
			return $this->server->clients[$CID];
		}
		return false;
	}

	public function getAll(){
		return $this->server->clients;
	}

	public function getByEID($eid){
		$eid = (int) $eid;
		$CID = $this->server->query("SELECT ip,port FROM players WHERE EID = '".$eid."';", true);
		$CID = $this->server->clientID($CID["ip"], $CID["port"]);
		if(isset($this->server->clients[$CID])){
			return $this->server->clients[$CID];
		}
		return false;
	}

	public function getByClientID($clientID){
		$clientID = (int) $clientID;
		$CID = $this->server->query("SELECT ip,port FROM players WHERE clientID = '".$clientID."';", true);
		$CID = $this->server->clientID($CID["ip"], $CID["port"]);
		if(isset($this->server->clients[$CID])){
			return $this->server->clients[$CID];
		}
		return false;
	}

	public function online(){
		$o = array();
		foreach($this->server->clients as $p){
			if($p->auth === true){
				$o[] = $p->username;
			}
		}
		return $o;
	}

	public function add($CID){
		if(isset($this->server->clients[$CID])){
			$player = $this->server->clients[$CID];
			console("[INFO] Player \"".$player->username."\" connected from ".$player->ip.":".$player->port);
			$player->data = $this->getOffline($player->username);
			$this->server->query("INSERT OR REPLACE INTO players (clientID, ip, port, name) VALUES (".$player->clientID.", '".$player->ip."', ".$player->port.", '".$player->username."');");
		}
	}

	public function remove($CID){
		if(isset($this->server->clients[$CID])){
			$player = $this->server->clients[$CID];
			if(is_object($player->entity)){
				$player->entity->close();
			}
			$this->saveOffline($player->username, $player->data);
			$this->server->query("DELETE FROM players WHERE name = '".$player->username."';");
			unset($this->server->entities[$player->eid]);
			unset($this->server->clients[$player->CID]);
		}
	}

	public function getOffline($name){
		if(!file_exists(FILE_PATH."players/".$name.".dat")){
			console("[NOTICE] Player data not found for \"".$name."\", creating new profile");
			$data = array(
				"spawn" => array(
					"x" => $this->server->spawn["x"],
					"y" => $this->server->spawn["y"],
					"z" => $this->server->spawn["z"],
				),
				"health" => 20,
				"lastIP" => "",
				"lastID" => 0,
			);
			$this->saveOffline($name, $data);
		}else{
			$data = unserialize(file_get_contents(FILE_PATH."players/".$name.".dat"));
		}
		$this->server->handle("api.player.offline.get", $data);
		return $data;
	}

	public function saveOffline($name, $data){
		$this->server->handle("api.player.offline.save", $data);
		file_put_contents(FILE_PATH."players/".str_replace("/", "", $name).".dat", serialize($data));
	}
}