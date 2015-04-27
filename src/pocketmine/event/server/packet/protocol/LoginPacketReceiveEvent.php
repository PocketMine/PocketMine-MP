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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\server\packet\protocol;

use pocketmine\network\Network;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\Player;

class LoginPacketReceiveEvent extends DataPacketEvent{
	public static $handlerList = null;

	/** @var LoginPacket */
	private $packet;

	public function __construct(LoginPacket $packet, Player $player){
		parent::__construct($player);
		$this->packet = $packet;
	}

	/**
	 * @return LoginPacket
	 */
	public function getPacket(){
		return $this->packet;
	}

	/**
	 * @return string
	 */
	public function getName(){
		return $this->packet->username;
	}

	/**
	 * @param string $name
	 */
	public function setName($name){
		$this->packet->username = (string) $name;
	}

	/**
	 * @return int
	 */
	public function getProtocol(){
		return $this->packet->protocol1;
	}

	/**
	 * @param int $proto
	 */
	public function setProtocol($proto){
		$this->packet->protocol1 = $this->packet->protocol2 = (int) $proto;
	}

	/**
	 * @return int
	 */
	public function getClientId(){
		return $this->packet->clientId;
	}

	/**
	 * @param int $clientId
	 */
	public function setClientId($clientId){
		$this->packet->clientId = (int) $clientId;
	}

	/**
	 * @return string
	 */
	public function getSkinData(){
		return $this->getProtocol() >= 19 ? $this->packet->skin : null;
	}

	/**
	 * @param string $data
	 */
	public function setSkinData($data){
		if($this->getProtocol() >= 19){
			$this->packet->skin = (string) $data;
		}
	}

	/**
	 * @return bool
	 */
	public function isSkinSlim(){
		return $this->getProtocol() >= 19 ? $this->packet->slim : false;
	}

	/**
	 * @param bool $value
	 */
	public function setSkinSlim($value){
		if($this->getProtocol() >= 19){
			$this->packet->slim = (bool) $value;
		}
	}

	public function handle(Player $player){
		if($this->getProtocol() !== Info::CURRENT_PROTOCOL){
			if($this->getProtocol() < Info::CURRENT_PROTOCOL){
				$message = "disconnectionScreen.outdatedClient";
				$player->dataPacket(
					PlayStatusPacket::create(PlayStatusPacket::LOGIN_FAILED_CLIENT)
					->setChannel(Network::CHANNEL_PRIORITY)
				);
			}else{
				$message = "disconnectionScreen.outdatedServer";
				$player->dataPacket(
					PlayStatusPacket::create(PlayStatusPacket::LOGIN_FAILED_SERVER)
						->setChannel(Network::CHANNEL_PRIORITY)
				);
			}

			$player->close("", $message, false);

			return;
		}


		if(strpos($this->getName(), "\x00") !== false or
			preg_match('#^[a-zA-Z0-9_]{3,16}$#', $this->getName()) === 0 or
			strtolower($this->getName()) === "rcon" or
			strtolower($this->getName()) === "console" or
			strlen($this->getName()) > 16 or strlen($this->getName()) < 3){
			$player->close("", "disconnectionScreen.invalidName");
			return;
		}

		if(strlen($this->getSkinData()) !== 64 * 32 * 4 and strlen($this->getSkinData()) !== 64 * 64 * 4){
			$player->close("", "disconnectionScreen.invalidSkin", false);
			return;
		}

		$player->handleLogin($this);
	}
}
