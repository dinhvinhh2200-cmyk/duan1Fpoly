<?php
/*
 * REQUIRE / BOOTSTRAP FILE
 * (Phần comment này chỉ để giải thích, máy tính sẽ bỏ qua)
 */

// -------------------------------------------------------------------------
// 1. THIẾT LẬP MÔI TRƯỜNG
// -------------------------------------------------------------------------

// Dòng 1: Đặt múi giờ chuẩn là Việt Nam
// Để khi lưu giờ đặt hàng (order_time), nó ra đúng 12:00 chứ không phải giờ Mỹ/Anh.
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Dòng 2-4: Khởi động Session (Phiên làm việc)
// Session giống như một cái túi ảo đi theo người dùng.
// Nó dùng để nhớ: Bạn là ai? Đã đăng nhập chưa? Giỏ hàng có gì?
// session_status() === PHP_SESSION_NONE: Kiểm tra xem cái túi đã được tạo chưa? Nếu chưa thì mới tạo (session_start).
// Tránh lỗi tạo 2 lần gây crash web.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------------------
// 2. LOAD CÁC FILE HỆ THỐNG (CORE FILES)
// -------------------------------------------------------------------------

// Dòng 5: Load file Cấu hình (config.php)
// File này chứa thông tin mật khẩu Database, đường dẫn website...
require_once 'config/config.php';

// Dòng 6: Load các hàm hỗ trợ (Helper)
// Ví dụ hàm redirect() để chuyển trang nhanh gọn.
require_once 'helpers/url_helper.php';

// Dòng 7-9: Load "Bộ 3 Nguyên Tử" của mô hình MVC
// 1. Database.php: Class chuyên xử lý kết nối MySQL (Mở cửa kho dữ liệu).
require_once 'core/Database.php';

// 2. Controller.php: Class cha. Mọi Controller con (như StaffController, PosController) đều phải kế thừa từ ông này.
require_once 'core/Controller.php';

// 3. App.php: Đây chính là bộ định tuyến (Router).
// Nó sẽ đọc URL (ví dụ: /product/add) và quyết định gọi file nào xử lý.
require_once 'core/App.php';