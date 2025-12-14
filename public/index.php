<?php
// Dòng 1: Gọi file 'require.php' nằm ở thư mục cha (../app/)
// require_once: Lệnh này bảo PHP "Hãy lấy toàn bộ nội dung của file kia dán vào đây". 
// Nếu file kia lỗi hoặc thiếu, chương trình sẽ CHẾT ngay lập tức (đó là ý nghĩa của 'require').
require_once '../app/require.php';

// Dòng 2: Khởi tạo đối tượng chính của ứng dụng
// new App(): Đây là lúc "Bộ não" (Router) của website bắt đầu làm việc.
// Nó sẽ tự động chạy hàm __construct() bên trong file App.php để xem người dùng muốn đi đâu (Dashboard hay POS...).
$init = new App();