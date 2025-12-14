<?php
/*
 * TABLE CONTROLLER
 * -------------------------------------------------------------------------
 * 1. Quản lý sơ đồ bàn: Thêm tự động, Xóa, Khôi phục.
 * 2. Bảo mật: Chỉ Admin.
 * -------------------------------------------------------------------------
 */
class Table extends Controller {
    
    private $tableModel;

    public function __construct() {
        $this->restrictToAdmin();
        $this->tableModel = $this->model('TableModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    public function index() {
        $tables = $this->tableModel->getTables();
        $this->view('admin/tables/index', ['tables' => $tables]);
    }

    // --- HÀM THÊM BÀN MỚI (TỰ ĐỘNG) ---
    public function add() {
        // Chỉ xử lý khi bấm nút (POST)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Gọi Model để tự động tạo bàn theo quy tắc "Bàn Số + ID"
            if ($this->tableModel->addAutoTable()) {
                $_SESSION['msg_type'] = 'success';
                $_SESSION['msg_text'] = 'Thêm bàn mới thành công!';
            } else {
                $_SESSION['msg_type'] = 'error';
                $_SESSION['msg_text'] = 'Lỗi hệ thống khi thêm bàn.';
            }
            
            // Quay lại trang danh sách
            redirect('table');
        }
    }

    // --- HÀM XÓA BÀN ---
    public function delete($id) {
        // Kiểm tra trạng thái bàn trước
        $table = $this->tableModel->getTableById($id);

        if ($table && $table->status == 'occupied') {
            $_SESSION['msg_type'] = 'error';
            $_SESSION['msg_text'] = '❌ Không thể xóa! Bàn này đang có khách ngồi!';
            redirect('table');
            return;
        }

        if ($this->tableModel->deleteTable($id)) {
            $_SESSION['msg_type'] = 'success';
            $_SESSION['msg_text'] = 'Đã xóa bàn thành công!';
        } else {
            $_SESSION['msg_type'] = 'error';
            $_SESSION['msg_text'] = 'Lỗi hệ thống khi xóa bàn.';
        }
        
        redirect('table');
    }

    // --- HÀM KHÔI PHỤC BÀN ---
    public function restore($id) {
        if ($this->tableModel->restoreTable($id)) {
            $_SESSION['msg_type'] = 'success';
            $_SESSION['msg_text'] = 'Đã khôi phục bàn thành công!';
        } else {
            $_SESSION['msg_type'] = 'error';
            $_SESSION['msg_text'] = 'Lỗi hệ thống khi khôi phục bàn.';
        }
        redirect('table');
    }
}