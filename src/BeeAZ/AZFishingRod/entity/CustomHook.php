<?php

namespace BeeAZ\AZFishingRod\entity;

use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\Water;
use pocketmine\block\Block;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\world\sound\BucketEmptyWaterSound;
use pocketmine\world\particle\BubbleParticle;
use pocketmine\world\particle\ExplosionParticle;
use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\ItemBreakSound;
use pocketmine\world\sound\PopSound;
use BeeAZ\AZFishingRod\Main;
use BeeAZ\AZFishingRod\utils\FishManager;
use BeeAZ\AZFishingRod\event\CustomFishEvent;
use pocketmine\world\particle\HugeExplodeParticle;

class CustomHook extends Projectile
{

    public int $rodTier = 1;
    private bool $inWater = false;
    private int $fishTimer = 0;
    private int $waitTimer = 0;
    private int $airTicks = 0;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::FISHING_HOOK;
    }
    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.25, 0.25);
    }
    protected function getInitialDragMultiplier(): float
    {
        return 0.05;
    }
    protected function getInitialGravity(): float
    {
        return 0.08;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->rodTier = $nbt->getInt("RodTier", 1);
        $owner = $this->getOwningEntity();
        if ($owner instanceof Player) $this->getNetworkProperties()->setLong(EntityMetadataProperties::OWNER_EID, $owner->getId());
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setInt("RodTier", $this->rodTier);
        return $nbt;
    }

    protected function onDispose(): void
    {
        $owner = $this->getOwningEntity();
        if ($owner instanceof Player) {
            $name = $owner->getName();
            if (isset(Main::getInstance()->fishingSession[$name]) && Main::getInstance()->fishingSession[$name] === $this->getId()) {
                unset(Main::getInstance()->fishingSession[$name]);
            }
        }
        parent::onDispose();
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $owner = $this->getOwningEntity();
        if ($owner === null || ($owner instanceof Player && (!$owner->isOnline() || !$owner->isAlive()))) {
            $this->flagForDespawn();
            return false;
        }

        $hasUpdate = parent::entityBaseTick($tickDiff);
        $pos = $this->getPosition();
        $block = $this->getWorld()->getBlock($pos);
        $blockUp = $this->getWorld()->getBlock($pos->add(0, 0.8, 0));

        if (($block instanceof Water) || ($blockUp instanceof Water)) {
            $this->airTicks = 0;
            if (!$this->inWater) {
                $this->inWater = true;
                $m = $this->getMotion();
                $m->x = 0;
                $m->z = 0;
                $m->y *= 0.1;
                $this->setMotion($m);
                $this->waitTimer = 0;
                $maxTicks = FishManager::getWaitTicks($this->rodTier);
                $this->fishTimer = mt_rand((int)($maxTicks * 0.9), $maxTicks);
            }
            $this->gravity = -0.02;
            $this->drag = 0.15;
            if ($this->getMotion()->y > 0.05) $this->setMotion($this->getMotion()->multiply(0.8));

            if ($this->fishTimer > 0) {
                $this->fishTimer -= $tickDiff;
                if ($this->fishTimer < 60 && $this->fishTimer % 10 == 0) $this->getWorld()->addParticle($this->getPosition(), new BubbleParticle());
                if ($this->fishTimer <= 0) {
                    $this->getWorld()->addSound($this->getPosition(), new BucketEmptyWaterSound());
                    $this->setMotion($this->getMotion()->subtract(0, 0.25, 0));
                    for ($i = 0; $i < 4; $i++) $this->getWorld()->addParticle($this->getPosition()->add(0, 0.5, 0), new BubbleParticle());
                    $this->waitTimer = 60;
                }
            } elseif ($this->waitTimer > 0) {
                $this->waitTimer -= $tickDiff;
                if ($this->waitTimer <= 0) $this->getWorld()->addSound($this->getPosition(), new PopSound());
            }
        } else {
            $this->airTicks += $tickDiff;
            if ($this->airTicks > 10) {
                $this->inWater = false;
                $this->fishTimer = 0;
                $this->waitTimer = 0;
                $this->gravity = 0.08;
                $this->drag = 0.05;
            } else $this->gravity = 0.04;
        }
        return $hasUpdate;
    }

    public function reelLine(int $tier): void
    {
        $owner = $this->getOwningEntity();
        if ($owner instanceof Player) {
            if ($this->waitTimer > 0) {
                $info = FishManager::getRodInfo($tier);
                if (mt_rand(1, 100) <= $info['break_chance']) {
                    $owner->sendTitle("§c⚡ ĐỨT DÂY! ⚡", "§7Cá quá mạnh...");
                    $this->getWorld()->addSound($this->getPosition(), new ItemBreakSound(VanillaItems::FISHING_ROD()));
                    $this->flagForDespawn();
                    return;
                }

                $fish = FishManager::getRandomFish($tier);
                $name = $fish['name'];
                $len = $fish['length'];
                $price = $fish['price'];

                $item = VanillaItems::RAW_SALMON();
                $item->setCustomName("§r§b$name §e($len cm)");
                $item->setLore(["§r§fGiá bán: §a$$price", "§r§7(Bán tại /fishing)"]);
                $item->getNamedTag()->setFloat("fish_price", $price);
                $item->getNamedTag()->setString("fish_name", $name);

                $ev = new CustomFishEvent($owner, [$item], 10);
                $ev->call();

                if (!$ev->isCancelled()) {
                    foreach ($ev->getLoot() as $i) $owner->getInventory()->addItem($i);
                    Main::getInstance()->saveRecord($owner, $name, $len); 

                    $maxSize = FishManager::getMaxSize($tier);
                    if ($len >= ($maxSize * 0.9)) {
                        $this->getWorld()->addParticle($this->getPosition(), new HugeExplodeParticle());
                        $this->getWorld()->addSound($this->getPosition(), new AnvilUseSound());
                        Main::getInstance()->getServer()->broadcastMessage("§l§0⚡ §6[BIG SIZE] §eNgười chơi §b" . $owner->getName() . " §eđã xuất sắc câu được con cá §c$name §e($len cm) đạt tới giới hạn của cần câu Cấp $tier! §0⚡");
                        $owner->sendTitle("§l§6BIG SIZE!", "§e$name ($len cm)");
                    } else {
                        $owner->sendPopup("§l§aDÍNH! §r§e$name §f($len cm) §7| §a+$price xu");
                    }
                    Main::getInstance()->handleEventCatch($owner, $len, $name);
                }
            } else {
                if ($this->inWater) {
                    if ($this->fishTimer > 0) $owner->sendPopup("§cChưa cắn câu!");
                    else $owner->sendPopup("§cHụt rồi!");
                }
            }
            $this->flagForDespawn();
        }
    }

    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void
    {
        parent::onHitBlock($blockHit, $hitResult);
        $this->setMotion(Vector3::zero());
    }
}
