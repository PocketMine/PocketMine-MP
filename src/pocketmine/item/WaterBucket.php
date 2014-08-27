<?php
namespace pocketmine\item;

use pocketmine\block\Water;
use pocketmine\level\Level;
use pocketmine\Player;

class WaterBucket extends Item{
    public function __construct($meta = 0, $count = 1){
        parent::__construct(self::WATER_BUCKET, $meta, $count, "Water Bucket");
        $this->isActivable = true;
        $this->maxStackSize = 1;
    }

    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
        //Support Make Non-Support Water to Support Water
        if($block->getID() === self::AIR){
            $water = new Water();
            $level->setBlock($block, $water, true, false, true);
            $water->place(clone $this, $block, $target, $face, $fx, $fy, $fz, $player);
            if(($player->gamemode & 0x01) === 0){
                $player->getInventory()->setItemInHand(new Bucket());
            }

            return true;
        }

        return false;
    }
} 