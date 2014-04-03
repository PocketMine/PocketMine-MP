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

 * 
 *
*/

namespace {
	/**
	 * Output text to the console, can contain Minecraft-formatted text.
	 *
	 * @param string $message
	 * @param bool   $EOL
	 * @param bool   $log
	 * @param int    $level
	 */
	function console($message, $EOL = true, $log = true, $level = 1){
		pocketmine\console($message, $EOL, $log, $level);
	}

	function safe_var_dump(){
		static $cnt = 0;
		foreach(func_get_args() as $var){
			switch(true){
				case is_array($var):
					echo str_repeat("  ", $cnt) . "array(" . count($var) . ") {" . PHP_EOL;
					foreach($var as $key => $value){
						echo str_repeat("  ", $cnt + 1) . "[" . (is_integer($key) ? $key : '"' . $key . '"') . "]=>" . PHP_EOL;
						++$cnt;
						safe_var_dump($value);
						--$cnt;
					}
					echo str_repeat("  ", $cnt) . "}" . PHP_EOL;
					break;
				case is_integer($var):
					echo str_repeat("  ", $cnt) . "int(" . $var . ")" . PHP_EOL;
					break;
				case is_float($var):
					echo str_repeat("  ", $cnt) . "float(" . $var . ")" . PHP_EOL;
					break;
				case is_bool($var):
					echo str_repeat("  ", $cnt) . "bool(" . ($var === true ? "true" : "false") . ")" . PHP_EOL;
					break;
				case is_string($var):
					echo str_repeat("  ", $cnt) . "string(" . strlen($var) . ") \"$var\"" . PHP_EOL;
					break;
				case is_resource($var):
					echo str_repeat("  ", $cnt) . "resource() of type (" . get_resource_type($var) . ")" . PHP_EOL;
					break;
				case is_object($var):
					echo str_repeat("  ", $cnt) . "object(" . get_class($var) . ")" . PHP_EOL;
					break;
				case is_null($var):
					echo str_repeat("  ", $cnt) . "NULL" . PHP_EOL;
					break;
			}
		}
	}

	function dummy(){

	}
}

namespace pocketmine {
	use pocketmine\utils\TextFormat;
	use pocketmine\utils\Utils;
	use pocketmine\wizard\Installer;

	const VERSION = "Alpha_1.4dev";
	const API_VERSION = "1.0.0";
	const CODENAME = "絶好(Zekkou)ケーキ(Cake)";
	const MINECRAFT_VERSION = "v0.8.1 alpha";
	const PHP_VERSION = "5.5";

	@define("pocketmine\\PATH", \getcwd() . DIRECTORY_SEPARATOR);

	if(!class_exists("SplClassLoader", false)){
		require_once(\pocketmine\PATH . "src/spl/SplClassLoader.php");
	}


	$autoloader = new \SplClassLoader();
	$autoloader->add("pocketmine", array(
		\pocketmine\PATH . "src"
	));
	$autoloader->register(true);

	//Startup code. Do not look at it, it can harm you. Most of them are hacks to fix date-related bugs, or basic functions used after this

	set_time_limit(0); //Who set it to 30 seconds?!?!

	if(ini_get("date.timezone") == ""){ //No Timezone set
		date_default_timezone_set("GMT");
		if(strpos(" " . strtoupper(php_uname("s")), " WIN") !== false){
			$time = time();
			$time -= $time % 60;
			//TODO: Parse different time & date formats by region. ¬¬ world
			//Example: USA
			exec("time.exe /T", $hour);
			$i = array_map("intval", explode(":", trim($hour[0])));
			exec("date.exe /T", $date);
			$j = array_map("intval", explode(substr($date[0], 2, 1), trim($date[0])));
			$offset = round((mktime($i[0], $i[1], 0, $j[1], $j[0], $j[2]) - $time) / 60) * 60;
		}else{
			exec("date +%s", $t);
			$offset = round((intval(trim($t[0])) - time()) / 60) * 60;
		}

		$daylight = (int) date("I");
		$d = timezone_name_from_abbr("", $offset, $daylight);
		@ini_set("date.timezone", $d);
		date_default_timezone_set($d);
	}else{
		$d = @date_default_timezone_get();
		if(strpos($d, "/") === false){
			$d = timezone_name_from_abbr($d);
			@ini_set("date.timezone", $d);
			date_default_timezone_set($d);
		}
	}

	gc_enable();
	error_reporting(E_ALL | E_STRICT);
	ini_set("allow_url_fopen", 1);
	ini_set("display_errors", 1);
	ini_set("display_startup_errors", 1);
	ini_set("default_charset", "utf-8");

	ini_set("memory_limit", "128M"); //Default
	define("pocketmine\\START_TIME", microtime(true));

	$opts = getopt("", array("enable-ansi", "disable-ansi", "data:", "plugins:", "no-wizard"));

	define("pocketmine\\DATA", isset($opts["data"]) ? realpath($opts["data"]) . DIRECTORY_SEPARATOR : \getcwd() . DIRECTORY_SEPARATOR);
	define("pocketmine\\PLUGIN_PATH", isset($opts["plugins"]) ? realpath($opts["plugins"]) . DIRECTORY_SEPARATOR : \getcwd() . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR);

	if((strpos(strtoupper(php_uname("s")), "WIN") === false or isset($opts["enable-ansi"])) and !isset($opts["disable-ansi"])){
		define("pocketmine\\ANSI", true);
	}else{
		define("pocketmine\\ANSI", false);
	}

	function kill($pid){
		switch(Utils::getOS()){
			case "win":
				exec("taskkill.exe /F /PID " . ((int) $pid) . " > NUL");
				break;
			case "mac":
			case "linux":
			default:
				exec("kill -9 " . ((int) $pid) . " > /dev/null 2>&1");
		}
	}

	/**
	 * Output text to the console, can contain Minecraft-formatted text.
	 *
	 * @param      $message
	 * @param bool $EOL
	 * @param bool $log
	 * @param int  $level
	 */
	function console($message, $EOL = true, $log = true, $level = 1){
		if(!defined("pocketmine\\DEBUG") or \pocketmine\DEBUG >= $level){
			$message .= $EOL === true ? PHP_EOL : "";
			if($message{0} !== "["){
				$message = "[INFO] $message";
			}
			$time = (\pocketmine\ANSI === true ? TextFormat::AQUA . date("H:i:s") . TextFormat::RESET : date("H:i:s")) . " ";
			$replaced = TextFormat::clean(preg_replace('/\x1b\[[0-9;]*m/', "", $time . $message));
			if($log === true and (!defined("LOG") or LOG === true)){
				log(date("Y-m-d") . " " . $replaced, "server", false, $level);
			}
			if(\pocketmine\ANSI === true){
				$add = "";
				if(preg_match("/^\\[([a-zA-Z0-9]*)\\]/", $message, $matches) > 0){
					switch($matches[1]){
						case "ERROR":
						case "SEVERE":
							$add .= TextFormat::RED;
							break;
						case "TRACE":
						case "INTERNAL":
						case "DEBUG":
							$add .= TextFormat::GRAY;
							break;
						case "WARNING":
							$add .= TextFormat::YELLOW;
							break;
						case "NOTICE":
							$add .= TextFormat::AQUA;
							break;
						default:
							$add = "";
							break;
					}
				}
				$message = TextFormat::toANSI($time . $add . $message . TextFormat::RESET);
			}else{
				$message = $replaced;
			}
			echo $message;
		}
	}

	function getTrace($start = 1){
		$e = new \Exception();
		$trace = $e->getTrace();
		$messages = array();
		$j = 0;
		for($i = (int) $start; isset($trace[$i]); ++$i, ++$j){
			$params = "";
			if(isset($trace[$i]["args"])){
				foreach($trace[$i]["args"] as $name => $value){
					$params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . @strval($value)) . ", ";
				}
			}
			$messages[] = "#$j " . (isset($trace[$i]["file"]) ? $trace[$i]["file"] : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . $trace[$i]["type"] : "") . $trace[$i]["function"] . "(" . substr($params, 0, -2) . ")";
		}

		return $messages;
	}

	function error_handler($errno, $errstr, $errfile, $errline){
		if(error_reporting() === 0){ //@ error-control
			return false;
		}
		$errorConversion = array(
			E_ERROR => "E_ERROR",
			E_WARNING => "E_WARNING",
			E_PARSE => "E_PARSE",
			E_NOTICE => "E_NOTICE",
			E_CORE_ERROR => "E_CORE_ERROR",
			E_CORE_WARNING => "E_CORE_WARNING",
			E_COMPILE_ERROR => "E_COMPILE_ERROR",
			E_COMPILE_WARNING => "E_COMPILE_WARNING",
			E_USER_ERROR => "E_USER_ERROR",
			E_USER_WARNING => "E_USER_WARNING",
			E_USER_NOTICE => "E_USER_NOTICE",
			E_STRICT => "E_STRICT",
			E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
			E_DEPRECATED => "E_DEPRECATED",
			E_USER_DEPRECATED => "E_USER_DEPRECATED",
		);
		$type = ($errno === E_ERROR or $errno === E_WARNING or $errno === E_USER_ERROR or $errno === E_USER_WARNING) ? "ERROR" : "NOTICE";
		$errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
		console("[$type] A $errno error happened: \"$errstr\" in \"$errfile\" at line $errline", true, true, 0);
		foreach(getTrace() as $i => $line){
			console("[TRACE] $line");
		}

		return true;
	}

	function log($message, $name, $EOL = true, $level = 2, $close = false){
		global $fpointers;
		if((!defined("pocketmine\\DEBUG") or \pocketmine\DEBUG >= $level) and (!defined("pocketmine\\LOG") or \pocketmine\LOG === true)){
			$message .= $EOL === true ? PHP_EOL : "";
			if(!isset($fpointers)){
				$fpointers = array();
			}
			if(!isset($fpointers[$name]) or $fpointers[$name] === false){
				$fpointers[$name] = @fopen(\pocketmine\DATA . "/" . $name . ".log", "ab");
			}
			@fwrite($fpointers[$name], $message);
			if($close === true){
				fclose($fpointers[$name]);
				unset($fpointers[$name]);
			}
		}
	}


	set_error_handler("\\pocketmine\\error_handler", E_ALL);

	$errors = 0;

	if(version_compare("5.4.0", PHP_VERSION) > 0){
		console("[ERROR] Use PHP >= 5.4.0", true, true, 0);
		++$errors;
	}

	if(php_sapi_name() !== "cli"){
		console("[ERROR] You must run PocketMine-MP using the CLI.", true, true, 0);
		++$errors;
	}

	if(!extension_loaded("sockets")){
		console("[ERROR] Unable to find the Socket extension.", true, true, 0);
		++$errors;
	}

	if(!extension_loaded("pthreads")){
		console("[ERROR] Unable to find the pthreads extension.", true, true, 0);
		++$errors;
	}else{
		$pthreads_version = phpversion("pthreads");
		if(substr_count($pthreads_version, ".") < 2){
			$pthreads_version = "0.$pthreads_version";
		}
		if(version_compare($pthreads_version, "2.0.4") < 0){
			console("[ERROR] pthreads >= 2.0.4 is required, while you have $pthreads_version.", true, true, 0);
			++$errors;
		}
	}

	if(!extension_loaded("curl")){
		console("[ERROR] Unable to find the cURL extension.", true, true, 0);
		++$errors;
	}

	if(!extension_loaded("sqlite3")){
		console("[ERROR] Unable to find the SQLite3 extension.", true, true, 0);
		++$errors;
	}

	if(!extension_loaded("yaml")){
		console("[ERROR] Unable to find the YAML extension.", true, true, 0);
		++$errors;
	}

	if(!extension_loaded("zlib")){
		console("[ERROR] Unable to find the Zlib extension.", true, true, 0);
		++$errors;
	}

	if($errors > 0){
		console("[ERROR] Please use the installer provided on the homepage, or recompile PHP again.", true, true, 0);
		exit(1); //Exit with error
	}

	if(file_exists(\pocketmine\PATH . ".git/refs/heads/master")){ //Found Git information!
		define("pocketmine\\GIT_COMMIT", strtolower(trim(file_get_contents(\pocketmine\PATH . ".git/refs/heads/master"))));
	}else{ //Unknown :(
		define("pocketmine\\GIT_COMMIT", str_repeat("00", 20));
	}

	@ini_set("opcache.mmap_base", bin2hex(Utils::getRandomBytes(8, false))); //Fix OPCache address errors

	if(!file_exists(\pocketmine\DATA . "server.properties") and !isset($opts["no-wizard"])){
		new Installer();
	}

	if(substr(__FILE__, 0, 7) !== "phar://"){
		console("[WARNING] Non-packaged PocketMine-MP installation detected, do not use on production.");
	}

	$server = new Server($autoloader, \pocketmine\PATH, \pocketmine\DATA, \pocketmine\PLUGIN_PATH);
	$server->start();

	kill(getmypid());
	exit(0);

}