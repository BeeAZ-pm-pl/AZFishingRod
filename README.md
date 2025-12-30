# 🎣 AZFishingRod - Hệ Thống Câu Cá Custom Đỉnh Cao Cho PMMP 5.0

**AZFishingRod** là giải pháp câu cá cày cuốc toàn diện dành cho máy chủ **PocketMine-MP 5.0**.  
Plugin được phát triển nhằm khơi dậy niềm đam mê làm server Survival / SkyBlock cho cộng đồng PocketMine Việt Nam với cơ chế cực kỳ cuốn hút.

---

## ✨ TÍNH NĂNG NỔI BẬT

### 🎯 Hệ thống Cần câu (Tier 1 - 5)
- Tier 1 mua bằng **Xu (Money)**
- Tier 2 – 5 mua bằng **Gold (tiền tệ cao cấp)**
- Thời gian cá cắn câu giảm dần:
  - Tier 1: 30s
  - Tier 5: 16s

### 🐟 150+ Loài Cá Việt Hóa
- Mỗi tier có **30 loài cá riêng**
- Từ cá ao hồ cho tới cá quái vật, cá thần thoại

### ⚡ BigSize Notification
- Khi câu được cá đạt **kích thước tối đa của cần**
- Phát **sấm sét + thông báo toàn server**

### 🏆 BXH Kì Tích (MySQL)
- Lưu **Top 10 cá to nhất server**
- Tự động lọc, chỉ lưu nếu cá mới **to hơn Top cũ**

### 👑 Sự Kiện “Vua Câu Cá”
- Tự động mỗi ngày: **20:00 – 20:30**
- Cần tối thiểu **5 người**
- Phần thưởng: **5000 Xu**

### 🖥️ Giao Diện UI
- Menu **Mua Cần** & **Bán Cá**
- Thiết kế hiệu ứng ⚡ SẤM SÉT ⚡
- Đóng form tự quay lại menu chính
- Sử dụng thư viện **pmforms**

---

## 📂 CÀI ĐẶT DATABASE (MYSQL)

### Bước 1: Chuẩn bị MySQL
Tạo database (ví dụ: `server_db`) và chạy SQL:

```sql
ALTER TABLE users ADD COLUMN money DOUBLE DEFAULT 0;
ALTER TABLE users ADD COLUMN gold DOUBLE DEFAULT 0;

CREATE TABLE IF NOT EXISTS fishing_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    fish_name VARCHAR(100) NOT NULL,
    fish_length FLOAT NOT NULL,
    caught_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (fish_length)
);
Bước 2: Tạo file kết nối MySQL
Tạo file:

bash
Sao chép mã
plugins/db.php
php
Sao chép mã
<?php
return [
    'host' => '127.0.0.1',
    'user' => 'root',
    'pass' => 'matkhau_sql',
    'name' => 'server_db',
    'port' => 3306
];
🛠️ CHUYỂN TỪ MYSQL SANG YAML (SERVER NHỎ)
A. Khai báo Config (onEnable)
php
Sao chép mã
$this->db = new Config($this->getDataFolder() . "database.yml", Config::YAML);
B. Hàm lưu kỉ tích (saveRecord)
php
Sao chép mã
public function saveRecord(Player $p, string $fishName, float $length): void {
    $top = $this->db->get("top_records", []);
    $top[] = [
        "username" => $p->getName(),
        "fish_name" => $fishName,
        "fish_length" => $length,
        "caught_at" => date("Y-m-d H:i:s")
    ];
    usort($top, fn($a, $b) => $b["fish_length"] <=> $a["fish_length"]);
    $top = array_slice($top, 0, 10);
    $this->db->set("top_records", $top);
    $this->db->save();
}
C. Hiển thị BXH (openLeaderboard)
php
Sao chép mã
$top = $this->db->get("top_records", []);
foreach ($top as $rank => $data) {
    $rankNum = $rank + 1;
    $content .= "#$rankNum {$data['username']} - {$data['fish_name']} ({$data['fish_length']}cm)\n";
}
D. Dùng EconomyAPI thay cho MySQL
php
Sao chép mã
public function addMoney(Player $p, float $amount): void {
    \onebone\economyapi\EconomyAPI::getInstance()->addMoney($p, $amount);
}
📜 LỆNH & QUYỀN HẠN
Lệnh
bash
Sao chép mã
/fishing
Quyền
Sao chép mã
azfishingrod.command
(Mặc định: true)

📦 YÊU CẦU
PocketMine-MP 5.0+

Thư viện pmforms

(Tuỳ chọn) EconomyAPI

MySQL hoặc YAML

👨‍💻 TÁC GIẢ
Developer: BeeAZ

Hy vọng bản share này giúp anh em tìm lại niềm đam mê làm server.
Nếu thấy hay, cho mình xin ⭐ Star trên GitHub để ủng hộ nhé! ❤️
