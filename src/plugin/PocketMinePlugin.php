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

/***REM_START***/
require_once("Plugin.php");
require_once("PluginDescription.php");
/***REM_END***/

abstract class PocketMinePlugin{
	private $server;
	private $isEnabled = false;
	private $initialized = false;
	private $description;
	private $dataFolder;
	private $config;
	private $configFile;
	private $file;
	
	public function __construct();
	public function __destruct();

	/**
	 * Called when the plugin is loaded, before calling onLoad()
	 */
	public function onLoad(){
	
	}
	
	public function onEnable(){
	
	}
	
	public final function isEnabled(){
		return $this->isEnabled === true;
	}
	
	public final function setEnabled($boolean = true){
		if($this->isEnabled !== $boolean){
			$this->isEnabled = $enabled;
			if($this->isEnabled === true){
				$this->onEnable();
			}else{
				$this->onDisable();
			}
		}
	}

	public function onDisable(){
	
	}
	
	public final function isDisabled(){
		return $this->isEnabled === false;
	}
	
	public final function getDataFolder(){
		return $this->dataFolder;
	}
	
	public final function getDescription(){
		return $this->description;
	}
	
	protected final function initialize(MainServer $server, PluginDescription $description, $dataFolder, $file){
		if($this->initialized === false){
			$this->initialized = true;
			$this->server = $server;
			$this->description = $description;
			$this->dataFolder = $dataFolder;
			$this->file = $file;
			$this->configFile = $this->dataFolder . "config.yml";
		}
	}
	
	public final function isInitialized(){
		return $this->initialized;
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, $args){
		return false;
	}
	
	/* TODO
	public function getCommand($name){
		$this->get
	}*/
	
	/**
	 * Gets an embedded resource on the plugin file. 
	 */
	public function getResource($filename){
		$filename = str_replace("\\", "/", $filename);
		if(isset(static::$resources)){
			if(isset(static::$resources[$filename])){
				return static::$resources[$filename];
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	public function saveResource($filename, $replace = false){
		if(trim($filename) === ""){
			return false;
		}
		
		if(($resource = $this->getResource($filename)) === false){
			return false;
		}
		
		$out = $this->dataFolder . $filename;
		if(!file_exists($this->dataFolder)){
			@mkdir($this->dataFolder, 0755, true);
		}
		
		if(file_exists($out) and $replace !== true){
			return false;
		}
		
		return @file_put_contents($out, $resource) !== false;
	}
	
	/**
	 * Returns all the resources incrusted on the plugin
	 */
	public function getResources();
	
	public function getConfig(){
		if(!isset($this->config)){
			$this->reloadConfig();
		}
		return $this->config;
	}
	
	public function saveConfig(){
		if($this->getConfig()->save() === false){
			console("[SEVERE] Could not save config to ". $this->configFile);
			return false;
		}
		return true;
	}
	
	public function saveDefaultConfig(){
		if(!file_exists($this->configFile)){
			$this->saveResource("config.yml", false);
		}
	}
	
	public function reloadConfig(){
		$this->config = new Config($this->configFile);
		if(($configStream = $this->getResource("config.yml")) !== false){
			$this->config->setDefaults(yaml_parse(config::fixYAMLIndexes($configStream)));
		}
	}
	
	public final function getServer(){
		return $this->dataFolder;
	}
	
	public function getName(){
		return 
	}
	
	protected function getFile(){
		return $this->file;
	}

}