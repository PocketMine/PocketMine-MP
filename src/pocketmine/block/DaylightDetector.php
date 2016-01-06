<?php
namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;

class DaylightDetector extends Transparent implements RedstonePowerSource{

	protected $id = self::DAYLIGHT_DETECTOR;
	protected $power;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Daylight Sensor";
	}

	public function getHardness(){
		return 0.2;
	}

	public function getBoundingBox(){
		return new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 0.375,
			$this->z + 1
		);
	}

	public function getToolType(){
		return Tool::TYPE_AXE;
	}

	public function isSolid(){
		return true;
	}

	public function canBeActivated(){
		return true;
	}

	public function getPowerLevel(){
		return $this->meta;
	}

	public function isStronglyPowering(Block $block){
		return false;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
		$this->getLevel()->scheduleUpdate($this, 50);
	}

	public function calculatePower(){
		$timeTick = $this->getLevel()->getTime();
		if($timeTick >= 4300 and $timeTick <= 7720){
			return 15;
		}elseif($timeTick >= 3180 and $timeTick <= 8840){
			return 14;
		}elseif($timeTick >= 2460 and $timeTick <= 9560){
			return 13;
		}elseif($timeTick >= 1880 and $timeTick <= 10140){
			return 12;
		}elseif($timeTick >= 1380 and $timeTick <= 10640){
			return 11;
		}elseif($timeTick >= 940 and $timeTick <= 11080){
			return 10;
		}elseif($timeTick >= 540 and $timeTick <= 11480){
			return 9;
		}elseif($timeTick >= 180 and $timeTick <= 11840){
			return 8;
		}elseif($timeTick >= 0 and $timeTick <= 12040){
			return 7;
		}elseif($timeTick >= 12240 and $timeTick <= 23780){
			return 6;
		}elseif($timeTick >= 12480 and $timeTick <= 23540){
			return 5;
		}elseif($timeTick >= 12720 and $timeTick <= 23300){
			return 4;
		}elseif($timeTick >= 12940 and $timeTick <= 23080){
			return 3;
		}elseif($timeTick >= 13220 and $timeTick <= 22800){
			return 2;
		}elseif(($timeTick >= 13680 and $timeTick <= 13685) and ($timeTick >= 22335 and $timeTick <= 22340)){
			return 1;
		}else{
			return 0;
		}
	}

	public function onUpdate($type){
		parent::onUpdate($type);
		if($type === Level::BLOCK_UPDATE_SCHEDULED){
			$power = $this->calculatePower();
			if($power !== $this->meta){
				$this->getLevel()->setBlock($this, $this);
			}
			$this->getLevel()->scheduleUpdate($this, 50);
		}
	}

	public function onActivate(Item $item, Player $player = null){
		$this->getLevel()->setBlock($this, new DaylightDetectorInverted($this->meta), true, false);
		return true;
	}

	public function getDrops(Item $item){
		if($item->isAxe() >= Tool::TIER_WOODEN){
			return [
				[Item::DAYLIGHT_DETECTOR, 0, 1]
			];
		}else{
			return [];
		}
	}

	public function getPoweringSides(){
		return [];
	}
}
