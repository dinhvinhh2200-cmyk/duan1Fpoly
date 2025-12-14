<?php
/*
 * PROFILE CONTROLLER
 * -------------------------------------------------------------------------
 * VAI TRÒ VÀ NHIỆM VỤ CHÍNH:
 * 1. Quản lý trang hồ sơ cá nhân: Nơi người dùng xem thông tin của chính mình.
 * 2. Bảo mật: Đảm bảo chỉ người đã đăng nhập mới được vào trang này.
 * 3. Thống kê cá nhân: Tính toán doanh số bán hàng trong ngày của riêng nhân viên đó.
 * 4. Bảo mật tài khoản: Xử lý chức năng đổi mật khẩu (Kiểm tra mật khẩu cũ, mã hóa mật khẩu mới).
 * -------------------------------------------------------------------------
 */

// Định nghĩa class Profile.
// 'extends Controller': Class này kế thừa từ class cha 'Controller' (trong core/Controller.php).
// Việc kế thừa giúp nó sử dụng được các công cụ có sẵn như: $this->model(), $this->view().
class Profile extends Controller {
    
    // Khai báo thuộc tính (biến) để chứa các đối tượng Model sẽ sử dụng.
    // 'private': Phạm vi truy cập riêng tư, chỉ dùng được bên trong class Profile này.
    private $userModel;  // Biến để chứa Model xử lý thông tin người dùng (User).
    private $orderModel; // Biến để chứa Model xử lý đơn hàng (Order).

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm __construct() là hàm đặc biệt, tự động chạy ngay lập tức khi class Profile được gọi.
    public function __construct() {
        
        // 1. KIỂM TRA ĐĂNG NHẬP (Bảo mật lớp đầu tiên)
        // isset(): Hàm kiểm tra xem biến có tồn tại và không phải null hay không.
        // $_SESSION['user_id']: Biến phiên làm việc lưu ID người dùng khi họ đăng nhập thành công.
        // Dấu '!': Phủ định (Nếu KHÔNG tồn tại user_id trong session).
        if (!isset($_SESSION['user_id'])) {
            // header('location: ...'): Lệnh PHP để chuyển hướng trình duyệt sang trang khác.
            // URLROOT: Hằng số chứa đường dẫn gốc của web (vd: http://localhost/ForTime-Coffee).
            header('location: ' . URLROOT . '/auth/login');
            
            // exit: Lệnh dừng ngay lập tức việc thực thi code phía dưới. 
            // Rất quan trọng để ngăn người dùng chưa đăng nhập nhìn thấy nội dung trang profile.
            exit;
        }

        // 2. LOAD CÁC MODEL CẦN THIẾT
        // $this->model(): Hàm này nằm ở Controller cha, giúp nạp file Model và khởi tạo nó.
        $this->userModel = $this->model('UserModel');   // Nạp file app/models/UserModel.php
        $this->orderModel = $this->model('OrderModel'); // Nạp file app/models/OrderModel.php
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Hàm này chạy khi người dùng truy cập vào đường dẫn: /profile
    // Nhiệm vụ: Lấy dữ liệu và hiển thị giao diện.
    public function index() {
        // Lấy ID của người dùng đang đăng nhập từ Session.
        $userId = $_SESSION['user_id'];
        
        // 1. GỌI MODEL ĐỂ LẤY THÔNG TIN USER
        // Gọi hàm findUserById trong UserModel để lấy tên, chức vụ, username... từ Database.
        $user = $this->userModel->findUserById($userId);
        
        // 2. TÍNH DOANH SỐ CÁ NHÂN TRONG NGÀY
        // Gọi hàm phụ (được viết ở cuối file này) để tính tổng tiền bán được hôm nay.
        $salesToday = $this->getPersonalSalesToday($userId);

        // 3. ĐÓNG GÓI DỮ LIỆU (PACKING DATA)
        // Tạo một mảng (Array) chứa tất cả dữ liệu cần gửi sang giao diện (View).
        $data = [
            'user'        => $user,       // Thông tin người dùng
            'sales_today' => $salesToday, // Doanh số hôm nay
        ];

        // 4. HIỂN THỊ GIAO DIỆN
        // Gọi hàm view() từ Controller cha.
        // Tham số 1: Đường dẫn tới file giao diện ('app/views/profile/index.php').
        // Tham số 2: Biến $data chứa dữ liệu để hiển thị lên màn hình.
        $this->view('profile/index', $data);
    }

    // --- HÀM XỬ LÝ ĐỔI MẬT KHẨU ---
    // Hàm này chạy khi người dùng bấm nút "Lưu/Cập nhật" trên form đổi mật khẩu.
    public function change_password() {
        // Kiểm tra xem người dùng có gửi dữ liệu bằng phương thức POST không.
        // (POST là phương thức gửi dữ liệu ngầm, an toàn hơn GET).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy ID người dùng hiện tại để biết đang đổi pass cho ai.
            $userId = $_SESSION['user_id'];
            
            // Lấy dữ liệu từ các ô input trong form HTML.
            // $_POST['...']: Biến toàn cục chứa dữ liệu gửi lên.
            $oldPass = $_POST['old_pass'];      // Mật khẩu cũ nhập vào
            $newPass = $_POST['new_pass'];      // Mật khẩu mới
            $confirmPass = $_POST['confirm_pass']; // Nhập lại mật khẩu mới

            // 1. KIỂM TRA XÁC NHẬN MẬT KHẨU (Validation)
            // So sánh mật khẩu mới và ô nhập lại có khớp nhau không.
            if ($newPass !== $confirmPass) {
                // Nếu không khớp -> Hiện thông báo Javascript và quay lại trang trước.
                echo "<script>alert('Xác nhận mật khẩu mới không khớp!'); window.history.back();</script>";
                return; // Dừng hàm tại đây.
            }

            // 2. LẤY THÔNG TIN USER TỪ DB ĐỂ KIỂM TRA PASS CŨ
            $user = $this->userModel->findUserById($userId);
            
            // password_verify($pass_nhap, $pass_trong_db): Hàm PHP chuyên dụng để kiểm tra mật khẩu.
            // Nó so sánh mật khẩu thô (người dùng nhập) với mật khẩu đã mã hóa (Hash) trong Database.
            if (password_verify($oldPass, $user->password_hash)) {
                
                // 3. MÃ HÓA MẬT KHẨU MỚI (HASHING)
                // Trước khi lưu vào DB, mật khẩu mới PHẢI được mã hóa để bảo mật.
                // password_hash(): Biến đổi "123456" thành chuỗi loằng ngoằng "$2y$10$..."
                $newPassHash = password_hash($newPass, PASSWORD_DEFAULT);
                
                // 4. GỌI MODEL ĐỂ CẬP NHẬT VÀO DATABASE
                if ($this->userModel->changePassword($userId, $newPassHash)) {
                    // Nếu thành công -> Chuyển hướng về trang profile.
                    echo "<script>alert('Đổi mật khẩu thành công!'); window.location.href='".URLROOT."/profile';</script>";
                } else {
                    // Nếu lỗi hệ thống (DB lỗi).
                    echo "<script>alert('Lỗi hệ thống!'); window.history.back();</script>";
                }
            } else {
                // Nếu mật khẩu cũ không đúng.
                echo "<script>alert('Mật khẩu cũ không đúng!'); window.history.back();</script>";
            }
        }
    }

    // --- HÀM PHỤ: TÍNH DOANH SỐ CÁ NHÂN ---
    // 'private': Chỉ dùng nội bộ trong file này, không cho bên ngoài gọi.
    // Chức năng: Tính tổng tiền các đơn hàng mà nhân viên này đã bán trong ngày hôm nay.
    private function getPersonalSalesToday($userId) {
        // Khởi tạo đối tượng Database mới để chạy câu lệnh SQL thủ công.
        // (Vì logic này khá đặc thù cho trang Profile nên viết trực tiếp ở đây cho tiện).
        $db = new Database();
        
        // Soạn câu lệnh SQL:
        // SELECT SUM(final_amount): Tính tổng cột tiền thực thu.
        // FROM orders: Từ bảng đơn hàng.
        // WHERE user_id = :uid: Chỉ lấy đơn của nhân viên này.
        // AND status = 'paid': Chỉ tính đơn đã thanh toán.
        // AND DATE(order_time) = CURDATE(): Chỉ tính đơn có ngày trùng với ngày hiện tại (Hôm nay).
        $db->query("SELECT SUM(final_amount) as total 
                    FROM orders 
                    WHERE user_id = :uid AND status = 'paid' AND DATE(order_time) = CURDATE()");
        
        // Gắn giá trị ID thật vào chỗ trống :uid (Kỹ thuật Bind Parameter để chống hack SQL Injection).
        $db->bind(':uid', $userId);
        
        // Thực thi câu lệnh và lấy về 1 dòng kết quả duy nhất.
        $row = $db->single();
        
        // Trả về kết quả:
        // $row->total: Tổng tiền lấy được.
        // ??: Toán tử Null Coalescing. Nếu $row->total là null (chưa bán được gì) thì trả về 0.
        return $row->total ?? 0;
    }
}