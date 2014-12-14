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

namespace pocketmine\utils;


/**
 * Class Config
 *
 * Config Class for simple config manipulation of multiple formats.
 */
class Config{
	const DETECT = -1; //Detect by file extension
	const PROPERTIES = 0; // .properties
	const CNF = Config::PROPERTIES; // .cnf
	const JSON = 1; // .js, .json
	const YAML = 2; // .yml, .yaml
	//const EXPORT = 3; // .export, .xport
	const SERIALIZED = 4; // .sl
	const ENUM = 5; // .txt, .list, .enum
	const ENUMERATION = Config::ENUM;

	/** @var array */
	private $config = [];
	/** @var string */
	private $file;
	/** @var boolean */
	private $correct = false;
	/** @var integer */
	private $type = Config::DETECT;

	public static $formats = [
		"properties" => Config::PROPERTIES,
		"cnf" => Config::CNF,
		"conf" => Config::CNF,
		"config" => Config::CNF,
		"json" => Config::JSON,
		"js" => Config::JSON,
		"yml" => Config::YAML,
		"yaml" => Config::YAML,
		//"export" => Config::EXPORT,
		//"xport" => Config::EXPORT,
		"sl" => Config::SERIALIZED,
		"serialize" => Config::SERIALIZED,
		"txt" => Config::ENUM,
		"list" => Config::ENUM,
		"enum" => Config::ENUM,
	];

	/**
	 * @param string $file     Path of the file to be loaded
	 * @param int    $type     Config type to load, -1 by default (detect)
	 * @param array  $default  Array with the default values, will be set if not existent
	 * @param null   &$correct Sets correct to true if everything has been loaded correctly
	 */
	public function __construct($file, $type = Config::DETECT, $default = [], &$correct = null){
		$this->load($file, $type, $default);
		$correct = $this->correct;
	}

	/**
	 * Removes all the changes in memory and loads the file again
	 */
	public function reload(){
		$this->config = [];
		$this->correct = false;
		unset($this->type);
		$this->load($this->file);
	}

	/**
	 * @param $str
	 *
	 * @return mixed
	 */
	public static function fixYAMLIndexes($str){
		return preg_replace("#^([ ]*)([a-zA-Z_]{1}[^\:]*)\:#m", "$1\"$2\":", $str);
	}

	/**
	 * @param       $file
	 * @param int   $type
	 * @param array $default
	 *
	 * @return bool
	 */
	public function load($file, $type = Config::DETECT, $default = []){
		$this->correct = true;
		$this->type = (int) $type;
		$this->file = $file;
		if(!is_array($default)){
			$default = [];
		}
		if(!file_exists($file)){
			$this->config = $default;
			$this->save();
		}else{
			if($this->type === Config::DETECT){
				$extension = explode(".", basename($this->file));
				$extension = strtolower(trim(array_pop($extension)));
				if(isset(Config::$formats[$extension])){
					$this->type = Config::$formats[$extension];
				}else{
					$this->correct = false;
				}
			}
			if($this->correct === true){
				$content = @file_get_contents($this->file);
				switch($this->type){
					case Config::PROPERTIES:
					case Config::CNF:
						$this->parseProperties($content);
						break;
					case Config::JSON:
						$this->config = json_decode($content, true);
						break;
					case Config::YAML:
						$content = self::fixYAMLIndexes($content);
						$this->config = yaml_parse($content);
						break;
					case Config::SERIALIZED:
						$this->config = unserialize($content);
						break;
					case Config::ENUM:
						$this->parseList($content);
						break;
					default:
						$this->correct = false;

						return false;
				}
				if(!is_array($this->config)){
					$this->config = $default;
				}
				if($this->fillDefaults($default, $this->config) > 0){
					$this->save();
				}
			}else{
				return false;
			}
		}

		return true;
	}

	/**
	 * @return boolean
	 */
	public function check(){
		return $this->correct === true;
	}

	/**
	 * @return boolean
	 */
	public function save(){
		if($this->correct === true){
			$content = null;
			switch($this->type){
				case Config::PROPERTIES:
				case Config::CNF:
					$content = $this->writeProperties();
					break;
				case Config::JSON:
					$content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING);
					break;
				case Config::YAML:
					$content = yaml_emit($this->config, YAML_UTF8_ENCODING);
					break;
				case Config::SERIALIZED:
					$content = @serialize($this->config);
					break;
				case Config::ENUM:
					$content = implode("\r\n", array_keys($this->config));
					break;
			}
			@file_put_contents($this->file, $content, LOCK_EX);

			return true;
		}else{
			return false;
		}
	}

	/**
	 * @param $k
	 *
	 * @return boolean|mixed
	 */
	public function &__get($k){
		return $this->get($k);
	}

	/**
	 * @param $k
	 * @param $v
	 */
	public function __set($k, $v){
		$this->set($k, $v);
	}

	/**
	 * @param $k
	 *
	 * @return boolean
	 */
	public function __isset($k){
		return $this->exists($k);
	}

	/**
	 * @param $k
	 */
	public function __unset($k){
		$this->remove($k);
	}

	public function setNested($key, $value){
		$vars = explode(".", $key);
		$base = array_shift($vars);

		if(!isset($this->config[$base])){
			$this->config[$base] = [];
		}

		$base =& $this->config[$base];

		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(!isset($base[$baseKey])){
				$base[$baseKey] = [];
			}
			$base =& $base[$baseKey];
		}

		$base = $value;
	}

	public function getNested($key){
		$vars = explode(".", $key);
		$base = array_shift($vars);
		if(isset($this->config[$base])){
			$base = $this->config[$base];
		}else{
			return null;
		}

		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(is_array($base) and isset($base[$baseKey])){
				$base = $base[$baseKey];
			}else{
				return null;
			}
		}

		return $base;
	}

	/**
	 * @param $k
	 *
	 * @return boolean|mixed
	 */
	public function &get($k, $default = false){
		if(isset($this->correct) and ($this->correct === false or !isset($this->config[$k]))){
			$return = $default;

			return $return;
		}

		return $this->config[$k];
	}

	/**
	 * @param string $path
	 *
	 * @return mixed
	 */
	public function &getPath($path){
		$currPath =& $this->config;
		foreach(explode(".", $path) as $component){
			if(isset($currPath[$component])){
				$currPath =& $currPath[$component];
			}else{
				$currPath = null;
			}
		}

		return $currPath;
	}

	/**
	 * @param string $path
	 * @param mixed  $value
	 */
	public function &setPath($path, $value){
		$currPath =& $this->config;
		$components = explode(".", $path);
		$final = array_pop($components);
		foreach($components as $component){
			if(!isset($currPath[$component])){
				$currPath[$component] = [];
			}
			$currPath =& $currPath[$component];
		}
		$currPath[$final] = $value;
	}

	/**
	 * @param string $k key to be set
	 * @param mixed  $v value to set key
	 */
	public function set($k, $v = true){
		$this->config[$k] = $v;
	}

	/**
	 * @param array $v
	 */
	public function setAll($v){
		$this->config = $v;
	}

	/**
	 * @param      $k
	 * @param bool $lowercase If set, searches Config in single-case / lowercase.
	 *
	 * @return boolean
	 */
	public function exists($k, $lowercase = false){
		if($lowercase === true){
			$k = strtolower($k); //Convert requested  key to lower
			$array = array_change_key_case($this->config, CASE_LOWER); //Change all keys in array to lower
			return isset($array[$k]); //Find $k in modified array
		}else{
			return isset($this->config[$k]);
		}
	}

	/**
	 * @param $k
	 */
	public function remove($k){
		unset($this->config[$k]);
	}

	/**
	 * @param bool $keys
	 *
	 * @return array
	 */
	public function getAll($keys = false){
		return ($keys === true ? array_keys($this->config) : $this->config);
	}

	/**
	 * @param array $defaults
	 */
	public function setDefaults(array $defaults){
		$this->fillDefaults($defaults, $this->config);
	}

	/**
	 * @param $default
	 * @param $data
	 *
	 * @return integer
	 */
	private function fillDefaults($default, &$data){
		$changed = 0;
		foreach($default as $k => $v){
			if(is_array($v)){
				if(!isset($data[$k]) or !is_array($data[$k])){
					$data[$k] = [];
				}
				$changed += $this->fillDefaults($v, $data[$k]);
			}elseif(!isset($data[$k])){
				$data[$k] = $v;
				++$changed;
			}
		}

		return $changed;
	}

	/**
	 * @param $content
	 */
	private function parseList($content){
		foreach(explode("\n", trim(str_replace("\r\n", "\n", $content))) as $v){
			$v = trim($v);
			if($v == ""){
				continue;
			}
			$this->config[$v] = true;
		}
	}

	/**
	 * @return string
	 */
	private function writeProperties(){
		$content = "#Properties Config file\r\n#" . date("D M j H:i:s T Y") . "\r\n";
		foreach($this->config as $k => $v){
			if(is_bool($v) === true){
				$v = $v === true ? "on" : "off";
			}elseif(is_array($v)){
				$v = implode(";", $v);
			}
			$content .= $k . "=" . $v . "\r\n";
		}

		return $content;
	}

	/**
	 * @param $content
	 */
	private function parseProperties($content){
		if(preg_match_all('/([a-zA-Z0-9\-_\.]*)=([^\r\n]*)/u', $content, $matches) > 0){ //false or 0 matches
			foreach($matches[1] as $i => $k){
				$v = trim($matches[2][$i]);
				switch(strtolower($v)){
					case "on":
					case "true":
					case "yes":
						$v = true;
						break;
					case "off":
					case "false":
					case "no":
						$v = false;
						break;
				}
				if(isset($this->config[$k])){
					MainLogger::getLogger()->debug("[Config] Repeated property " . $k . " on file " . $this->file);
				}
				$this->config[$k] = $v;
			}
		}
	}

}
