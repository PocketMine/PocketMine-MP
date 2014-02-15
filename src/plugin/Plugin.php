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

interface Plugin{

	public function __construct();
	public function __destruct();

	/**
	 * Called when the plugin is loaded, before calling onLoad()
	 */
	public function onLoad();
	
	public function onEnable();
	
	public function isEnabled();

	public function onDisable();
	
	public function isDisabled();
	
	public function getDataFolder();
	
	public function getDescription();
	
	/**
	 * Gets an embedded resource on the plugin file. 
	 */
	public function getResource($filename);
	
	public function saveResource($filename, $replace = false);	
	
	/**
	 * Returns all the resources incrusted on the plugin
	 */
	public function getResources();
	
	public function getConfig();
	
	public function saveConfig();
	
	public function saveDefaultConfig();
	
	public function reloadConfig();
	
	public function getServer();
	
	public function getName();	

}