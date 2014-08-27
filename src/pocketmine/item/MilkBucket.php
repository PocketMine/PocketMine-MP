<?php
namespace pocketmine\item;

class MilkBucket extends Item{
    public function __construct($meta = 0, $count = 1){
        parent::__construct(self::MILK_BUCKET, $meta, $count, "Milk Bucket");
        $this->maxStackSize = 1;
    }
} 