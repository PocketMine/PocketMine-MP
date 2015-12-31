<?php
namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\Player;

class DaylightDetectorInverted extends DaylightDetector{
	protected $id = self::DAYLIGHT_DETECTOR_INVERTED;

	public function calculatePower(){
		return 15 - parent::calculatePower();
	}

	public function onActivate(Item $item, Player $player = null){
		$this->getLevel()->setBlock($this, new DaylightDetector(), true, false);
		return true;
	}
}