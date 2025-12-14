<?php
/*
 * DISCOUNT CONTROLLER
 * Vai trò: Quản lý toàn bộ chức năng liên quan đến Mã giảm giá (Discount Codes).
 * Nhiệm vụ chính:
 * - Hiển thị: Danh sách các mã giảm giá (bao gồm cả mã đã xóa mềm).
 * - Thêm mới: Tạo mã giảm giá với các điều kiện (giá trị giảm, đơn tối thiểu, [MỚI] giảm tối đa, [MỚI] thời hạn).
 * - Cập nhật: Sửa thông tin mã giảm giá hiện có.
 * - Xóa & Khôi phục: Xóa mềm mã (đưa vào thùng rác) và khôi phục lại khi cần.
 * - [ĐÃ SỬA] Bỏ chức năng Bật/Tắt trạng thái.
 */
class DiscountController extends Controller {
    
    // Khai báo thuộc tính (biến) $discountModel.
    // 'private': Biến này chỉ được dùng trong nội bộ class này.
    // Nó sẽ chứa đối tượng DiscountModel để thao tác với Database.
    private $discountModel;

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Chạy tự động khi Controller được gọi.
    public function __construct() {
        // 1. BẢO MẬT: Gọi hàm kiểm tra quyền từ class cha.
        // Chỉ cho phép Admin truy cập. Nếu không phải -> Chuyển hướng.
        $this->restrictToAdmin();
        
        // 2. LOAD MODEL: Nạp file 'app/models/DiscountModel.php'.
        // Gán vào biến $this->discountModel để dùng cho các hàm bên dưới.
        $this->discountModel = $this->model('DiscountModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Hiển thị danh sách mã giảm giá.
    public function index() {
        // Gọi hàm getAllDiscountsIncludingDeleted() từ Model.
        // Hàm này lấy tất cả mã, bao gồm cả những mã đã bị xóa mềm (is_deleted = 1).
        $discounts = $this->discountModel->getAllDiscountsIncludingDeleted();
        
        // Đóng gói dữ liệu vào mảng $data.
        $data = ['discounts' => $discounts];
        
        // Gọi View hiển thị giao diện.
        $this->view('admin/discounts/index', $data);
    }

    // --- HÀM THÊM MÃ MỚI ---
    public function add() {
        // Kiểm tra xem người dùng có submit form (bấm nút Lưu) không.
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy dữ liệu từ form:
            // strtoupper(): Chuyển mã thành chữ IN HOA (ví dụ: sale10 -> SALE10).
            // trim(): Cắt khoảng trắng thừa.
            $code = strtoupper(trim($_POST['code']));
            
            $type = $_POST['type']; // Loại giảm giá (percentage/fixed).
            
            // (float): Ép kiểu dữ liệu sang số thực (số có dấu chấm) để tính toán chính xác.
            $value = (float)$_POST['value']; 
            
            // [ĐÃ SỬA] Bỏ xử lý is_active, mặc định luôn hoạt động nếu không bị xóa

            // 1. XỬ LÝ ĐIỀU KIỆN (Đơn tối thiểu)
            $condition_type = $_POST['condition_type']; // Lấy loại điều kiện ('none' hoặc 'min').
            $min_order_value = 0; // Mặc định là 0 (không có điều kiện).

            // Nếu người dùng chọn 'Có điều kiện' (min)
            if ($condition_type == 'min') {
                // Lấy giá trị tiền tối thiểu nhập vào.
                $min_order_value = (float)$_POST['min_order_value'];
                
                // Kiểm tra hợp lệ: Phải lớn hơn 0.
                if ($min_order_value <= 0) {
                    // Lưu lỗi vào Session.
                    $_SESSION['error_discount_min'] = "Số tiền đơn hàng tối thiểu phải lớn hơn 0!";
                    // Quay lại trang danh sách.
                    header('location: ' . URLROOT . '/discount');
                    return; // Dừng hàm tại đây.
                }
            }

            // [MỚI] Xử lý Giảm tối đa (Chỉ áp dụng cho %)
            $max_discount_amount = 0;
            if ($type == 'percentage') {
                $max_discount_amount = (float)$_POST['max_discount_amount'];
                // Không bắt buộc nhập, nhưng nếu nhập thì phải > 0
                if ($_POST['max_discount_amount'] != '' && $max_discount_amount <= 0) {
                     $_SESSION['error_discount_value'] = "Số tiền giảm tối đa phải lớn hơn 0!";
                     header('location: ' . URLROOT . '/discount');
                     return;
                }
            }

            // [MỚI] Xử lý Ngày bắt đầu / Kết thúc
            // Nếu người dùng không chọn ngày thì gán là NULL
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date   = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;

            // Kiểm tra: Ngày kết thúc phải >= Ngày bắt đầu
            if ($start_date && $end_date && $end_date < $start_date) {
                $_SESSION['alert_type'] = 'error';
                $_SESSION['alert_msg'] = 'Lỗi: Ngày kết thúc không được nhỏ hơn Ngày bắt đầu!';
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // --- 2. VALIDATE DỮ LIỆU (Kiểm tra lỗi) ---
            
            // Kiểm tra Mã Code rỗng
            if (empty($code)) {
                $_SESSION['error_discount_code'] = "Vui lòng nhập Mã giảm giá!";
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // Kiểm tra trùng Mã (Gọi Model)
            if ($this->discountModel->checkCodeExists($code)) {
                $_SESSION['error_discount_code'] = "Mã '$code' đã tồn tại!";
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // Kiểm tra Giá trị giảm (Phải dương)
            if ($value <= 0) {
                $_SESSION['error_discount_value'] = "Giá trị phải lớn hơn 0!";
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // Kiểm tra Giá trị Phần trăm (Không quá 100%)
            if ($type == 'percentage' && $value > 100) {
                $_SESSION['error_discount_value'] = "Giảm giá phần trăm không được quá 100%!";
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // --- 3. THÊM VÀO DB ---
            // Nếu không có lỗi, gom dữ liệu vào mảng $data.
            // [ĐÃ SỬA] Bỏ is_active khỏi mảng dữ liệu, thêm max_discount_amount, start_date, end_date
            $data = [
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'min_order_value' => $min_order_value,
                'max_discount_amount' => $max_discount_amount,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];

            // Gọi hàm addDiscount() từ Model.
            if ($this->discountModel->addDiscount($data)) {
                // Thành công -> Quay lại trang danh sách.
                header('location: ' . URLROOT . '/discount');
            } else {
                // Thất bại -> Báo lỗi chết chương trình.
                die('Lỗi hệ thống khi thêm mã.');
            }
        }
    }

    // --- HÀM XÓA MÃ (XÓA MỀM) ---
    public function delete($id) {
        // Gọi hàm deleteDiscount() từ Model.
        // Hàm này sẽ cập nhật is_deleted = 1 chứ không xóa vĩnh viễn khỏi DB.
        if ($this->discountModel->deleteDiscount($id)) {
            // Thông báo thành công (Lưu vào Session để hiển thị alert).
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_msg'] = 'Đã xóa mã giảm giá thành công (Đã chuyển vào thùng rác).';
        } else {
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_msg'] = 'Có lỗi xảy ra, vui lòng thử lại.';
        }

        // Quay lại trang danh sách.
        header('location: ' . URLROOT . '/discount');
        exit; // Dừng code.
    }

    // [ĐÃ SỬA] Xóa hàm toggle (Bật/Tắt) vì không còn sử dụng

    // --- HÀM SỬA MÃ GIẢM GIÁ ---
    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Lấy dữ liệu từ form (tương tự hàm Add).
            $code = strtoupper(trim($_POST['code']));
            $type = $_POST['type'];
            $value = (float)$_POST['value'];
            // [ĐÃ SỬA] Bỏ xử lý is_active

            // Xử lý điều kiện đơn tối thiểu
            $condition_type = $_POST['condition_type'];
            $min_order_value = 0;
            if ($condition_type == 'min') {
                $min_order_value = (float)$_POST['min_order_value'];
                // Validate điều kiện
                if ($min_order_value <= 0) {
                    $_SESSION['error_discount_min'] = "Số tiền tối thiểu phải lớn hơn 0!";
                    header('location: ' . URLROOT . '/discount');
                    return;
                }
            }

            // [MỚI] Xử lý Giảm tối đa
            $max_discount_amount = 0;
            if ($type == 'percentage') {
                $max_discount_amount = (float)$_POST['max_discount_amount'];
            }

            // [MỚI] Xử lý Ngày
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date   = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;

            if ($start_date && $end_date && $end_date < $start_date) {
                $_SESSION['alert_type'] = 'error';
                $_SESSION['alert_msg'] = 'Lỗi: Ngày kết thúc không được nhỏ hơn Ngày bắt đầu!';
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // Validate Code
            if (empty($code)) {
                $_SESSION['error_discount_code'] = "Vui lòng nhập Mã giảm giá!";
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // Kiểm tra trùng mã (QUAN TRỌNG: Trừ chính mã đang sửa ra).
            // Gọi hàm checkCodeExists với tham số thứ 2 là $id.
            if ($this->discountModel->checkCodeExists($code, $id)) {
                $_SESSION['error_discount_code'] = "Mã '$code' đã tồn tại! Vui lòng chọn mã khác.";
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // Validate Giá trị
            if ($value <= 0) {
                $_SESSION['error_discount_value'] = "Giá trị phải lớn hơn 0!";
                header('location: ' . URLROOT . '/discount');
                return;
            }
            if ($type == 'percentage' && $value > 100) {
                $_SESSION['error_discount_value'] = "Giảm giá % không được quá 100!";
                header('location: ' . URLROOT . '/discount');
                return;
            }

            // Gom dữ liệu để cập nhật
            // [ĐÃ SỬA] Bỏ is_active khỏi mảng dữ liệu, thêm max_discount_amount, start_date, end_date
            $data = [
                'id' => $id,
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'min_order_value' => $min_order_value,
                'max_discount_amount' => $max_discount_amount,
                'start_date' => $start_date,
                'end_date' => $end_date
            ];

            // Gọi Model cập nhật
            if ($this->discountModel->updateDiscount($data)) {
                header('location: ' . URLROOT . '/discount');
            } else {
                die('Lỗi hệ thống khi cập nhật mã.');
            }
        }
    }

    // --- HÀM KHÔI PHỤC MÃ (RESTORE) ---
    public function restore($id) {
        // Gọi hàm restoreDiscount() từ Model.
        // Hàm này sẽ set is_deleted = 0 (Hồi sinh).
        if ($this->discountModel->restoreDiscount($id)) {
            header('location: ' . URLROOT . '/discount');
        }
    }
}