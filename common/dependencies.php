<?php

/*

-
/   \
/         \
/    POCKET     \
/    MINECRAFT PHP    \
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


require_once(dirname(__FILE__)."/config.php");
require_once("common/functions.php");

$errors = 0;

if(version_compare("5.3.3", PHP_VERSION) > 0){
console("[ERROR] Use PHP >= 5.3.3", true, true, 0);
++$errors;
}

if(version_compare("5.4.0", PHP_VERSION) > 0){
console("[NOTICE] Use PHP >= 5.4.0 to increase performance", true, true, 0);
define("HEX2BIN", false);
}else{
define("HEX2BIN", true);
}

if(php_sapi_name() !== "cli"){
console("[ERROR] Use PHP-CLI to execute the library or create your own", true, true, 0);
++$errors;
}

if(!extension_loaded("sockets")){
console("[ERROR] Unable to find Socket extension", true, true, 0);
++$errors;
}

/*if(!extension_loaded("zlib")){
console("[ERROR] Unable to find Zlib extension", true, true, 0);
++$errors;
}*/

if(!extension_loaded("sqlite3")){
console("[ERROR] Unable to find SQLite3 extension", true, true, 0);
++$errors;
}

if($errors > 0){
die();
}

require_once("classes/Utils.class.php");
require_once("classes/UDPSocket.class.php");
require_once("classes/Packet.class.php");
require_once("classes/Entity.class.php");
require_once("classes/ChunkParser.class.php");
require_once("classes/NBT.class.php");
require_once("classes/SerializedPacketHandler.class.php");
require_once("classes/CustomPacketHandler.class.php");
require_once("classes/MinecraftInterface.class.php");
require_once("classes/BigInteger.class.php");

?>