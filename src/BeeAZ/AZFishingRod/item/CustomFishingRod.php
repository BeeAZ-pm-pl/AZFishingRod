<?php

namespace BeeAZ\AZFishingRod\item;

use pocketmine\item\FishingRod;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use BeeAZ\AZFishingRod\utils\FishManager;

class CustomFishingRod extends FishingRod
{
    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::FISHING_ROD), "Custom Fishing Rod");
    }

    public function setTier(int $tier): void
    {
        $info = FishManager::getRodInfo($tier);
        $this->getNamedTag()->setInt("tier", $tier);
        $this->setCustomName("§l§e⚡ Cần Câu Cấp $tier ⚡");
        $this->setLore(["§r§fĐộ bền: §a" . $info['durability'], "§r§fTỉ lệ đứt: §c" . $info['break_chance'] . "%"]);
    }

    public function getTier(): int
    {
        return $this->getNamedTag()->getInt("tier", 1);
    }
    public function getMaxDurability(): int
    {
        return FishManager::getRodInfo($this->getTier())['durability'];
    }
}
