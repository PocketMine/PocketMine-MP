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

/***REM_START***/
require_once("src/world/generator/object/tree/TreeObject.php");
/***REM_END***/

class SmallTreeObject extends TreeObject{
	var $type = 0;
	private $trunkHeight = 5;
   private static $leavesHeight = 4; // All trees appear to be 4 tall
   private static $leafRadii = array( 1, 1.41, 2.83, 2.24 );

	private $addLeavesVines = false;
	private $addLogVines = false;
	private $addCocoaPlants = false;

	public function canPlaceObject(Level $level, Vector3 $pos){
		$radiusToCheck = 0;
		for ($yy = 0; $yy < $this->trunkHeight + 3; ++$yy) {
			if ($yy == 1 or $yy === $this->trunkHeight) {
				++$radiusToCheck;
			}
			for($xx = -$radiusToCheck; $xx < ($radiusToCheck + 1); ++$xx){
				for($zz = -$radiusToCheck; $zz < ($radiusToCheck + 1); ++$zz){
					$block = $level->getBlock(new Vector3($pos->x + $xx, $pos->y + $yy, $pos->z + $zz));
					if(!isset($this->overridable[$block->getID()])){
						return false;
					}
				}
			}
		}
		return true;
	}

	public function placeObject(Level $level, Vector3 $pos){
      // The base dirt block
      $dirtpos = new Vector3( $pos->x, $pos->y -1, $pos->z );
		$level->setBlock( $dirtpos, new DirtBlock() );

      // Adjust the tree trunk's height randomly
      //    plot [-14:11] int( x / 8 ) + 5
      //    - min=4 (all leaves are 4 tall, some trunk must show)
      //    - max=6 (top leaves are within ground-level whacking range
      //             on all small trees)
      $heightPre = mt_rand( -14, 11 ); // (TODO: seed may apply)
      $this->trunkHeight = intval( $heightPre / 8 ) + 5;

      // Adjust the starting leaf density using the trunk height as a
      // starting position (tall trees with skimpy leaves don't look
      // too good)
      $leafPre = mt_rand( $this->trunkHeight, 10 ) / 20.0; // (TODO: seed may apply)

      // Now build the tree (from the top down)
      $leaflevel = 0;
      for( $yy = ($this->trunkHeight + 1); $yy >= 0; --$yy )
      {
         if( $leaflevel < self::$leavesHeight )
         {
            // The size is a slight variation on the trunkheight
            $radius = self::$leafRadii[ $leaflevel ] + $leafPre;
            $bRadius = 3;
            for( $xx = -$bRadius; $xx <= $bRadius; ++ $xx )
            {
               for( $zz = -$bRadius; $zz <= $bRadius; ++ $zz )
               {
                  if( sqrt(($xx * $xx) + ($zz * $zz)) <= $radius )
                  {
                     $leafpos = new Vector3( $pos->x + $xx,
                                             $pos->y + $yy,
                                             $pos->z + $zz );
                     $level->setBlock( $leafpos, new LeavesBlock( $this->type ) );
                  }
               }
            }
            $leaflevel ++;
         }

         // Place the trunk last
         if( $leaflevel > 1 )
         {
            $trunkpos = new Vector3( $pos->x, $pos->y + $yy, $pos->z );
            $level->setBlock( $trunkpos, new WoodBlock( $this->type ) );
         }
      }
   }
}