<?php
/*
 * QR STAFF CONTROLLER
 * -------------------------------------------------------------------------
 * VAI TRÒ VÀ NHIỆM VỤ CHÍNH:
 * 1. Quản lý danh sách "Whitelist": Đây là danh sách các nhân viên ĐƯỢC PHÉP chấm công.
 * 2. Bảo mật: Chỉ Admin mới có quyền truy cập vào trang quản lý này.
 * 3. Hiển thị: Lấy danh sách nhân viên từ Database và gửi sang giao diện để hiển thị.
 * 4. Thêm mới: Nhận tên nhân viên từ form, kiểm tra xem có bị trùng tên không, rồi lưu vào Database.
 * 5. Xóa: Xóa nhân viên khỏi danh sách cho phép chấm công.
 * -------------------------------------------------------------------------
 */

// Định nghĩa class QrStaff.
// 'class': Từ khóa để tạo một khuôn mẫu đối tượng.
// 'extends Controller': Kế thừa từ class cha 'Controller'.
// Điều này giúp QrStaff có thể sử dụng các "siêu năng lực" của cha như: nạp Model, gọi View, kiểm tra Admin.
class QrStaff extends Controller {
    
    // Khai báo thuộc tính (biến) $qrModel.
    // 'private': Biến này là "tài sản riêng", chỉ được dùng bên trong class này thôi.
    // Nó sẽ chứa đối tượng Model để giao tiếp với Database.
    private $qrModel;

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm __construct() sẽ TỰ ĐỘNG CHẠY ngay khi class QrStaff được gọi.
    public function __construct() {
        // Gọi hàm restrictToAdmin() từ class cha Controller.
        // Hàm này kiểm tra: Nếu người dùng không phải Admin -> Đuổi về trang Login ngay lập tức.
        $this->restrictToAdmin(); 

        // Gọi hàm model() từ class cha để nạp file 'app/models/QrStaffModel.php'.
        // Sau đó gán nó vào biến $this->qrModel để dùng cho các hàm bên dưới.
        $this->qrModel = $this->model('QrStaffModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Hàm này chạy khi truy cập đường dẫn: /QrStaff
    // Nhiệm vụ: Lấy danh sách và hiển thị bảng.
    public function index() {
        // 1. Gọi Model để lấy dữ liệu
        // $this->qrModel->getAll(): Chạy câu lệnh SQL lấy tất cả nhân viên trong bảng 'qr_allowed_staff'.
        $list = $this->qrModel->getAll();
        
        // 2. Gửi dữ liệu sang View
        // Gọi hàm view() để hiển thị file giao diện 'admin/qr_staff/index.php'.
        // ['staff_list' => $list]: Đóng gói danh sách vừa lấy được vào một cái hộp tên là 'staff_list' để gửi đi.
        $this->view('admin/qr_staff/index', ['staff_list' => $list]);
    }

    // --- HÀM THÊM NHÂN VIÊN MỚI ---
    // Hàm này chạy khi người dùng bấm nút "Lưu lại" trên form thêm mới.
    public function add() {
        // Kiểm tra xem người dùng có gửi dữ liệu bằng phương thức POST không.
        // $_SERVER['REQUEST_METHOD']: Biến hệ thống chứa kiểu gửi (GET hoặc POST).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy tên nhân viên từ ô nhập liệu có name="full_name".
            // trim(): Cắt bỏ khoảng trắng thừa ở đầu và cuối (ví dụ: "  Vinh  " -> "Vinh").
            $name = trim($_POST['full_name']);
            
            // --- BẮT ĐẦU KIỂM TRA (VALIDATION) ---

            // 1. Kiểm tra rỗng: Nếu tên không có gì.
            if (empty($name)) {
                // (Chỗ này bạn để trống, có thể thêm thông báo lỗi nếu muốn, nhưng form HTML thường đã có 'required').
            } 
            
            // 2. Kiểm tra trùng tên: Gọi Model để xem tên này đã có trong Database chưa.
            // $this->qrModel->isDuplicate($name): Trả về true nếu trùng, false nếu không.
            elseif ($this->qrModel->isDuplicate($name)) {
                // Nếu trùng tên -> Bắn ra một đoạn Javascript để hiện thông báo (Alert).
                // window.location.href: Sau khi bấm OK ở thông báo, chuyển trang quay lại danh sách.
                echo "<script>
                        alert('Tên này đã tồn tại trong danh sách!'); 
                        window.location.href='" . URLROOT . "/QrStaff';
                      </script>";
                return; // Dừng hàm ngay tại đây, không chạy code phía dưới nữa.
            } 
            
            // 3. Nếu mọi thứ ổn (Không rỗng, Không trùng) -> Thêm vào Database.
            else {
                // Gọi hàm add() của Model để chạy lệnh INSERT SQL.
                $this->qrModel->add($name);
            }
            
            // Sau khi xử lý xong (dù thêm được hay không), chuyển hướng (F5) lại trang danh sách.
            // Để tránh việc người dùng nhấn F5 lại bị gửi form lần nữa.
            redirect('QrStaff');
        }
    }

    // --- HÀM XÓA NHÂN VIÊN ---
    // Nhận tham số $id từ đường dẫn URL (ví dụ: /QrStaff/delete/5 -> $id = 5).
    public function delete($id) {
        // Gọi hàm delete($id) trong Model để chạy lệnh DELETE SQL xóa dòng có ID này.
        $this->qrModel->delete($id);
        
        // Sau khi xóa xong, chuyển hướng quay lại trang danh sách ban đầu.
        redirect('QrStaff');
    }
}