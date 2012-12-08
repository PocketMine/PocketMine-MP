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

define("MAP_WIDTH", 256);
define("MAP_HEIGHT", 128);

class ChunkParser{
private $raw = b"";
var $sectorLenght = 4096; //16 * 16 * 16
var $chunkLenght = 86016; //21 * $sectorLenght
var $map;

function __construct(){
$map = array();
}

public function loadFile($file){
if(!file_exists($file)){
return false;
}
$this->raw = file_get_contents($file);
$this->chunkLenght = $this->sectorLenght * ord($this->raw{0});
return true;
}

private function getOffsetPosition($X, $Z){
$data = substr($this->raw, ($X << 2) + ($Z << 7), 4); //$X * 4 + $Z * 128
return array(ord($data{0}), ord($data{1}), ord($data{2}), ord($data{3}));
}

private function getOffset($X, $Z){
$info = $this->getOffsetPosition($X, $Z);
return 4096 + (($info[1] * $info[0]) << 12) + (($info[2] * $data[0]) << 16);
}

public function getChunk($X, $Z, $header = true){
$X = (int) $X;
$Z = (int) $Z;
if($header === false){
$add = 4;
}else{
$add = 0;
}
return substr($this->raw, $this->getOffset($X, $Z) + $add, $this->chunkLenght - $add);
}

public function parseChunk($X, $Z){
$X = (int) $X;
$Z = (int) $Z;
$offset = $this->getOffset($X, $Z);
$len = Utils::readLInt(substr($this->raw, $offset, 4));
$offset += 4;
$chunk = array(
0 => array(), //Block
1 => array(), //Data
2 => array(), //SkyLight
3 => array(), //BlockLight
);
foreach($chunk as $section => &$data){
$l = $section === 0 ? 128:64;
for($i = 0; $i < 256; ++$i){
$data[] = substr($this->raw, $offset, $l);
$offset += $l;
}
}
return $chunk;
}

public function getRawColumn($offset, $l){
$data = "";
if($l === 128){
$data = substr($this->raw, $offset, $l);
}elseif($l === 64){
for($i = 0; $i < $l; ++$i){
$d = ord($this->raw{$offset + $i});
$data .= chr($d >> 4);
$data .= chr($d & 0x0F);
}
}
return $data;
}

public function parseColumn($offset, $l){
$data = array();
if($l === 128){
for($i = 0; $i < $l; ++$i){
$data[] = ord($this->raw{$offset + $i});
}
}elseif($l === 64){
for($i = 0; $i < $l; ++$i){
$d = ord($this->raw{$offset + $i});
$data[] = $d >> 4;
$data[] = $d & 0x0F;
}
}
return $data;
}

public function loadMap(){
console("[DEBUG] Loading chunks...", true, true, 2);
for($x = 0; $x < 16; ++$x){
$this->map[$x] = array();
for($z = 0; $z < 16; ++$z){
$this->map[$x][$z] = $this->parseChunk($x, $z);
console("[INTERNAL] Chunk X ".$x." Z ".$z." loaded", true, true, 3);
}
}
console("[DEBUG] Chunks loaded!", true, true, 2);
}

public function getBlock($x, $y, $z){
$x = (int) $x;
$y = (int) $y;
$z = (int) $z;
$X = $x >> 4;
$Z = $z >> 4;
$aX = $x - ($X << 4);
$aZ = $z - ($Z << 4);
$index = $aZ + ($aX << 4);
console("[DEBUG] $x $y $z | $X $Z $index", true, true, 2);
var_dump($this->map[$X][$Z][0][$index]);
$block = ord($this->map[$X][$Z][0][$index]{$y});
//$meta = $this->getOffset($X, $Z) + 4 + (($x << 6) + $y + ($z << 10));
return array($block, 0);
}

}