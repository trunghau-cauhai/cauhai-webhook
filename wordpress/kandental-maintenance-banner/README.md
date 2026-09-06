# Kan Dental – Maintenance Banner (WordPress)

Banner thông báo bảo trì cho **kandental.com**. Không phụ thuộc theme, chạy được với mọi page builder (Elementor, WPBakery…), tương thích plugin cache (trạng thái "đã đóng" lưu ở `localStorage` phía client nên không bị cache dính).

## Nội dung

| File | Dùng khi nào |
|---|---|
| `kandental-maintenance-banner.php` | **Khuyến nghị.** Plugin đầy đủ: trang cấu hình trong Admin, 3 chế độ hiển thị, đếm ngược, nút CTA, đổi màu, shortcode. |
| `snippet-functions.php` | Cần nhanh, không muốn cài plugin: dán vào `functions.php` của child theme hoặc WPCode. Chỉ hiển thị ảnh banner + nút đóng. |

## Cài đặt (bản plugin)

**Cách 1 – upload qua Admin**
1. Nén `kandental-maintenance-banner.php` thành `kandental-maintenance-banner.zip`.
2. `Plugins → Add New → Upload Plugin` → chọn file zip → `Install Now` → `Activate`.
3. Vào `Settings → Banner bảo trì` để chỉnh nội dung.

**Cách 2 – FTP/SSH**
```bash
wp-content/plugins/kandental-maintenance-banner/kandental-maintenance-banner.php
```
Rồi kích hoạt trong `Plugins`.

**Cách 3 – must-use plugin (không thể vô tình tắt)**
```bash
wp-content/mu-plugins/kandental-maintenance-banner.php
```
Tự kích hoạt, không cần bật trong danh sách plugin.

## Cấu hình

`Settings → Banner bảo trì`:

- **Chế độ hiển thị**
  - `Ảnh thiết kế sẵn` (mặc định) — render trực tiếp `https://kandental.com/wp-content/uploads/2026/09/WEBSITE.png`, giữ nguyên 100% thiết kế.
  - `Text` — banner tự dựng: tiêu đề, mô tả, đồng hồ đếm ngược, nút CTA. Nhẹ hơn ảnh, responsive tốt hơn trên mobile.
  - `Ảnh + text` — cả hai.
- **Vị trí**: đầu trang hoặc cuối trang; có tùy chọn ghim cố định khi cuộn (tự động chừa chỗ cho admin bar và đẩy nội dung trang xuống, không đè header).
- **Kết thúc bảo trì**: đặt mốc thời gian → banner đếm ngược và **tự ẩn** khi hết hạn (kiểm tra cả server-side lẫn client-side).
- **Cho phép đóng**: khách bấm `×` thì banner không hiện lại. Khi bạn sửa bất kỳ nội dung nào, hash phiên bản đổi → banner hiện lại cho tất cả mọi người.
- **Ẩn với người đăng nhập**: bật khi chỉ muốn cảnh báo khách vãng lai.

Chèn thủ công vào một trang: shortcode `[kandental_banner]`.

## Lưu ý vận hành

- **Ảnh banner**: file PNG thường 1–3 MB, làm chậm LCP toàn site. Nên convert sang WebP và giới hạn < 300 KB trước khi dùng chế độ ảnh; hoặc dùng chế độ `Text` cho mobile.
- **Cache**: sau khi đổi cấu hình, purge cache (WP Rocket / LiteSpeed / Cloudflare) để markup mới lên.
- **Chế độ bảo trì toàn site** (chặn truy cập, trả HTTP 503) là việc khác — banner này chỉ là thông báo, site vẫn hoạt động bình thường.
- Gỡ banner khi xong: tắt checkbox "Kích hoạt banner" hoặc deactivate plugin. Option lưu ở key `kdmb_settings`.
