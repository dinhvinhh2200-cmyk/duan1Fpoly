<?php
//Vai trò: Quản lý việc Xác thực người dùng (Authentication).

//Nhiệm vụ chính:

//Đăng nhập (Login): Nhận dữ liệu từ form, kiểm tra tính hợp lệ, gọi Model để xác thực với Database, tạo Session để lưu trạng thái đăng nhập.

//Phân quyền (Authorization): Điều hướng người dùng đến đúng trang (Admin về Dashboard, Staff về POS) dựa trên chức vụ.

//Đăng xuất (Logout): Xóa sạch thông tin phiên làm việc để bảo mật.
// Định nghĩa class AuthController.
// 'class': Khuôn mẫu để tạo ra các đối tượng.
// 'extends Controller': Kế thừa từ lớp cha Controller (trong core/Controller.php).
// Việc kế thừa giúp AuthController dùng được các công cụ của cha như: $this->model(), $this->view().
class AuthController extends Controller {
    
    // Khai báo thuộc tính (biến) $userModel.
    // 'private': Biến này là tài sản riêng, chỉ dùng được bên trong class AuthController này.
    // Nó dùng để chứa đối tượng UserModel (để thao tác với Database).
    private $userModel;

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm __construct() là hàm đặc biệt, tự động chạy ngay khi class này được gọi.
    public function __construct() {
        // Gọi hàm model() (kế thừa từ Controller cha) để nạp file 'app/models/UserModel.php'.
        // Gán kết quả vào biến $this->userModel để dùng xuyên suốt class này.
        $this->userModel = $this->model('UserModel');
    }

    // --- HÀM XỬ LÝ ĐĂNG NHẬP ---
    // Khi người dùng vào đường dẫn /auth/login, hàm này sẽ chạy.
    public function login() {
        // Khởi tạo một mảng (Array) chứa dữ liệu mặc định để gửi sang View.
        // username, password: Để trống ban đầu.
        // error: Để chứa thông báo lỗi nếu đăng nhập sai.
        $data = [
            'username' => '',
            'password' => '',
            'error' => ''
        ];

        // Kiểm tra phương thức gửi dữ liệu.
        // $_SERVER['REQUEST_METHOD']: Biến hệ thống chứa kiểu request (GET hoặc POST).
        // Nếu là 'POST' -> Nghĩa là người dùng đã điền form và bấm nút "Đăng nhập".
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // --- 1. LỌC DỮ LIỆU ĐẦU VÀO (Sanitize) ---
            // filter_input_array: Hàm PHP giúp làm sạch dữ liệu từ $_POST.
            // FILTER_SANITIZE_STRING: Loại bỏ các thẻ HTML độc hại (chống hack XSS cơ bản).
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Lấy dữ liệu người dùng nhập và cắt bỏ khoảng trắng thừa (trim).
            $data['username'] = trim($_POST['username']);
            $data['password'] = trim($_POST['password']);

            // --- 2. KIỂM TRA RỖNG (Validation) ---
            // empty(): Hàm kiểm tra xem biến có rỗng không.
            // Nếu thiếu username HOẶC thiếu password...
            if (empty($data['username']) || empty($data['password'])) {
                // Gán thông báo lỗi vào mảng $data.
                $data['error'] = 'Vui lòng nhập đầy đủ thông tin.';
            } else {
                
                // --- 3. GỌI MODEL ĐỂ KIỂM TRA (Authentication) ---
                // Gọi hàm login() trong UserModel để so sánh với Database.
                // Biến $loggedInUser sẽ chứa thông tin user (nếu đúng) hoặc false (nếu sai).
                $loggedInUser = $this->userModel->login($data['username'], $data['password']);

                // Nếu đăng nhập thành công ($loggedInUser có dữ liệu)...
                if ($loggedInUser) {
                    
                    // --- 4. TẠO SESSION (Lưu trạng thái đăng nhập) ---
                    // $_SESSION: Biến siêu toàn cục, giúp lưu dữ liệu phiên làm việc (giữ đăng nhập khi chuyển trang).
                    
                    // Lưu ID người dùng.
                    $_SESSION['user_id'] = $loggedInUser->user_id;
                    // Lưu chức vụ (1: Admin, 2: Staff...).
                    $_SESSION['role_id'] = $loggedInUser->role_id;
                    // Lưu họ tên hiển thị.
                    $_SESSION['full_name'] = $loggedInUser->full_name;
                    // Lưu tên đăng nhập.
                    $_SESSION['username'] = $loggedInUser->username;

                    // --- 5. ĐIỀU HƯỚNG (Redirect) ---
                    // Kiểm tra chức vụ để chuyển đến trang phù hợp.
                    
                    // Nếu là Admin (role_id == 1)
                    if ($_SESSION['role_id'] == 1) {
                        // Chuyển hướng đến trang Dashboard (Báo cáo).
                        // Hàm redirect() được định nghĩa trong helpers/url_helper.php.
                        redirect('dashboard');
                    } else {
                        // Nếu là Nhân viên (role_id != 1)
                        // Chuyển hướng đến màn hình bán hàng (POS).
                        redirect('pos');
                    }
                } else {
                    // Nếu đăng nhập thất bại (Model trả về false).
                    // Gán thông báo lỗi để hiển thị ra màn hình.
                    $data['error'] = 'Sai tài khoản/mật khẩu hoặc tài khoản bị khóa.';
                }
            }
        }

        // --- 6. HIỂN THỊ GIAO DIỆN (Render View) ---
        // Nếu không phải POST (mới vào trang) hoặc đăng nhập lỗi -> Hiện lại form login.
        // Truyền mảng $data sang View để:
        // - Hiển thị lại username đã nhập (để đỡ phải gõ lại).
        // - Hiển thị thông báo lỗi (nếu có).
        $this->view('auth/login', $data);
    }

    // --- HÀM XỬ LÝ ĐĂNG XUẤT ---
    public function logout() {
        // unset(): Hàm hủy bỏ một biến.
        // Xóa từng biến trong Session để người dùng không còn được xác thực.
        unset($_SESSION['user_id']);    // Xóa ID
        unset($_SESSION['username']);   // Xóa tên đăng nhập
        unset($_SESSION['full_name']);  // Xóa họ tên
        unset($_SESSION['role_id']);    // Xóa quyền hạn
        
        // session_destroy(): Hủy toàn bộ phiên làm việc hiện tại trên server.
        session_destroy();

        // Sau khi xóa sạch, chuyển hướng người dùng về lại trang đăng nhập.
        redirect('auth/login');
    }
}