<?php
namespace pocketmine\item;

use pocketmine\block\Lava;
use pocketmine\level\Level;
use pocketmine\Player;

class LavaBucket extends Item{
    public function __construct($meta = 0, $count = 1){
        parent::__construct(self::LAVA_BUCKET, $meta, $count, "Lava Bucket");
        $this->isActivable = true;
        $this->maxStackSize = 1;
    }

    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
        if($block->getID() === self::AIR){
            $level->setBlock($block, new Lava(), true, false, true);
            if(($player->gamemode & 0x01) === 0){
                $player->getInventory()->setItemInHand(new Bucket());
            }

            return true;
        }

        return false;
    }
} 