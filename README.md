# 🎣 AZFishingRod - Hệ Thống Câu Cá "Cày Cuốc" Cho PocketMine-MP 5.0

**AZFishingRod** là một plugin câu cá toàn diện được thiết kế để mang lại trải nghiệm cày cuốc thực thụ cho cộng đồng PocketMine Việt Nam. Plugin tích hợp đầy đủ từ hệ thống cần câu phân cấp, cửa hàng UI, sự kiện hằng ngày cho đến bảng xếp hạng kỉ tích lưu trữ qua MySQL.

---

## ✨ Tính Năng Nổi Bật

* **Hệ thống Cần câu 5 Cấp Độ (Tier 1-5):**
    * Cấp 1 mua bằng **Xu (Money)**. Cấp 2-5 mua bằng **Gold (Tiền tệ cao cấp)**.
    * Thời gian chờ cá cắn câu giảm dần theo cấp độ (Cấp 5 giật cực nhanh).
* **150+ Loài Cá Việt Hóa:** 30 loài cá khác nhau cho mỗi cấp độ cần, từ "Cá Rô Đồng" đến "Thần Biển Poseidon".
* **Thông Báo BigSize Thông Minh:** Nổ hiệu ứng sấm sét và thông báo toàn server khi cá đạt ngưỡng kích thước cực đại của cần câu.
* **BXH Kì Tích (Top 10):** Lưu trữ 10 con cá to nhất lịch sử server vào Database (Tự động lọc, chỉ lưu cá đủ to để lọt Top).
* **Sự Kiện "Vua Câu Cá":** Tự động bắt đầu từ **20:00 - 20:30** mỗi ngày. Thưởng nóng 5.000 Xu cho người thắng cuộc.
* **Giao Diện UI (pmforms):** Menu Bán Cá tự động quét túi đồ để bán cá cực nhanh.

---

## 📂 Hướng Dẫn Cài Đặt Database (MySQL)

Plugin được tối ưu để sử dụng **MySQL**. Anh em làm theo các bước sau để thiết lập:

1. **Chuẩn bị:** Tạo một Database tên là `server_db` trong phpMyAdmin.
2. **File Kết Nối:** Tạo file theo đường dẫn `plugins/db.php` và dán nội dung sau:
```php
<?php
return [
    'host' => '127.0.0.1',
    'user' => 'root',
    'pass' => 'matkhau_sql_cua_ban',
    'name' => 'server_db',
    'port' => 3306
];
