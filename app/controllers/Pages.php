<?php
//Vai trò: Controller này quản lý trang chủ (Home/Dashboard) của khu vực quản trị hoặc các trang tĩnh khác.

//Nhiệm vụ chính:

//Kiểm tra đăng nhập: Đảm bảo chỉ người dùng đã đăng nhập mới được xem.

//Chuẩn bị dữ liệu: Lấy thông tin người dùng từ session để hiển thị lời chào.

//Hiển thị: Gọi View để hiển thị trang chủ.
// Định nghĩa class Pages.
// 'class': Tạo một lớp đối tượng.
// 'extends Controller': Kế thừa từ class cha Controller để sử dụng các tính năng chung.
class Pages extends Controller {
    
    // --- HÀM KHỞI TẠO (Constructor) ---
    // Chạy tự động ngay khi Controller được gọi.
    public function __construct() {
        // 1. KIỂM TRA ĐĂNG NHẬP (Bảo mật cơ bản)
        // isset($_SESSION['user_id']): Kiểm tra xem trong session có lưu ID người dùng không.
        // Dấu '!': Phủ định (Nếu KHÔNG có session, tức là chưa đăng nhập).
        if (!isset($_SESSION['user_id'])) {
            // Nếu chưa đăng nhập -> Chuyển hướng về trang Login.
            // URLROOT: Hằng số chứa đường dẫn gốc của website (định nghĩa trong config/config.php).
            header('location: ' . URLROOT . '/auth/login');
            
            // exit: Dừng chương trình ngay lập tức để không chạy các lệnh bên dưới (tránh lộ thông tin).
            exit;
        }
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Khi truy cập vào trang chủ (ví dụ: /pages hoặc /), hàm này sẽ chạy.
    // Nhiệm vụ: Hiển thị trang chào mừng và thông tin chung.
    public function index() {
        // Chuẩn bị mảng dữ liệu ($data) để gửi sang View.
        $data = [
            'title'     => 'Trang Quản Trị', // Tiêu đề trang
            'full_name' => $_SESSION['full_name'], // Lấy tên đầy đủ của người dùng từ Session
            // Kiểm tra role_id: Nếu là 1 thì hiển thị 'Quản trị viên', ngược lại là 'Nhân viên'.
            // Toán tử 3 ngôi: (Điều kiện) ? Giá trị đúng : Giá trị sai.
            'role_name' => ($_SESSION['role_id'] == 1) ? 'Quản trị viên (Admin)' : 'Nhân viên'
        ];

        // Gọi hàm view() để hiển thị giao diện.
        // - Tham số 1: Đường dẫn tới file view ('pages/index').
        // - Tham số 2: Mảng dữ liệu $data.
        $this->view('pages/index', $data);
    }
}