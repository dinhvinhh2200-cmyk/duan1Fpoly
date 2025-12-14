<?php
/*
 * SHIFT CONTROLLER
 * -------------------------------------------------------------------------
 * VAI TRÒ VÀ NHIỆM VỤ CHÍNH:
 * 1. Quản lý Ca làm việc (Work Shift): Bao gồm Mở ca (Open) và Chốt ca (Close).
 * 2. Bảo mật: Yêu cầu nhân viên phải đăng nhập trước khi thao tác.
 * 3. Báo cáo: Tính toán và hiển thị doanh thu trong ca, so sánh tiền thực tế trong két.
 * 4. Lịch sử: Xem lại các ca làm việc trước đó.
 * -------------------------------------------------------------------------
 */

// Định nghĩa class Shift.
// 'class': Tạo một khuôn mẫu đối tượng.
// 'extends Controller': Kế thừa từ class cha 'Controller'.
// Điều này giúp Shift có thể sử dụng các "siêu năng lực" của cha như: nạp Model ($this->model), gọi View ($this->view).
class Shift extends Controller {
    
    // Khai báo thuộc tính (biến) $cashModel.
    // 'private': Biến này là "tài sản riêng", chỉ được dùng bên trong class này thôi.
    // Nó sẽ chứa đối tượng Model để giao tiếp với Database (xử lý tiền nong).
    private $cashModel;
    private $orderModel; // [MỚI] Biến chứa Model đơn hàng để xem chi tiết

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm __construct() sẽ TỰ ĐỘNG CHẠY ngay khi class Shift được gọi.
    public function __construct() {
        
        // 1. KIỂM TRA ĐĂNG NHẬP (Bảo mật)
        // isset(): Kiểm tra xem biến user_id có tồn tại trong Session không.
        // Dấu '!': Phủ định (Nếu KHÔNG tồn tại -> Chưa đăng nhập).
        if (!isset($_SESSION['user_id'])) { 
            // redirect(): Hàm chuyển hướng người dùng sang trang khác (được định nghĩa trong helpers/url_helper.php).
            // Chuyển về trang đăng nhập nếu chưa có quyền.
            redirect('auth/login'); 
        }
        
        // 2. NẠP MODEL
        // Gọi hàm model() từ class cha để nạp file 'app/models/CashModel.php'.
        // Sau đó gán nó vào biến $this->cashModel để dùng cho các hàm bên dưới.
        $this->cashModel = $this->model('CashModel');
        // [MỚI] Nạp thêm OrderModel
        $this->orderModel = $this->model('OrderModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) - TRANG BÁO CÁO CA HIỆN TẠI ---
    // Hàm này chạy khi truy cập đường dẫn: /shift
    public function index() {
        // 1. Lấy thông tin ca đang mở
        // Gọi hàm getCurrentSession() trong Model để lấy ca chưa chốt.
        $session = $this->cashModel->getCurrentSession();
        
        // Nếu không tìm thấy ca nào đang mở (nghĩa là chưa mở ca).
        if (!$session) { 
            // Chuyển hướng ngay sang trang Mở ca.
            redirect('shift/open'); 
        }

        // 2. Tính toán doanh thu chi tiết
        // Gọi hàm getCurrentSessionSalesBreakdown() để lấy tổng doanh thu, tiền mặt, chuyển khoản.
        // Truyền vào thời gian bắt đầu ca ($session->start_time).
        $salesData = $this->cashModel->getCurrentSessionSalesBreakdown($session->start_time);

        // 3. Tính "Tiền lý thuyết trong két" (Expected Cash)
        // Công thức: Tiền đầu ca (Opening Cash) + Doanh thu TIỀN MẶT (Total Cash).
        // (Không cộng doanh thu chuyển khoản vì tiền đó nằm trong ngân hàng, không nằm trong két).
        $expectedCash = $session->opening_cash + $salesData->total_cash;

        // 4. Lấy danh sách món đã bán trong ca này
        // Gọi hàm getItemsInSession() để xem chi tiết bán được bao nhiêu ly cà phê, trà sữa...
        // Tham số: Từ lúc mở ca đến hiện tại (date('Y-m-d H:i:s')).
        $currentItems = $this->cashModel->getItemsInSession($session->start_time, date('Y-m-d H:i:s'));
        
        // [MỚI] 5. Lấy danh sách hóa đơn CỦA NHÂN VIÊN NÀY trong ca hiện tại
        // Để họ tự đối chiếu xem mình đã thu tiền đơn nào
        $myOrders = $this->cashModel->getStaffOrdersInSession($_SESSION['user_id'], $session->start_time, date('Y-m-d H:i:s'));

        // 6. Lấy lịch sử các ca đã chốt trước đó (để hiển thị bảng bên dưới).
        $history = $this->cashModel->getClosedSessions();

        // 7. Đóng gói dữ liệu để gửi sang View
        $data = [
            'session'       => $session,      // Thông tin ca hiện tại
            'sales_data'    => $salesData,    // Chi tiết doanh thu (Tiền mặt/CK)
            'expected'      => $expectedCash, // Tiền lý thuyết phải có trong két
            'history'       => $history,      // Lịch sử các ca cũ
            'current_items' => $currentItems, // Chi tiết món bán được
            'my_orders'     => $myOrders      // [MỚI] Danh sách đơn của nhân viên
        ];

        // 8. Hiển thị giao diện
        // Gọi file 'app/views/shift/report.php' và truyền dữ liệu $data vào.
        $this->view('shift/report', $data);
    }

    // --- HÀM MỞ CA (OPEN) ---
    // Hàm này chạy khi truy cập: /shift/open
    public function open() {
        // 1. Kiểm tra logic: Đã có ca đang mở chưa?
        // Nếu đã có ca đang chạy -> Không cần mở nữa -> Đuổi về trang bán hàng (POS).
        if ($this->cashModel->getCurrentSession()) {
            redirect('pos');
        }

        // 2. Xử lý khi người dùng bấm nút "Xác nhận mở ca" (POST request)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Lấy số tiền đầu ca từ form nhập liệu.
            $money = $_POST['opening_cash'];
            
            // Gọi hàm startSession() trong Model để tạo dòng mới trong Database.
            // Truyền vào: ID người mở ca (lấy từ Session) và Số tiền đầu ca.
            if ($this->cashModel->startSession($_SESSION['user_id'], $money)) {
                // Nếu thành công -> Chuyển sang màn hình POS để bắt đầu bán hàng.
                redirect('pos');
            }
        }

        // 3. Hiển thị giao diện nhập tiền đầu ca (Nếu chưa bấm nút).
        $this->view('shift/open');
    }

    // --- HÀM CHỐT CA (CLOSE) ---
    // Hàm này chạy khi người dùng bấm nút "Chốt ca & Đăng xuất" (POST request từ trang Report).
    public function close() {
        // Kiểm tra phương thức gửi phải là POST.
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy dữ liệu từ form chốt ca:
            $sessionId  = $_POST['session_id'];  // ID của ca cần chốt
            $actualCash = $_POST['actual_cash']; // Số tiền thực tế đếm được trong két
            $note       = $_POST['note'];        // Ghi chú (nếu thừa/thiếu tiền)
            
            // Lấy lại thông tin ca hiện tại để tính toán lần cuối.
            $session = $this->cashModel->getCurrentSession();

            // Tính toán doanh thu hiện tại (để lưu vào lịch sử làm bằng chứng).
            $salesData = $this->cashModel->getCurrentSessionSalesBreakdown($session->start_time);
            
            // Lấy tổng doanh thu (Bao gồm cả Tiền mặt + Chuyển khoản) để lưu vào cột total_sales.
            $totalSales = $salesData->total_all; 

            // Gọi hàm closeSession() trong Model để cập nhật giờ kết thúc (end_time) và các số liệu.
            if ($this->cashModel->closeSession($sessionId, $_SESSION['user_id'], $totalSales, $actualCash, $note)) {
                // Chốt xong thành công -> Tự động đăng xuất tài khoản.
                redirect('auth/logout');
            }
        }
    }

    // --- API AJAX: LẤY CHI TIẾT MÓN TRONG CA CŨ ---
    // Hàm này được gọi ngầm bởi Javascript khi bấm nút "Mắt" (Xem chi tiết) ở bảng lịch sử.
    // Nó trả về dữ liệu JSON, không trả về giao diện HTML.
    public function get_session_details() {
        // Chỉ xử lý nếu là POST request.
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Lấy tham số thời gian bắt đầu và kết thúc của ca đó.
            $start = $_POST['start'];
            $end   = $_POST['end'];
            
            // Gọi Model để lấy danh sách món bán được trong khoảng thời gian đó.
            $items = $this->cashModel->getItemsInSession($start, $end);
            
            // Đặt header báo cho trình duyệt biết đây là dữ liệu JSON.
            header('Content-Type: application/json');
            
            // Trả về kết quả JSON.
            // json_encode(): Chuyển mảng PHP thành chuỗi JSON.
            echo json_encode(['status' => 'success', 'items' => $items]);
            
            // Dừng chương trình ngay lập tức để đảm bảo JSON sạch sẽ.
            exit;
        }
    }

    // [MỚI] API AJAX: LẤY CHI TIẾT ĐƠN HÀNG (Dành cho Staff xem lại)
    // Hàm này tương tự bên Dashboard nhưng cho phép Staff truy cập trong màn hình Shift
    public function get_order_detail($id) {
        // Gọi Model lấy thông tin chi tiết đơn hàng
        $data = $this->orderModel->getOrderDetail($id);
        
        // Trả về JSON
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}