<?php

/*

           -
         /   \
      /         \
   /   PocketMine  \
/          MP         \
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

class OrePopulator extends Populator{
	private $oreTypes = array();
	public function populate(Level $level, $chunkX, $chunkZ, Random $random){
		foreach($this->oreTypes as $type){
			$ore = new OreObject($random, $type);
			for($i = 0; $i < $ore->type->clusterCount; ++$i){
				$v = new Vector3(
					$random->nextRange($chunkX << 4, ($chunkX << 4) + 16),
					$random->nextRange($ore->type->minHeight, $ore->type->maxHeight),
					$random->nextRange($chunkZ << 4, ($chunkZ << 4) + 16)
				);
				if($ore->canPlaceObject($level, $v)){
					$ore->placeObject($level, $v);
				}
			}
		}
	}
	
	public function setOreTypes(array $types){
		$this->oreTypes = $types;
	}
}