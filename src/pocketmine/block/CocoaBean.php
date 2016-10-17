<?php
namespace pocketmine\block;

use pocketmine\event\block\BlockGrowEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;

class CocoaBean extends Flowable{

    public function __construct($meta = 0){
        parent::__construct(127, $meta, "Cocoa Bean");
        $this->hardness = 0.5;
        $this->meta = $meta;
    }

    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
        foreach(Server::getInstance()->getOnlinePlayers() as $p) $p->sendMessage("설치 : ".$this->meta);
        $faces = [
            2 => 0,
            3 => 2,
            4 => 3,
            5 => 1,
        ];
        if(!isset($faces[$face])){
            return false;
        }else{
            $this->meta = $faces[$face];
            $this->getLevel()->setBlock($block, $this, true);
            return true;
        }
    }

    public function onActivate(Item $item, Player $player = null){
        if($item->getID() === Item::DYE and $item->getDamage() === 0x0F){ //Bonemeal
            $block = clone $this;
            $block->meta += 4 * mt_rand(1,2);
            if($block->meta < $block->meta % 4 + 8){
                $block->meta = $block->meta % 4 + 8;
            }
            Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this, $block));
            if(!$ev->isCancelled()){
                $this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
            }
            $item->count--;
            return true;
        }
        return false;
    }

    public function onUpdate($type){
        if($type === Level::BLOCK_UPDATE_NORMAL){
            switch($this->meta % 4){
                case 0:
                    $f = 3;
                break;
                case 1:
                    $f = 4;
                break;
                case 2:
                    $f = 2;
                break;
                case 3:
                    $f = 5;
                break;
                default:
                    $f = false;
            }
            $tree = $this->getSide($f);
            if($tree->getID() != 17 && $tree->getDamage() != 3){
                $this->getLevel()->useBreakOn($this);
                return Level::BLOCK_UPDATE_NORMAL;
            }
        }elseif($type === Level::BLOCK_UPDATE_RANDOM){
            if(mt_rand(0, 4) == 1){
                if($this->meta < $this->meta % 4 + 8){
                    $block = clone $this;
                    $block->meta += 4;
                    Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this, $block));
                    if(!$ev->isCancelled()){
                        $this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
                    }else{
                        return Level::BLOCK_UPDATE_RANDOM;
                    }
                }
            }else{
                return Level::BLOCK_UPDATE_RANDOM;
            }
        }
        return false;
    }

    public function getDrops(Item $item){
        $drops = [];
        if($this->meta >= $this->meta % 4 + 8){
            $drops[] = [Item::DYE, 3, mt_rand(1, 4)];
        }else{
            $drops[] = [Item::DYE, 3, 1];
        }
        return $drops;
    }

    public function getBoundingBox(){
        return null;
    }
}
