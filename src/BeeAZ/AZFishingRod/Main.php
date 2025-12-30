<?php

namespace BeeAZ\AZFishingRod;

use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\player\Player;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\item\{ItemTypeIds, StringToItemParser, VanillaItems};
use pocketmine\entity\{EntityFactory, EntityDataHelper};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use pocketmine\scheduler\ClosureTask;
use BeeAZ\AZFishingRod\item\CustomFishingRod;
use BeeAZ\AZFishingRod\entity\CustomHook;
use BeeAZ\AZFishingRod\utils\FishManager;
use dktapps\pmforms\{MenuForm, MenuOption, CustomForm, CustomFormResponse};
use dktapps\pmforms\element\{Label, Slider};

class Main extends PluginBase
{
    private static Main $instance;
    public array $fishingSession = [];
    private \mysqli $db;
    private bool $eventActive = false;
    private array $eventParticipants = [];
    private float $maxLen = 0.0;
    private string $winnerName = "";

    public function onEnable(): void
    {
        self::$instance = $this;
        date_default_timezone_set("Asia/Ho_Chi_Minh");

        $dbPath = $this->getServer()->getDataPath() . "plugins/db.php";
        if (!file_exists($dbPath)) {
            $this->getLogger()->error("Missing db.php");
            return;
        }
        $c = include($dbPath);
        $this->db = new \mysqli($c['host'], $c['user'], $c['pass'], $c['name'], $c['port']);

        $this->db->query("CREATE TABLE IF NOT EXISTS fishing_records (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), fish_name VARCHAR(100), fish_length FLOAT, caught_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (fish_length))");

        EntityFactory::getInstance()->register(CustomHook::class, function (World $world, CompoundTag $nbt): CustomHook {
            return new CustomHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['AZFishingHook', 'minecraft:fishing_hook']);

        GlobalItemDataHandlers::getDeserializer()->map(ItemTypeIds::FISHING_ROD, fn() => new CustomFishingRod());
        StringToItemParser::getInstance()->register("custom_fishing_rod", fn() => new CustomFishingRod());
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->checkEventTime()), 1200);
    }

    public static function getInstance(): Main
    {
        return self::$instance;
    }

    public function addMoney(Player $p, float $amount): void
    {
        $this->db->query("UPDATE users SET money = money + $amount WHERE username = '" . strtolower($p->getName()) . "'");
    }

    public function saveRecord(Player $p, string $fishName, float $length): void
    {
        $res = $this->db->query("SELECT fish_length FROM fishing_records ORDER BY fish_length DESC LIMIT 10");
        $shouldSave = false;

        if ($res->num_rows < 10) {
            $shouldSave = true;
        } else {
            $lastLen = 0.0;
            while ($row = $res->fetch_assoc()) $lastLen = (float)$row['fish_length'];
            if ($length > $lastLen) $shouldSave = true;
        }

        if ($shouldSave) {
            $name = $p->getName();
            $stmt = $this->db->prepare("INSERT INTO fishing_records (username, fish_name, fish_length) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $name, $fishName, $length);
            $stmt->execute();
            $stmt->close();

            $this->db->query("DELETE FROM fishing_records WHERE id NOT IN (SELECT id FROM (SELECT id FROM fishing_records ORDER BY fish_length DESC LIMIT 10) t)");

            $p->sendMessage("§l§6⚡ KÌ TÍCH ⚡ §r§eChúc mừng! Cá của bạn đã lọt vào TOP 10 Server!");
        }
    }

    public function reduceMoney(Player $p, float $amount): bool
    {
        $n = strtolower($p->getName());
        $r = $this->db->query("SELECT money FROM users WHERE username = '$n'");
        if ($row = $r->fetch_assoc()) {
            if ($row['money'] >= $amount) {
                $this->db->query("UPDATE users SET money = money - $amount WHERE username = '$n'");
                return true;
            }
        }
        return false;
    }

    public function reduceGold(Player $p, float $amount): bool
    {
        $n = strtolower($p->getName());
        $r = $this->db->query("SELECT gold FROM users WHERE username = '$n'");
        if ($row = $r->fetch_assoc()) {
            if ($row['gold'] >= $amount) {
                $this->db->query("UPDATE users SET gold = gold - $amount WHERE username = '$n'");
                return true;
            }
        }
        return false;
    }

    public function getCurrency(Player $p): array
    {
        $n = strtolower($p->getName());
        $r = $this->db->query("SELECT money, gold FROM users WHERE username = '$n'");
        if ($row = $r->fetch_assoc()) return ["money" => $row['money'], "gold" => $row['gold']];
        return ["money" => 0, "gold" => 0];
    }

    public function onCommand(CommandSender $s, Command $c, string $l, array $a): bool
    {
        if ($s instanceof Player) $this->openMainMenu($s);
        return true;
    }

    public function openMainMenu(Player $p): void
    {
        $cur = $this->getCurrency($p);
        $m = number_format($cur['money']);
        $g = number_format($cur['gold']);

        $p->sendForm(new MenuForm(
            "§l§e⚡ FISHING ⚡",
            "§fTài sản hiện có:\n§aXu: $m\n§6Gold: $g\n\n§7Chọn một tính năng bên dưới:",
            [
                new MenuOption("§l§c⚡ MUA CẦN CÂU ⚡\n§r§8Nâng cấp trang bị"),
                new MenuOption("§l§b⚡ BÁN CÁ ⚡\n§r§8Kiếm tiền nhanh"),
                new MenuOption("§l§6⚡ KÌ TÍCH ⚡\n§r§8Bảng vàng Top 10")
            ],
            function (Player $p, int $sel): void {
                if ($sel === 0) $this->openShopRod($p);
                if ($sel === 1) $this->openSellFish($p);
                if ($sel === 2) $this->openLeaderboard($p);
            }
        ));
    }

    public function openLeaderboard(Player $p): void
    {
        $res = $this->db->query("SELECT username, fish_name, fish_length, caught_at FROM fishing_records ORDER BY fish_length DESC LIMIT 10");
        $content = "§eDanh sách 10 con cá khủng nhất server:\n\n";
        $rank = 1;

        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $color = match ($rank) {
                    1 => "§e",
                    2 => "§7",
                    3 => "§6",
                    default => "§f"
                };
                $date = date("d/m H:i", strtotime($row['caught_at']));
                $content .= "$color#$rank §b{$row['username']} §f- §a{$row['fish_name']} §e({$row['fish_length']}cm)\n§7 $date\n\n";
                $rank++;
            }
        } else {
            $content .= "§cChưa có kỷ lục nào!";
        }

        $p->sendForm(new MenuForm(
            "§l§6⚡ BẢNG VÀNG KÌ TÍCH ⚡",
            $content,
            [new MenuOption("§l§cQUAY LẠI")],
            function (Player $p, int $sel): void {
                $this->openMainMenu($p);
            }
        ));
    }

    public function openShopRod(Player $p): void
    {
        $opts = [];
        for ($i = 1; $i <= 5; $i++) {
            $info = FishManager::getRodInfo($i);
            $price = number_format($info['price']);
            $curr = $info['currency'] === 'gold' ? "§6Gold" : "§aXu";
            $dur = $info['durability'];
            $wait = FishManager::getWaitTicks($i) / 20;
            $opts[] = new MenuOption("§l§0⚡ Cần Cấp $i ⚡\n§r§fGiá: $price $curr §8| §9Wait: {$wait}s");
        }

        $txt = "§fHướng dẫn mua hàng:\n§7- §eCần cấp càng cao §7thì thời gian cá cắn câu càng nhanh.\n§7- §bCần cấp 5 §7có tỉ lệ ra Boss/BigSize cao nhất.\n§7- Cần Cấp 1 mua bằng §aXu§7, Cấp 2-5 mua bằng §6Gold§7.\n";

        $p->sendForm(new MenuForm(
            "§l§0⚡ SHOP CẦN CÂU ⚡",
            $txt,
            $opts,
            function (Player $p, int $sel): void {
                $tier = $sel + 1;
                $info = FishManager::getRodInfo($tier);
                $price = $info['price'];
                $isGold = $info['currency'] === 'gold';
                $success = $isGold ? $this->reduceGold($p, $price) : $this->reduceMoney($p, $price);
                if ($success) {
                    $rod = new CustomFishingRod();
                    $rod->setTier($tier);
                    $p->getInventory()->addItem($rod);
                    $p->sendMessage("§l§a⚡ MUA THÀNH CÔNG! §r§fBạn đã nhận được §eCần Cấp $tier");
                } else $p->sendMessage("§l§c⚡ GIAO DỊCH THẤT BẠI! §r§fBạn không đủ " . ($isGold ? "§6Gold" : "§aXu"));
            },
            function (Player $p): void {
                $this->openMainMenu($p);
            }
        ));
    }

    public function openSellFish(Player $p): void
    {
        $inv = $p->getInventory();
        $sellable = [];
        foreach ($inv->getContents() as $slot => $item) {
            if ($item->getNamedTag()->getTag("fish_price")) {
                $n = $item->getNamedTag()->getString("fish_name");
                $pr = $item->getNamedTag()->getFloat("fish_price");
                if (!isset($sellable[$n])) $sellable[$n] = ["c" => 0, "i" => []];
                $sellable[$n]["c"] += $item->getCount();
                $sellable[$n]["i"][] = ["s" => $slot, "p" => $pr, "c" => $item->getCount()];
            }
        }

        if (empty($sellable)) {
            $p->sendMessage("§l§c⚡ LỖI: §r§fTúi đồ của bạn không có cá!");
            return;
        }
        $opts = [];
        $keys = array_keys($sellable);
        foreach ($sellable as $n => $d) $opts[] = new MenuOption("§l§0⚡ $n ⚡\n§r§fSố lượng: §e" . $d['c']);

        $txt = "§fThông tin thu mua:\n§7- Tại đây hiển thị danh sách các loại cá có trong túi đồ của bạn.\n§7- Giá bán phụ thuộc vào §eKích thước (cm) §7và §bĐộ hiếm §7của cá.\n§7- Chọn một loại cá bên dưới để bán.";

        $p->sendForm(new MenuForm(
            "§l§0⚡ KHO CÁ ⚡",
            $txt,
            $opts,
            function (Player $p, int $sel) use ($keys, $sellable): void {
                $this->confirmSell($p, $keys[$sel], $sellable[$keys[$sel]]);
            },
            function (Player $p): void {
                $this->openMainMenu($p);
            }
        ));
    }

    public function confirmSell(Player $p, string $name, array $data): void
    {
        $p->sendForm(new CustomForm(
            "§l§0⚡ BÁN: $name ⚡",
            [
                new Label("l", "§fLoại cá: §b$name\n§fTổng số lượng: §e" . $data['c'] . "\n\n§7Kéo thanh trượt bên dưới để chọn số lượng muốn bán:"),
                new Slider("a", "Số lượng", 1, $data['c'], 1, $data['c'])
            ],
            function (Player $p, ?CustomFormResponse $r) use ($name, $data): void {
                if ($r === null) {
                    $this->openSellFish($p);
                    return;
                }
                $amt = (int)$r->getFloat("a");
                $total = 0.0;
                $sold = 0;
                $inv = $p->getInventory();
                foreach ($data["i"] as $info) {
                    if ($sold >= $amt) break;
                    $take = min($info["c"], $amt - $sold);
                    $total += $info["p"] * $take;
                    $sold += $take;
                    $it = $inv->getItem($info["s"]);
                    if ($take >= $info["c"]) $inv->setItem($info["s"], VanillaItems::AIR());
                    else {
                        $it->setCount($info["c"] - $take);
                        $inv->setItem($info["s"], $it);
                    }
                }
                $this->addMoney($p, $total);
                $p->sendMessage("§l§a⚡ ĐÃ BÁN XONG! §r§fBạn bán §e$sold $name §fvà nhận được §a$" . number_format($total));
            },
            function (Player $p): void {
                $this->openSellFish($p);
            }
        ));
    }

    public function checkEventTime(): void
    {
        $H = (int)date("H");
        $i = (int)date("i");
        if ($H == 20 && $i < 30) {
            if (!$this->eventActive) {
                $this->eventActive = true;
                $this->eventParticipants = [];
                $this->maxLen = 0.0;
                $this->winnerName = "";
                $this->getServer()->broadcastMessage("§l§0⚡ §6[SỰ KIỆN] §eGIẢI CÂU CÁ ĐÃ BẮT ĐẦU! §0⚡\n§fThời gian: 20:00 - 20:30\n§fPhần thưởng: §a$5,000 Xu");
            }
        } else if ($this->eventActive) {
            $this->eventActive = false;
            $this->getServer()->broadcastMessage("§l§0⚡ §6[SỰ KIỆN] §cĐÃ KẾT THÚC! §0⚡");
            if (count($this->eventParticipants) >= 5 && $this->winnerName !== "") {
                $this->getServer()->broadcastMessage("§l§e🏆 NGƯỜI CHIẾN THẮNG: §b" . $this->winnerName . "\n§fThành tích: Cá dài §a" . $this->maxLen . "cm");
                $this->db->query("UPDATE users SET money = money + 5000 WHERE username = '" . strtolower($this->winnerName) . "'");
            } else $this->getServer()->broadcastMessage("§cGiải đấu bị hủy do không đủ 5 người tham gia.");
        }
    }

    public function registerParticipant(string $n): void
    {
        if ($this->eventActive && !in_array($n, $this->eventParticipants)) $this->eventParticipants[] = $n;
    }
    public function handleEventCatch(Player $p, float $l, string $n): void
    {
        if ($this->eventActive && count($this->eventParticipants) >= 5 && $l > $this->maxLen) {
            $this->maxLen = $l;
            $this->winnerName = $p->getName();
            $this->getServer()->broadcastMessage("§l§6[BXH] §b" . $p->getName() . " §evừa vươn lên TOP 1 với §a$n ($l cm)!");
        }
    }
}


