<?php

/*
 * DevTools plugin for PocketMine-MP
 * Copyright (C) 2014 PocketMine Team <https://github.com/PocketMine/SimpleAuth>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*/

$opts = getopt("", array("out:"));

if(ini_get("phar.readonly") == 1){
	echo "Set phar.readonly to 0 with -dphar.readonly=0\n";
	exit(1);
}

$folderPath = rtrim(str_replace("\\", "/", realpath("./src")), "/") . "/";
$relativePath = rtrim(str_replace("\\", "/", realpath("./")), "/") . "/";
$pharName = isset($opts["out"]) ? $opts["out"] : "PocketMine-MP_Steadfast.phar";



if(!is_dir($folderPath)){
	echo $folderPath ." is not a folder\n";
	exit(1);
}

echo "\nCreating ".$pharName."...\n";
$phar = new \Phar($pharName);

$entry = addslashes(str_replace("\\", "/", "src/PocketMine-MP.php"));
echo "Setting entry point to ".$entry."\n";
$phar->setStub('<?php require("phar://". __FILE__ ."/'.$entry.'"); __HALT_COMPILER();');

$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->startBuffering();
echo "Adding files...\n";
$maxLen = 0;
$count = 0;
foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folderPath)) as $file){
	$path = rtrim(str_replace(array("\\", $relativePath), array("/", ""), $file), "/");
	if($path{0} === "." or strpos($path, "/.") !== false){
		continue;
	}
	$phar->addFile($file, $path);
	if(strlen($path) > $maxLen){
		$maxLen = strlen($path);
	}
	echo "\r[".(++$count)."] ".str_pad($path, $maxLen, " ");
}
echo "\nCompressing...\n";
$phar->compressFiles(\Phar::GZ);
$phar->stopBuffering();

echo "Done!\n";
