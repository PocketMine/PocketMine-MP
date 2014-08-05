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

namespace pocketmine\command\selector;

use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;

abstract class Selector{
	/**
	 * @return string[]
	 */
	public abstract function getNames();
	/**
	 * @param CommandSender $sender
	 * @param array $args
	 * @return string|null
	 */
	public abstract function onRun(CommandSender $sender, array $args);
	public static function checkSelectors(array $args, CommandSender $sender, Player $player){
		foreach($args as $name => $value){
			switch($name){
				case "x":
				case "y":
				case "z":
					if(isset($args["d" . $name])){
						break;
					}
					$delta = 0;
					if($value{0} === "~" and $sender instanceof Position){
						$delta += $player->{$name};
					}
					$actual = $sender->{$name};
					if(((int) $delta) !== ((int) $actual)){
						return false;
					}
					break;
				case "r":
					if($sender instanceof Position){
						if($sender->distance($player) > floatval($value)){
							return false;
						}
						break;
					}
					return false;
				case "rm":
					if($sender instanceof Position){
						if($sender->distance($player) < floatval($value)){
							return false;
						}
						break;
					}
					return false;
				case "m":
					$mode = intval($value);
					if($mode === -1){
						break; // what is the point of adding this (in PC) when they can just safely leave this out?
					}
					if($mode !== $player->getGamemode()){
						return false;
					}
					break;
				case "name":
					if($value !== $sender->getName()){
						return false;
					}
					break;
				case "name!":
					if($value === $sender->getName()){
						return false;
					}
					break;
				// TODO argument "c" (count)
				case "rx":
					if($player->yaw > floatval($value)){
						return false;
					}
					break;
				case "rxm":
					if($player->yaw < floatval($value)){
						return false;
					}
					break;
				case "ry":
					if($player->pitch > floatval($value)){
						return false;
					}
					break;
				case "rym":
					if($player->pitch < floatval($value)){
						return false;
					}
					break;
			}
		}
		foreach(["x", "y", "z"] as $v){
			if(isset($args["d" . $v])){
				if(isset($args[$v])){
					$from = (int) $args[$v];
				}
				elseif($sender instanceof Position){ // lower priority
					$from = $sender->{$v};
				}
				else{
					continue;
				}
				$to = (int) $args["d" . $v];
				$actual = $player->{$v};
				if($from <= $actual and $actual <= $to){
					break;
				}
				return false;
			}
		}
		return true;
	}
}
