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

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", 1);
ini_set('default_charset', 'utf-8');
define("FILE_PATH", dirname(__FILE__)."/../");
set_include_path(get_include_path() . PATH_SEPARATOR . FILE_PATH . PATH_SEPARATOR . FILE_PATH . "/classes/");
ini_set("memory_limit", "1024M");
define("CURRENT_PROTOCOL", 5);
define("CURRENT_VERSION", 1);
define("LOG", true);
define("MAGIC", "\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78");