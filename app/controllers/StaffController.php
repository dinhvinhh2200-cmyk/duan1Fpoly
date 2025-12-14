<?php
/*
 * STAFF CONTROLLER
 * -------------------------------------------------------------------------
 * VAI TRÒ VÀ NHIỆM VỤ CHÍNH:
 * 1. Quản lý danh sách nhân viên: Hiển thị bảng danh sách tất cả tài khoản.
 * 2. Bảo mật: Chỉ cho phép Admin (Quản trị viên) truy cập vào các chức năng này.
 * 3. Thêm mới (Create): Tạo tài khoản cho nhân viên mới (bao gồm mã hóa mật khẩu).
 * 4. Cập nhật (Update): Sửa thông tin cá nhân, thay đổi chức vụ hoặc khóa tài khoản.
 * 5. Xóa (Delete): Xóa mềm (chuyển vào thùng rác) hoặc khôi phục nhân viên.
 * -------------------------------------------------------------------------
 */

// Định nghĩa class StaffController.
// 'class': Từ khóa để tạo một khuôn mẫu (bản thiết kế) cho đối tượng.
// 'extends Controller': Kế thừa từ class cha 'Controller'.
// Điều này giúp StaffController sở hữu mọi "tài sản" (hàm, biến) của cha nó (như hàm model(), view(), restrictToAdmin()).
class StaffController extends Controller {

    // Khai báo thuộc tính (biến) $userModel.
    // Biến này sẽ được dùng để chứa đối tượng Model (cầu nối với Database).
    private $userModel;

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm __construct() là hàm đặc biệt, nó tự động chạy ngay lập tức khi class này được gọi.
    public function __construct() {
        // 1. BẢO MẬT: Gọi hàm restrictToAdmin() từ class cha Controller.
        // Hàm này sẽ kiểm tra xem người dùng hiện tại có phải là Admin không.
        // Nếu không phải -> Chặn lại và đuổi về trang Login ngay lập tức.
        $this->restrictToAdmin();
        
        // 2. NẠP MODEL:
        // Gọi hàm model() (kế thừa từ cha) để nạp file 'app/models/UserModel.php'.
        // Sau đó gán nó vào biến $this->userModel để dùng cho các hàm bên dưới.
        $this->userModel = $this->model('UserModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Hàm này chạy khi truy cập đường dẫn: /staff
    // Nhiệm vụ: Lấy danh sách nhân viên và hiển thị ra màn hình.
    public function index() {
        // Gọi hàm getAllUsers() trong UserModel để lấy toàn bộ danh sách từ Database.
        $users = $this->userModel->getAllUsers();
        
        // Đóng gói dữ liệu vào một mảng (Array) để gửi sang View.
        $data = ['users' => $users];
        
        // Gọi hàm view() để hiển thị file giao diện 'admin/users/user_index.php'.
        // Truyền kèm mảng $data để bên View có thể hiển thị danh sách lên bảng.
        $this->view('admin/users/user_index', $data);
    }

    // --- HÀM THÊM NHÂN VIÊN MỚI ---
    // Hàm này chạy khi người dùng bấm nút "Lưu/Thêm" trên form thêm mới.
    // Quy trình xử lý: Validate (Kiểm tra) -> Hash Password (Mã hóa) -> Lưu DB.
    public function add() {
        // Kiểm tra xem người dùng có gửi dữ liệu bằng phương thức POST không.
        // (POST là cách gửi dữ liệu ngầm, an toàn và bảo mật hơn GET).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // 1. LỌC DỮ LIỆU ĐẦU VÀO (Sanitize)
            // filter_input_array: Hàm PHP giúp làm sạch dữ liệu từ $_POST.
            // FILTER_SANITIZE_STRING: Loại bỏ các thẻ HTML độc hại (chống hack XSS cơ bản).
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Lấy dữ liệu từ form và cắt bỏ khoảng trắng thừa (trim).
            $username = trim($_POST['username']);
            $password = trim($_POST['password']); // Lấy mật khẩu thô người dùng nhập.

            // --- BẮT ĐẦU KIỂM TRA (VALIDATION) ---

            // 2. Kiểm tra Username hợp lệ (chỉ cho phép chữ cái, số và dấu gạch dưới).
            // preg_match: Hàm kiểm tra chuỗi dựa trên biểu thức quy tắc (Regex).
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                // Nếu sai quy tắc -> Lưu lỗi vào Session.
                $_SESSION['error_username'] = 'Tên đăng nhập không hợp lệ (chỉ được dùng chữ, số, _)!';
                // Quay lại trang danh sách.
                header('location: ' . URLROOT . '/staff');
                exit; // Dừng chương trình.
            }

            // 3. Kiểm tra Trùng tên đăng nhập.
            // Gọi Model để tìm xem username này đã có ai dùng chưa.
            $existingUser = $this->userModel->findUserByUsername($username);
            if ($existingUser) {
                $_SESSION['error_username'] = 'Tên đăng nhập này đã tồn tại! Vui lòng chọn tên khác.';
                header('location: ' . URLROOT . '/staff');
                exit;
            }

            // 4. [QUAN TRỌNG] Kiểm tra Mật khẩu (Bắt buộc nhập khi tạo mới).
            if (empty($password)) {
                $_SESSION['error_password'] = 'Vui lòng nhập mật khẩu cho nhân viên mới!';
                header('location: ' . URLROOT . '/staff');
                exit;
            }

            // 5. CHUẨN BỊ DỮ LIỆU ĐỂ LƯU
            // password_hash(): Hàm PHP dùng để mã hóa mật khẩu.
            // Nó biến "123456" thành một chuỗi ký tự loằng ngoằng không thể đọc được.
            // PASSWORD_DEFAULT: Sử dụng thuật toán mã hóa mạnh nhất hiện có của PHP (thường là Bcrypt).
            $data = [
                'username'  => $username,
                'password'  => password_hash($password, PASSWORD_DEFAULT), // Mật khẩu đã mã hóa.
                'full_name' => trim($_POST['full_name']), // Họ tên đầy đủ.
                'role_id'   => trim($_POST['role_id']),   // Chức vụ (1: Admin, 2: Staff).
                'is_active' => 1 // Trạng thái (1: Hoạt động, 0: Khóa).
            ];

            // 6. GỌI MODEL ĐỂ LƯU VÀO DATABASE
            // Nếu hàm addUser() trả về true (thành công).
            if ($this->userModel->addUser($data)) {
                // Chuyển hướng quay lại trang danh sách.
                header('location: ' . URLROOT . '/staff');
            } else {
                // Nếu lỗi hệ thống (DB chết, sai query...) -> Dừng và báo lỗi.
                die('Lỗi hệ thống khi thêm nhân viên.');
            }
        }
    }

    // --- HÀM CẬP NHẬT THÔNG TIN NHÂN VIÊN ---
    // Nhận tham số $id từ URL (ví dụ: /staff/edit/5 -> $id = 5).
    public function edit($id) {
        // Chỉ xử lý khi có request POST (bấm nút Lưu).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Làm sạch dữ liệu đầu vào.
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Lấy mật khẩu mới (nếu có nhập).
            $password = trim($_POST['password']);
            
            // Chuẩn bị mảng dữ liệu để cập nhật.
            $data = [
                'id'        => $id, // ID của nhân viên cần sửa.
                'full_name' => trim($_POST['full_name']),
                'role_id'   => trim($_POST['role_id']),
                'is_active' => 1,
                // Logic mật khẩu:
                // - Nếu ô mật khẩu KHÔNG rỗng (!empty) -> Người dùng muốn đổi pass -> Mã hóa pass mới.
                // - Nếu ô mật khẩu RỖNG -> Người dùng không đổi pass -> Để chuỗi rỗng '' (Model sẽ tự hiểu và giữ pass cũ).
                'password'  => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : ''
            ];

            // Gọi Model để thực hiện cập nhật (Update).
            if ($this->userModel->updateUser($data)) {
                header('location: ' . URLROOT . '/staff');
            }
        }
    }

    // --- HÀM XÓA NHÂN VIÊN ---
    // Nhận $id từ URL.
    public function delete($id) {
        // 1. BẢO MẬT: Không cho phép tự xóa chính mình.
        // $_SESSION['user_id']: ID của người đang đăng nhập.
        if ($id == $_SESSION['user_id']) {
            // Thay vì echo script, ta lưu thông báo lỗi vào Session để hiển thị ở trang sau.
            $_SESSION['msg_type'] = 'error'; // Loại thông báo (màu đỏ).
            $_SESSION['msg_text'] = 'Không thể xóa tài khoản đang đăng nhập hiện tại!';
            
            // Chuyển hướng quay lại trang danh sách ngay lập tức.
            header('location: ' . URLROOT . '/staff');
            exit; // Dừng chương trình.
        }

        // 2. THỰC HIỆN XÓA (Xóa mềm - Soft Delete)
        // Gọi hàm deleteUser() trong Model để đánh dấu tài khoản này là "Đã xóa".
        if ($this->userModel->deleteUser($id)) {
            // Lưu thông báo thành công vào Session.
            $_SESSION['msg_type'] = 'success'; // Loại thông báo (màu xanh).
            $_SESSION['msg_text'] = 'Đã chuyển nhân viên vào thùng rác.';
            
            header('location: ' . URLROOT . '/staff');
        }
    }

    // --- HÀM KHÔI PHỤC NHÂN VIÊN ---
    // Dùng để cứu tài khoản từ "thùng rác" trở lại hoạt động bình thường.
    public function restore($id) {
        // Gọi hàm restoreUser() trong Model để bỏ đánh dấu xóa.
        if ($this->userModel->restoreUser($id)) {
            // Nếu thành công -> Quay lại trang danh sách.
            // (Bạn có thể thêm Session msg ở đây nếu muốn thông báo).
            header('location: ' . URLROOT . '/staff');
        } else {
            // Nếu thất bại -> Báo lỗi.
            die('Lỗi hệ thống khi khôi phục nhân viên.');
        }
    }
}