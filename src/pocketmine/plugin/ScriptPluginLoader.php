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

namespace pocketmine\plugin;

use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\plugin\PluginEnableEvent;
use pocketmine\Server;
use pocketmine\utils\PluginException;

/**
 * Simple script loader, not for plugin development
 * For an example see https://gist.github.com/shoghicp/516105d470cf7d140757
 */
class ScriptPluginLoader implements PluginLoader{

	/** @var Server */
	private $server;

	/**
	 * @param Server $server
	 */
	public function __construct(Server $server){
		$this->server = $server;
	}

	/**
	 * Loads the plugin contained in $file
	 *
	 * @param string $file
	 *
	 * @return Plugin
	 *
	 * @throws \Exception
	 */
	public function loadPlugin($file){
		if(($description = $this->getPluginDescription($file)) instanceof PluginDescription){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.load", [$description->getFullName()]));
			$dataFolder = dirname($file) . DIRECTORY_SEPARATOR . $description->getName();
			if(file_exists($dataFolder) and !is_dir($dataFolder)){
				throw new \InvalidStateException("Projected dataFolder '" . $dataFolder . "' for " . $description->getName() . " exists and is not a directory");
			}

			include_once($file);

			$className = $description->getMain();

			if(class_exists($className, true)){
				$plugin = new $className();
				$this->initPlugin($plugin, $description, $dataFolder, $file);

				return $plugin;
			}else{
				throw new PluginException("Couldn't load plugin " . $description->getName() . ": main class not found");
			}
		}

		return null;
	}

	/**
	 * Gets the PluginDescription from the file
	 *
	 * @param string $file
	 *
	 * @return PluginDescription
	 */
	public function getPluginDescription($file){
		$content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		$data = [];

		$insideHeader = false;
		foreach($content as $line){
			if(!$insideHeader and strpos($line, "/**") !== false){
				$insideHeader = true;
			}

			if(preg_match("/^[ \t]+\\*[ \t]+@([a-zA-Z]+)[ \t]+(.*)$/", $line, $matches) > 0){
				$key = $matches[1];
				$content = trim($matches[2]);

				$data[$key] = $content;
			}

			if($insideHeader and strpos($line, "**/") !== false){
				break;
			}
		}
		if($insideHeader){
			return new PluginDescription($data);
		}

		return null;
	}

	/**
	 * Returns the filename patterns that this loader accepts
	 *
	 * @return array
	 */
	public function getPluginFilters(){
		return "/\\.php$/i";
	}

	/**
	 * @param PluginBase        $plugin
	 * @param PluginDescription $description
	 * @param string            $dataFolder
	 * @param string            $file
	 */
	private function initPlugin(PluginBase $plugin, PluginDescription $description, $dataFolder, $file){
		$plugin->init($this, $this->server, $description, $dataFolder, $file);
		$plugin->onLoad();
	}

	/**
	 * @param Plugin $plugin
	 */
	public function enablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and !$plugin->isEnabled()){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.enable", [$plugin->getDescription()->getFullName()]));

			$plugin->setEnabled(true);

			$this->server->getPluginManager()->callEvent(new PluginEnableEvent($plugin));
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function disablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and $plugin->isEnabled()){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.disable", [$plugin->getDescription()->getFullName()]));

			$this->server->getPluginManager()->callEvent(new PluginDisableEvent($plugin));

			$plugin->setEnabled(false);
		}
	}
}