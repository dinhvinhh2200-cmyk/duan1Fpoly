<?php
/*
 * CORE CONTROLLER
 * Vai trò: Lớp cha (Base Class) mà tất cả Controller con phải kế thừa
 * Chức năng: Cung cấp các phương thức chung để gọi Model, View và các tiện ích bảo mật
 */
class Controller {

    // ============================================================
    // 1. HÀM KHỞI TẠO TỰ ĐỘNG
    // ============================================================
    
    // __construct(): Hàm này chạy TỰ ĐỘNG ngay khi bất kỳ Controller nào được gọi.
    public function __construct() {
        // Gọi ngay hàm kiểm tra tài khoản (được viết ở dưới).
        // Mục đích: Để đảm bảo an toàn, cứ vào trang nào là kiểm tra xem tài khoản còn sống không.
        $this->checkAccountStatus();
    }

    // ============================================================
    // 2. HÀM KIỂM TRA BẢO MẬT (REAL-TIME)
    // ============================================================

    // protected: Chỉ dùng nội bộ trong dòng họ Controller (cha và con).
    protected function checkAccountStatus() {
        // Kiểm tra: Nếu người dùng ĐÃ ĐĂNG NHẬP (có lưu session user_id)
        if (isset($_SESSION['user_id'])) {
            
            // Tạo kết nối đến Database để kiểm tra nóng
            $db = new Database();
            
            // Soạn câu lệnh: "Lấy trạng thái (is_active) của người dùng có ID này"
            $db->query("SELECT is_active FROM users WHERE user_id = :uid");
            
            // Điền ID từ session vào chỗ trống :uid (để chống hack)
            $db->bind(':uid', $_SESSION['user_id']);
            
            // Lấy kết quả 1 dòng
            $user = $db->single();

            // Logic chặn:
            // 1. !$user: Không tìm thấy người này (đã bị xóa khỏi DB).
            // 2. $user->is_active == 0: Tìm thấy nhưng bị khóa (Locked).
            if (!$user || $user->is_deleted == 1) {
                // Xóa sạch phiên làm việc (Đăng xuất ngay lập tức)
                session_unset();
                session_destroy();
                
                // Hiện thông báo và đá về trang đăng nhập bằng Javascript
                echo "<script>
                        alert('Phiên làm việc hết hạn hoặc tài khoản của bạn đã bị khóa!');
                        window.location.href = '" . URLROOT . "/auth/login';
                      </script>";
                
                // exit: Dừng ngay mọi hoạt động của code phía sau (quan trọng).
                exit; 
            }
        }
    }

    // ============================================================
    // 3. CÔNG CỤ: GỌI MODEL (XỬ LÝ DỮ LIỆU)
    // ============================================================

    // Hàm này giúp Controller con gọi Model dễ dàng.
    // Ví dụ dùng: $productModel = $this->model('ProductModel');
    public function model($model) {
        // Kiểm tra file Model có tồn tại trong thư mục app/models/ không?
        if (file_exists('../app/models/' . $model . '.php')) {
            // Nếu có, nạp file đó vào.
            require_once '../app/models/' . $model . '.php';
            
            // Khởi tạo và trả về đối tượng Model đó để sử dụng.
            // Ví dụ: return new ProductModel();
            return new $model();
        } else {
            // Nếu không thấy file -> Báo lỗi chết chương trình.
            die("Model không tồn tại: " . $model);
        }
    }

    // ============================================================
    // 4. CÔNG CỤ: GỌI VIEW (HIỂN THỊ GIAO DIỆN)
    // ============================================================

    // Hàm này giúp hiển thị file giao diện HTML.
    // Tham số $view: Tên file view (vd: 'admin/dashboard').
    // Tham số $data: Mảng dữ liệu muốn gửi sang view (vd: danh sách sản phẩm).
    public function view($view, $data = []) {
        // Kiểm tra file View có tồn tại trong thư mục app/views/ không?
        if (file_exists('../app/views/' . $view . '.php')) {
            // Nếu có, nạp file đó vào.
            // Lúc này file View sẽ chạy và hiển thị HTML ra trình duyệt.
            // Biến $data truyền vào sẽ được dùng bên trong file View này.
            require_once '../app/views/' . $view . '.php';
        } else {
            // Nếu sai đường dẫn -> Báo lỗi.
            die("View không tồn tại: " . $view);
        }
    }

    // ============================================================
    // 5. CÔNG CỤ BẢO MẬT: CHỈ CHO ADMIN
    // ============================================================

    // Hàm này được gọi ở đầu các Controller quan trọng (như Staff, Dashboard).
    public function restrictToAdmin() {
        // Kiểm tra 2 điều kiện:
        // 1. Chưa đăng nhập (!isset...)
        // 2. HOẶC Đã đăng nhập nhưng Role ID khác 1 (Không phải Admin)
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
            // Đá về trang đăng nhập ngay lập tức.
            header('location: ' . URLROOT . '/auth/login');
            exit;
        }
    }
}