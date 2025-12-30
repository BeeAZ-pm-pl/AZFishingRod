<?php

namespace BeeAZ\AZFishingRod\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class CustomFishEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;
    private array $loot;
    private int $xp;

    public function __construct(Player $player, array $loot, int $xp)
    {
        $this->player = $player;
        $this->loot = $loot;
        $this->xp = $xp;
    }

    public function getLoot(): array
    {
        return $this->loot;
    }
    public function setLoot(array $loot): void
    {
        $this->loot = $loot;
    }
    public function getXp(): int
    {
        return $this->xp;
    }
    public function setXp(int $xp): void
    {
        $this->xp = $xp;
    }
}
