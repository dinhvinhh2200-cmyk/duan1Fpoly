<?php
//Vai trò: Controller này xử lý giao diện và logic cho trang chấm công (quét mã QR hoặc nhập tên).

//Nhiệm vụ chính:

///Hiển thị form nhập tên/quét QR.

//Nhận dữ liệu từ form gửi lên (Tên nhân viên, Hành động Vào/Ra).

//Kiểm tra tên có hợp lệ không (dựa vào QrStaffModel).

//Ghi nhận giờ vào/ra (dựa vào AttendanceModel).

//Thông báo kết quả cho nhân viên (Thành công/Thất bại).
// Định nghĩa class Attendance.
// 'class': Từ khóa để tạo một lớp đối tượng (khuôn mẫu).
// 'extends Controller': Kế thừa từ class cha 'Controller'.
// Nghĩa là class Attendance này sẽ thừa hưởng toàn bộ công cụ (hàm model, view...) của Controller cha.
class Attendance extends Controller {
    
    // Khai báo thuộc tính (biến) để chứa các Model sẽ dùng.
    // 'private': Phạm vi truy cập riêng tư. Chỉ có thể dùng biến này bên trong class Attendance này thôi.
    private $attendanceModel; // Biến chứa Model xử lý chấm công.
    private $qrStaffModel;    // [MỚI] Biến chứa Model kiểm tra danh sách nhân viên hợp lệ.

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm này có tên đặc biệt là __construct.
    // Nó sẽ TỰ ĐỘNG CHẠY ngay khi Controller này được gọi.
    public function __construct() {
        // $this: Đại diện cho đối tượng hiện tại (class Attendance này).
        // -> : Toán tử mũi tên, dùng để truy cập vào hàm hoặc biến bên trong đối tượng.
        
        // Gọi hàm model() (được thừa kế từ Controller cha) để nạp file Model.
        // Sau đó gán nó vào biến $this->attendanceModel để dùng sau này.
        $this->attendanceModel = $this->model('AttendanceModel');
        
        // Tương tự, nạp Model QrStaffModel và gán vào biến.
        $this->qrStaffModel = $this->model('QrStaffModel'); 
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Khi người dùng truy cập vào trang /Attendance, hàm này sẽ chạy.
    public function index() {
        // Khai báo mảng (Array) chứa dữ liệu để gửi sang giao diện (View).
        // Mảng này có 2 phần tử: 'message' (lời nhắn) và 'message_type' (loại thông báo: xanh/đỏ).
        // Ban đầu để trống vì chưa có hành động gì.
        $data = [
            'message' => '',
            'message_type' => ''
        ];

        // Kiểm tra xem người dùng có đang gửi dữ liệu (bấm nút) không?
        // $_SERVER['REQUEST_METHOD']: Biến hệ thống chứa phương thức gửi dữ liệu (GET hoặc POST).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy dữ liệu từ form gửi lên thông qua biến siêu toàn cục $_POST.
            // trim(): Hàm cắt bỏ khoảng trắng thừa ở đầu và cuối chuỗi (ví dụ: "  Vinh  " -> "Vinh").
            $name = trim($_POST['staff_name']); 
            
            // Lấy hành động (vào ca hay ra ca) từ input ẩn trong form.
            $action = $_POST['action']; 

            // --- BẮT ĐẦU KIỂM TRA DỮ LIỆU (VALIDATION) ---

            // 1. Kiểm tra rỗng: Nếu biến $name không có gì.
            if (empty($name)) {
                $data['message'] = "Vui lòng nhập tên của bạn!";
                $data['message_type'] = "error"; // Loại lỗi (để hiện màu đỏ).
            } 
            
            // 2. [MỚI] Kiểm tra tên có trong danh sách cho phép không.
            // Gọi hàm checkNameExists() từ Model QrStaffModel.
            // Dấu '!' nghĩa là PHỦ ĐỊNH (Nếu KHÔNG tồn tại tên này).
            elseif (!$this->qrStaffModel->checkNameExists($name)) {
                $data['message'] = "❌ LỖI: Tên <b>'$name'</b> không có trong danh sách nhân viên!<br>Vui lòng liên hệ Admin hoặc kiểm tra lại chính tả.";
                $data['message_type'] = "error";
            } 
            
            // 3. Nếu tên hợp lệ (vượt qua 2 bài kiểm tra trên) -> Xử lý chấm công.
            else {
                // Nếu hành động là 'checkin' (Vào ca).
                if ($action == 'checkin') {
                    // Gọi hàm checkIn() bên Model để lưu vào Database.
                    if ($this->attendanceModel->checkIn($name)) {
                        // date('H:i'): Lấy giờ hiện tại (Giờ:Phút).
                        $data['message'] = "✅ Xin chào <b>$name</b>! <br>Đã ghi nhận vào ca lúc " . date('H:i');
                        $data['message_type'] = "success"; // Loại thành công (để hiện màu xanh).
                    }
                } 
                // Ngược lại, nếu hành động là 'checkout' (Kết ca).
                elseif ($action == 'checkout') {
                    // Gọi hàm checkOut() bên Model.
                    // [ĐÃ SỬA] Hàm này giờ trả về true (nếu OK) hoặc false (nếu chưa check-in).
                    if ($this->attendanceModel->checkOut($name)) {
                        $data['message'] = "👋 Tạm biệt <b>$name</b>! <br>Đã ghi nhận ra ca lúc " . date('H:i');
                        $data['message_type'] = "success";
                    } else {
                        // [MỚI] Nếu thất bại (do chưa check-in) -> Báo lỗi.
                        $data['message'] = "❌ Lỗi: Bạn chưa Check-in hôm nay nên không thể Check-out!";
                        $data['message_type'] = "error"; 
                    }
                }
            }
        }

        // Gọi hàm view() (từ Controller cha) để hiển thị file giao diện HTML.
        // Tham số 1: Đường dẫn file view ('attendance/index.php').
        // Tham số 2: Biến $data chứa thông báo để hiển thị lên màn hình.
        $this->view('attendance/index', $data);
    }
}