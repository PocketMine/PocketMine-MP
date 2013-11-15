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
require_once(dirname(__FILE__)."/config.php");
require_once(FILE_PATH."/src/utils/TextFormat.php");
require_once(FILE_PATH."/src/functions.php");
/***REM_END***/
define(DATA_PATH, realpath(arg("data-path", FILE_PATH))."/");

if(arg("enable-ansi", strpos(strtoupper(php_uname("s")), "WIN") === 0 ? false:true) === true){
	define("ENABLE_ANSI", true);
}else{
	define("ENABLE_ANSI", false);
}

set_error_handler("error_handler", E_ALL);

$errors = 0;

if(version_compare("5.4.0", PHP_VERSION) > 0){
	console("[ERROR] PHP 버전 5.4.0 이상을 사용하세요.", true, true, 0);
	++$errors;
}

if(php_sapi_name() !== "cli"){
	console("[ERROR] CLI를 사용하여 PocketMine-MP를 실행하세요..", true, true, 0);
	++$errors;
}

if(!extension_loaded("sockets") and @dl((PHP_SHLIB_SUFFIX === "dll" ? "php_":"") . "sockets." . PHP_SHLIB_SUFFIX) === false){
	console("[ERROR] 소켓 확장 프로그램을 찾을 수 없습니다.", true, true, 0);
	++$errors;
}

if(!extension_loaded("pthreads") and @dl((PHP_SHLIB_SUFFIX === "dll" ? "php_":"") . "pthreads." . PHP_SHLIB_SUFFIX) === false){
	console("[ERROR] pthreads 확장 프로그램을 찾을 수 없습니다.", true, true, 0);
	++$errors;
}

if(!extension_loaded("curl") and @dl((PHP_SHLIB_SUFFIX === "dll" ? "php_":"") . "curl." . PHP_SHLIB_SUFFIX) === false){
	console("[ERROR] cURL 확장 프로그램을 찾을 수 없습니다.", true, true, 0);
	++$errors;
}

if(!extension_loaded("sqlite3") and @dl((PHP_SHLIB_SUFFIX === "dll" ? "php_":"") . "sqlite3." . PHP_SHLIB_SUFFIX) === false){
	console("[ERROR] SQLite3 확장 프로그램을 찾을 수 없습니다.", true, true, 0);
	++$errors;
}

if(!extension_loaded("zlib") and @dl((PHP_SHLIB_SUFFIX === "dll" ? "php_":"") . "zlib." . PHP_SHLIB_SUFFIX) === false){
	console("[ERROR] Zlib 확장 프로그램을 찾을 수 없습니다.", true, true, 0);
	++$errors;
}

if($errors > 0){
	console("[ERROR] 홈페이지에서 제공하는 인스톨러를 사용하세요.", true, true, 0);
	exit(1); //Exit with error
}

/***REM_START***/
require_once(FILE_PATH."/src/math/Vector3.php");
require_once(FILE_PATH."/src/world/Position.php");
require_once(FILE_PATH."/src/pmf/PMF.php");

require_all(FILE_PATH . "src/");
/***REM_END***/
