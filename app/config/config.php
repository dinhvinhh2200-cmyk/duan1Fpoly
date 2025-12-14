<?php
/*
 * CONFIGURATION FILE
 * Hằng số (Define): Là những giá trị cố định, KHÔNG THỂ thay đổi trong quá trình chạy code.
 */

// ===================================================================================
// 1. CẤU HÌNH CƠ SỞ DỮ LIỆU (DATABASE)
// ===================================================================================
define('DB_HOST', 'localhost');  // Địa chỉ máy chủ chứa DB (Thường là máy của bạn - localhost)
define('DB_USER', 'root');       // Tên đăng nhập vào MySQL (Mặc định XAMPP là root)
define('DB_PASS', '');           // Mật khẩu MySQL (Mặc định XAMPP để trống)
define('DB_NAME', 'f_coffee');   // Tên cái kho dữ liệu bạn đã tạo trong phpMyAdmin

// ===================================================================================
// 2. CẤU HÌNH ĐƯỜNG DẪN (PATH)
// ===================================================================================

// APPROOT: Đường dẫn VẬT LÝ trên ổ cứng máy tính
// dirname(dirname(__FILE__)): Lấy thư mục cha của thư mục chứa file này.
// Kết quả sẽ dạng: C:\xampp\htdocs\ForTime-Coffee\app
// Dùng để: Khi code PHP cần tìm file khác để require (nội bộ máy chủ).
define('APPROOT', dirname(dirname(__FILE__)));

// URLROOT: Đường dẫn TRÌNH DUYỆT (Internet)
// Đây là cái bạn gõ trên Chrome.
// Dùng để: Nhúng file CSS, JS, Ảnh (src="...") hoặc tạo link chuyển trang (href="...").
// LƯU Ý QUAN TRỌNG: Nếu bạn đổi tên thư mục dự án, phải vào đây sửa lại dòng này!
define('URLROOT', 'http://localhost/ForTime-Coffee');

// ===================================================================================
// 3. THÔNG TIN WEBSITE
// ===================================================================================
define('SITENAME', 'ForTime Coffee Management'); // Tên hiện trên tab trình duyệt
define('APPVERSION', '1.0.0');                   // Phiên bản ứng dụng