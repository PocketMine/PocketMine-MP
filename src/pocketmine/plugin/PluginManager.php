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

use pocketmine\command\PluginCommand;
use pocketmine\command\SimpleCommandMap;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerList;
use pocketmine\event\Listener;
use pocketmine\permission\Permissible;
use pocketmine\permission\Permission;
use pocketmine\Server;

/**
 * Manages all the plugins, Permissions and Permissibles
 */
class PluginManager{

	/** @var PluginManager */
	private static $instance = null;

	/** @var Server */
	private $server;

	/** @var SimpleCommandMap */
	private $commandMap;

	/**
	 * @var Plugin[]
	 */
	protected $plugins = array();

	/**
	 * @var Permission[]
	 */
	protected $permissions = array();

	/**
	 * @var Permission[]
	 */
	protected $defaultPerms = array();

	/**
	 * @var Permission[]
	 */
	protected $defaultPermsOp = array();

	/**
	 * @var Permissible[]
	 */
	protected $permSubs = array();

	/**
	 * @var Permissible[]
	 */
	protected $defSubs = array();

	/**
	 * @var Permissible[]
	 */
	protected $defSubsOp = array();

	/**
	 * @var PluginLoader[]
	 */
	protected $fileAssociations = array();

	/**
	 * @return PluginManager
	 */
	public static function getInstance(){
		return self::$instance;
	}

	/**
	 * @param Server           $server
	 * @param SimpleCommandMap $commandMap
	 */
	public function __construct(Server $server, SimpleCommandMap $commandMap){
		$this->server = $server;
		$this->commandMap = $commandMap;
	}

	/**
	 * @param string $name
	 *
	 * @return null|Plugin
	 */
	public function getPlugin($name){
		if(isset($this->plugins[$name])){
			return $this->plugins[$name];
		}

		return null;
	}

	/**
	 * @param string $loaderName A PluginLoader class name
	 *
	 * @return boolean
	 */
	public function registerInterface($loaderName){
		if(is_subclass_of($loaderName, "pocketmine\\plugin\\PluginLoader")){
			$loader = new $loaderName($this->server);
		}else{
			return false;
		}

		$this->fileAssociations[$loaderName] = $loader;

		return true;
	}

	/**
	 * @return Plugin[]
	 */
	public function getPlugins(){
		return $this->plugins;
	}

	/**
	 * @param string $path
	 *
	 * @return Plugin
	 */
	public function loadPlugin($path){
		foreach($this->fileAssociations as $loader){
			if(preg_match($loader->getPluginFilters(), basename($path)) > 0){
				$description = $loader->getPluginDescription($path);
				if($description instanceof PluginDescription){
					if(($plugin = $loader->loadPlugin($path)) instanceof Plugin){
						$this->plugins[$plugin->getDescription()->getName()] = $plugin;

						$pluginCommands = $this->parseYamlCommands($plugin);

						if(count($pluginCommands) > 0){
							$this->commandMap->registerAll($plugin->getDescription()->getName(), $pluginCommands);
						}
						return $plugin;
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param string $directory
	 * @param array $newLoaders
	 *
	 * @return Plugin[]
	 */
	public function loadPlugins($directory, $newLoaders = null){

		if(is_dir($directory)){
			$plugins = array();
			$loadedPlugins = array();
			$dependencies = array();
			$softDependencies = array();
			if(is_array($newLoaders)){
				$loaders = array();
				foreach($newLoaders as $key){
					if(isset($this->fileAssociations[$key])){
						$loaders[$key] = $this->fileAssociations[$key];
					}
				}
			}else{
				$loaders = $this->fileAssociations;
			}
			foreach($loaders as $loader){
				foreach(new \RegexIterator(new \DirectoryIterator($directory), $loader->getPluginFilters()) as $file){
					if($file === "." or $file === ".."){
						continue;
					}
					$file = $directory . $file;
					$description = $loader->getPluginDescription($file);
					if($description instanceof PluginDescription){
						$name = $description->getName();
						if(stripos($name, "pocketmine") !== false or stripos($name, "minecraft") !== false or stripos($name, "mojang") !== false){
							console("[ERROR] Could not load plugin '" . $name . "': restricted name");
							continue;
						}elseif(strpos($name, " ") !== false){
							console("[WARNING] Plugin '" . $name . "' uses spaces in its name, this is discouraged");
						}

						if(isset($plugins[$name]) or $this->getPlugin($name) instanceof Plugin){
							console("[ERROR] Could not load duplicate plugin '" . $name . "': plugin exists");
							continue;
						}

						$compatible = false;
						//Check multiple dependencies
						foreach($description->getCompatibleApis() as $version){
							//Format: majorVersion.minorVersion.patch
							$version = array_map("intval", explode(".", $version));
							$apiVersion = array_map("intval", explode(".", $this->server->getApiVersion()));
							//Completely different API version
							if($version[0] !== $apiVersion[0]){
								continue;
							}
							//If the plugin requires new API features, being backwards compatible
							if($version[1] > $apiVersion[1]){
								continue;
							}

							$compatible = true;
							break;
						}

						if($compatible === false){
							console("[ERROR] Could not load plugin '" . $name . "': API version not compatible");
							continue;
						}

						$plugins[$name] = $file;

						$softDependencies[$name] = (array) $description->getSoftDepend();
						$dependencies[$name] = (array) $description->getDepend();

						foreach($description->getLoadBefore() as $before){
							if(isset($softDependencies[$before])){
								$softDependencies[$before][] = $name;
							}else{
								$softDependencies[$before] = array($name);
							}
						}
					}
				}
			}


			while(count($plugins) > 0){
				$missingDependency = true;
				foreach($plugins as $name => $file){
					if(isset($dependencies[$name])){
						foreach($dependencies[$name] as $key => $dependency){
							if(isset($loadedPlugins[$dependency]) or $this->getPlugin($dependency) instanceof Plugin){
								unset($dependencies[$name][$key]);
							}elseif(!isset($plugins[$dependency])){
								console("[SEVERE] Could not load plugin '" . $name . "': Unknown dependency");
								break;
							}
						}

						if(count($dependencies[$name]) === 0){
							unset($dependencies[$name]);
						}
					}

					if(isset($softDependencies[$name])){
						foreach($softDependencies[$name] as $key => $dependency){
							if(isset($loadedPlugins[$dependency]) or $this->getPlugin($dependency) instanceof Plugin){
								unset($softDependencies[$name][$key]);
							}
						}

						if(count($softDependencies[$name]) === 0){
							unset($softDependencies[$name]);
						}
					}

					if(!isset($dependencies[$name]) and !isset($softDependencies[$name])){
						unset($plugins[$name]);
						$missingDependency = false;
						if($plugin = $this->loadPlugin($file) and $plugin instanceof Plugin){
							$loadedPlugins[$name] = $plugin;
						}else{
							console("[SEVERE] Could not load plugin '" . $name . "'");
						}
					}
				}

				if($missingDependency === true){
					foreach($plugins as $name => $file){
						if(!isset($dependencies[$name])){
							unset($softDependencies[$name]);
							unset($plugins[$name]);
							$missingDependency = false;
							if($plugin = $this->loadPlugin($file) and $plugin instanceof Plugin){
								$loadedPlugins[$name] = $plugin;
							}else{
								console("[SEVERE] Could not load plugin '" . $name . "'");
							}
						}
					}

					//No plugins loaded :(
					if($missingDependency === true){
						foreach($plugins as $name => $file){
							console("[SEVERE] Could not load plugin '" . $name . "': circular dependency detected");
						}
						$plugins = array();
					}
				}
			}

			return $loadedPlugins;
		}else{
			return array();
		}
	}

	/**
	 * @param string $name
	 *
	 * @return null|Permission
	 */
	public function getPermission($name){
		if(isset($this->permissions[$name])){
			return $this->permissions[$name];
		}

		return null;
	}

	/**
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function addPermission(Permission $permission){
		if(!isset($this->permissions[$permission->getName()])){
			$this->permissions[$permission->getName()] = $permission;
			$this->calculatePermissionDefault($permission);

			return true;
		}

		return false;
	}

	/**
	 * @param string|Permission $permission
	 */
	public function removePermission($permission){
		if($permission instanceof Permission){
			unset($this->permissions[$permission->getName()]);
		}else{
			unset($this->permissions[$permission]);
		}
	}

	/**
	 * @param boolean $op
	 *
	 * @return Permission[]
	 */
	public function getDefaultPermissions($op){
		if($op === true){
			return $this->defaultPermsOp;
		}else{
			return $this->defaultPerms;
		}
	}

	/**
	 * @param Permission $permission
	 */
	public function recalculatePermissionDefaults(Permission $permission){
		if(isset($this->permissions[$permission->getName()])){
			unset($this->defaultPermsOp[$permission->getName()]);
			unset($this->defaultPerms[$permission->getName()]);
			$this->calculatePermissionDefault($permission);
		}
	}

	/**
	 * @param Permission $permission
	 */
	private function calculatePermissionDefault(Permission $permission){
		if($permission->getDefault() === Permission::DEFAULT_OP or $permission->getDefault() === Permission::DEFAULT_TRUE){
			$this->defaultPermsOp[$permission->getName()] = $permission;
			$this->dirtyPermissibles(true);
		}

		if($permission->getDefault() === Permission::DEFAULT_NOT_OP or $permission->getDefault() === Permission::DEFAULT_TRUE){
			$this->defaultPerms[$permission->getName()] = $permission;
			$this->dirtyPermissibles(false);
		}
	}

	/**
	 * @param boolean $op
	 */
	private function dirtyPermissibles($op){
		foreach($this->getDefaultPermSubscriptions($op) as $p){
			$p->recalculatePermissions();
		}
	}

	/**
	 * @param string      $permission
	 * @param Permissible $permissible
	 */
	public function subscribeToPermission($permission, Permissible $permissible){
		if(!isset($this->permSubs[$permission])){
			//TODO: Use WeakRef
			$this->permSubs[$permission] = array();
		}
		$this->permSubs[$permission][spl_object_hash($permissible)] = $permissible;
	}

	/**
	 * @param string      $permission
	 * @param Permissible $permissible
	 */
	public function unsubscribeFromPermission($permission, Permissible $permissible){
		if(isset($this->permSubs[$permission])){
			unset($this->permSubs[$permission][spl_object_hash($permissible)]);
		}
	}

	/**
	 * @param string $permission
	 *
	 * @return Permissible[]
	 */
	public function getPermissionSubscriptions($permission){
		if(isset($this->permSubs[$permission])){
			return $this->permSubs[$permission];
		}

		return array();
	}

	/**
	 * @param boolean     $op
	 * @param Permissible $permissible
	 */
	public function subscribeToDefaultPerms($op, Permissible $permissible){
		if($op === true){
			$this->defSubsOp[spl_object_hash($permissible)] = $permissible;
		}else{
			$this->defSubs[spl_object_hash($permissible)] = $permissible;
		}
	}

	/**
	 * @param boolean     $op
	 * @param Permissible $permissible
	 */
	public function unsubscribeFromDefaultPerms($op, Permissible $permissible){
		if($op === true){
			unset($this->defSubsOp[spl_object_hash($permissible)]);
		}else{
			unset($this->defSubs[spl_object_hash($permissible)]);
		}
	}

	/**
	 * @param boolean $op
	 *
	 * @return Permissible[]
	 */
	public function getDefaultPermSubscriptions($op){
		if($op === true){
			return $this->defSubsOp;
		}else{
			return $this->defSubs;
		}
	}

	/**
	 * @return Permission[]
	 */
	public function getPermissions(){
		return $this->permissions;
	}

	/**
	 * @param Plugin $plugin
	 *
	 * @return bool
	 */
	public function isPluginEnabled(Plugin $plugin){
		if($plugin instanceof Plugin and isset($this->plugins[$plugin->getDescription()->getName()])){
			return $plugin->isEnabled();
		}else{
			return false;
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function enablePlugin(Plugin $plugin){
		if(!$plugin->isEnabled()){

			foreach($plugin->getDescription()->getPermissions() as $perm){
				$this->addPermission($perm);
			}

			$plugin->getPluginLoader()->enablePlugin($plugin);
		}
	}

	/**
	 * @param Plugin $plugin
	 *
	 * @return PluginCommand[]
	 */
	protected function parseYamlCommands(Plugin $plugin){
		$pluginCmds = array();

		foreach($plugin->getDescription()->getCommands() as $key => $data){
			if(strpos($key, ":") !== false){
				console("[SEVERE] Could not load command " . $key . " for plugin " . $plugin->getDescription()->getName());
				continue;
			}
			if(is_array($data)){
				$newCmd = new PluginCommand($key, $plugin);
				if(isset($data["description"])){
					$newCmd->setDescription($data["description"]);
				}

				if(isset($data["usage"])){
					$newCmd->setUsage($data["usage"]);
				}

				if(isset($data["aliases"]) and is_array($data["aliases"])){
					$aliasList = array();
					foreach($data["aliases"] as $alias){
						if(strpos($alias, ":") !== false){
							console("[SEVERE] Could not load alias " . $alias . " for plugin " . $plugin->getDescription()->getName());
							continue;
						}
						$aliasList[] = $alias;
					}

					$newCmd->setAliases($aliasList);
				}

				if(isset($data["permission"])){
					$newCmd->setPermission($data["permission"]);
				}

				if(isset($data["permission-message"])){
					$newCmd->setPermissionMessage($data["permission-message"]);
				}

				$pluginCmds[] = $newCmd;
			}
		}

		return $pluginCmds;
	}

	public function disablePlugins(){
		foreach($this->getPlugins() as $plugin){
			$this->disablePlugin($plugin);
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function disablePlugin(Plugin $plugin){
		if($plugin->isEnabled()){
			$plugin->getPluginLoader()->disablePlugin($plugin);
			$this->server->getScheduler()->cancelTasks($plugin);
			HandlerList::unregisterAll($plugin);
			foreach($plugin->getDescription()->getPermissions() as $perm){
				$this->removePermission($perm);
			}
		}
	}

	public function clearPlugins(){
		$this->disablePlugins();
		$this->plugins = array();
		$this->fileAssociations = array();
		$this->permissions = array();
		$this->defaultPerms = array();
		$this->defaultPermsOp = array();
	}

	/**
	 * Calls an event
	 *
	 * @param Event $event
	 */
	public function callEvent(Event $event){
		$this->fireEvent($event);
	}

	private function fireEvent(Event $event){
		$handlers = $event->getHandlers();
		$listeners = $handlers->getRegisteredListeners();

		foreach($listeners as $registration){
			if(!$registration->getPlugin()->isEnabled()){
				continue;
			}
			$registration->callEvent($event);
		}
	}

	/**
	 * Registers all the events in the given Listener class
	 *
	 * @param Listener $listener
	 * @param Plugin   $plugin
	 */
	public function registerEvents(Listener $listener, Plugin $plugin){
		if(!$plugin->isEnabled()){
			trigger_error("Plugin attempted to register " . get_class($listener) . " while not enabled", E_USER_WARNING);

			return;
		}

		$reflection = new \ReflectionClass(get_class($listener));
		foreach($reflection->getMethods() as $method){
			if(!$method->isStatic()){
				$priority = EventPriority::NORMAL;
				$ignoreCancelled = false;
				if(preg_match("/^[\t ]*\\* @priority[\t ]{1,}([a-zA-Z]{1,})$/m", (string) $method->getDocComment(), $matches) > 0){
					$matches[1] = strtoupper($matches[1]);
					if(defined("pocketmine\\event\\EventPriority::" . $matches[1])){
						$priority = constant("pocketmine\\event\\EventPriority::" . $matches[1]);
					}
				}
				if(preg_match("/^[\t ]*\\* @ignoreCancelled[\t ]{1,}([a-zA-Z]{1,})$/m", (string) $method->getDocComment(), $matches) > 0){
					$matches[1] = strtolower($matches[1]);
					if($matches[1] === "false"){
						$ignoreCancelled = false;
					}elseif($matches[1] === "true"){
						$ignoreCancelled = true;
					}
				}
				$parameters = $method->getParameters();
				if(count($parameters) === 1 and $parameters[0]->getClass() instanceof \ReflectionClass and is_subclass_of($parameters[0]->getClass()->getName(), "pocketmine\\event\\Event")){
					$this->registerEvent($parameters[0]->getClass()->getName(), $listener, $priority, new MethodEventExecutor($method->getName()), $plugin, $ignoreCancelled);
				}
			}
		}
	}

	/**
	 * @param string        $event Class name that extends Event
	 * @param Listener      $listener
	 * @param int           $priority
	 * @param EventExecutor $executor
	 * @param Plugin        $plugin
	 * @param bool          $ignoreCancelled
	 */
	public function registerEvent($event, Listener $listener, $priority, EventExecutor $executor, Plugin $plugin, $ignoreCancelled = false){
		if(!is_subclass_of($event, "pocketmine\\event\\Event")){
			trigger_error($event . " is not a valid Event", E_USER_WARNING);

			return;
		}
		if(!$plugin->isEnabled()){
			trigger_error("Plugin attempted to register " . $event . " while not enabled");

			return;
		}

		$this->getEventListeners($event)->register(new RegisteredListener($listener, $executor, $priority, $plugin, $ignoreCancelled));
	}

	/**
	 * @param string $event
	 *
	 * @return HandlerList
	 */
	private function getEventListeners($event){
		if($event::$handlerList === null){
			$event::$handlerList = new HandlerList();
		}

		return $event::$handlerList;
	}

}