<?php

namespace BeeAZ\AZFishingRod\utils;

class FishManager
{

    private static array $fishNames = [
        1 => ["Cá Chép", "Cá Diếc", "Cá Rô Đồng", "Cá Trê", "Cá Bống", "Cá Mè Hoa", "Cá Trắm Cỏ", "Cá Trắm Đen", "Cá Nheo", "Cá Lóc", "Cá Sặc Rằn", "Cá Lăng", "Cá Tra", "Cá Chim Trắng", "Cá Rô Phi", "Cá Thát Lát", "Cá Bảy Màu", "Cá Đuôi Kiếm", "Cá Mún", "Cá Bình Tích", "Cá Hia", "Cá Ngạnh", "Cá Chạch", "Cá Chốt", "Cá Bò", "Cá Kìm", "Cá Mai", "Cá Cơm", "Cá Linh", "Cá He"],
        2 => ["Cá Hồi", "Cá Tuyết", "Cá Mòi", "Cá Trích", "Cá Bơn", "Cá Hanh", "Cá Chình Sông", "Cá Tầm", "Cá Hồi Vân", "Cá Vược", "Cá Chép Koi", "Cá Rồng Huyết", "Cá La Hán", "Cá Dĩa", "Cá Ông Tiên", "Cá Betta Rồng", "Cá Phượng Hoàng", "Cá Neon Vua", "Cá Thần Tiên", "Cá Tai Tượng", "Cá Hải Tượng", "Cá Sấu Hỏa Tiễn", "Cá Hổ Mekong", "Cá Sam", "Cá Đuối Nước Ngọt", "Cá Phổi", "Cá Khủng Long", "Cá Rồng Ngân", "Cá Rồng Kim", "Cá Hồng Két"],
        3 => ["Mực Ống", "Mực Nang", "Mực Lá", "Bạch Tuộc", "Tôm Hùm Bông", "Cua Hoàng Đế", "Ghẹ Xanh", "Bề Bề", "Sò Điệp", "Hàu Sữa", "Ốc Hương", "Tu Hài", "Bào Ngư", "Vi Cá Mập", "Hải Sâm", "Sứa Biển", "Cầu Gai", "Sao Biển", "San Hô Đỏ", "Ngọc Trai Đen", "Rùa Biển", "Cá Heo", "Cá Voi Sát Thủ", "Cá Mập Trắng", "Cá Nhà Táng", "Cá Kiếm", "Cá Cờ", "Cá Ngừ Đại Dương", "Cá Mặt Trăng", "Cá Đuối Mantas"],
        4 => ["Rồng Biển", "Thủy Quái Nhỏ", "Cá Ma", "Cá Xương", "Cá Quỷ", "Cá Đèn Lồng", "Cá Răng Kiếm", "Cá Vảy Rồng", "Cá Thiết Giáp", "Cá Điện", "Cá Lửa", "Cá Băng", "Cá Độc", "Cá Hắc Ám", "Cá Ánh Sáng", "Cá Thời Gian", "Cá Không Gian", "Cá Hư Không", "Cá Tinh Tú", "Cá Mặt Trời", "Cá Sao Băng", "Cá Thiên Hà", "Cá Vũ Trụ", "Cá Thần", "Cá Thánh", "Cá Tiên", "Cá Rồng Thần", "Bạch Tuộc Khổng Lồ", "Mực Ma Cà Rồng", "Cá Mập Megalodon Con"],
        5 => ["Thần Biển Poseidon", "Thần Neptune", "Thủy Tinh", "Long Vương", "Bạch Long", "Hắc Long", "Thanh Long", "Xích Long", "Hoàng Long", "Cá Voi Xanh Cổ Đại", "Siêu Cá Mập Megalodon", "Thương Long Mosasaurus", "Thằn Lằn Đầu Rắn", "Quái Vật Kraken", "Thủy Quái Leviathan", "Rồng Hydra", "Quái Vật Scylla", "Quái Vật Charybdis", "Rắn Biển Jormungandr", "Thần Cthulhu", "Vua Quái Vật Godzilla", "King Kong Biết Bơi", "Rùa Gamera", "Bướm Mothra", "Thằn Lằn Rodan", "Rồng Ba Đầu Ghidorah", "Người Khổng Lồ Titan", "Quái Thú Colossus", "Quái Thú Behemoth", "Chim Thần Ziz"]
    ];

    public static function getMaxSize(int $tier): float
    {
        return ($tier * 20 + 50) * 2;
    }

    public static function getRandomFish(int $tier): array
    {
        $tier = max(1, min(5, $tier));
        $names = self::$fishNames[$tier];
        $name = $names[array_rand($names)];
        $baseLen = $tier * 20;
        $length = mt_rand($baseLen, $baseLen + 50) + (mt_rand(0, 99) / 100);
        if (mt_rand(1, 100) <= 5) $length *= 2;

        return [
            "name" => $name,
            "length" => round($length, 2),
            "price" => round($length * 0.2, 2)
        ];
    }

    public static function getRodInfo(int $tier): array
    {
        return match ($tier) {
            1 => ["price" => 1000, "currency" => "money", "durability" => 50, "break_chance" => 30],
            2 => ["price" => 50, "currency" => "gold", "durability" => 100, "break_chance" => 25],
            3 => ["price" => 150, "currency" => "gold", "durability" => 200, "break_chance" => 20],
            4 => ["price" => 500, "currency" => "gold", "durability" => 400, "break_chance" => 15],
            5 => ["price" => 1000, "currency" => "gold", "durability" => 1000, "break_chance" => 5],
            default => ["price" => 1000, "currency" => "money", "durability" => 50, "break_chance" => 30],
        };
    }

    public static function getWaitTicks(int $tier): int
    {
        return match ($tier) {
            1 => 600,
            2 => 500,
            3 => 400,
            4 => 360,
            5 => 320,
            default => 600,
        };
    }
}
