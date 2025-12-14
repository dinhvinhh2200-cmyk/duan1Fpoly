<?php
//Vai trò: Quản lý việc hiển thị Thực đơn (Menu) của quán.

//Nhiệm vụ chính:

//Kiểm tra quyền truy cập: Đảm bảo người dùng đã đăng nhập.

//Lấy dữ liệu: Gọi Model để lấy danh sách Danh mục và Sản phẩm.

//Hiển thị: Gửi dữ liệu sang View để vẽ giao diện Menu.
// Định nghĩa class MenuController.
// 'class': Tạo một lớp đối tượng.
// 'extends Controller': Kế thừa từ class cha Controller để sử dụng các tính năng chung (model, view...).
class MenuController extends Controller {
    
    // Khai báo các thuộc tính (biến) để chứa Model.
    // 'private': Biến này chỉ được dùng trong nội bộ class này.
    private $categoryModel; // Model xử lý danh mục.
    private $productModel;  // Model xử lý sản phẩm.

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Chạy tự động ngay khi Controller được gọi.
    public function __construct() {
        // 1. KIỂM TRA ĐĂNG NHẬP (Bảo mật cơ bản)
        // isset($_SESSION['user_id']): Kiểm tra xem trong session có lưu ID người dùng không.
        // Dấu '!': Phủ định (Nếu KHÔNG có session).
        if (!isset($_SESSION['user_id'])) {
            // Nếu chưa đăng nhập -> Chuyển hướng về trang Login.
            header('location: ' . URLROOT . '/auth/login');
            // exit: Dừng chương trình ngay lập tức để không chạy các lệnh bên dưới.
            exit;
        }

        // 2. LOAD MODEL: Nạp các file Model cần thiết.
        // Hàm $this->model() được kế thừa từ Controller cha.
        $this->categoryModel = $this->model('CategoryModel');
        $this->productModel = $this->model('ProductModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Khi truy cập /menu, hàm này sẽ chạy.
    // Nhiệm vụ: Lấy dữ liệu và hiển thị trang Menu.
    public function index() {
        // Gọi hàm getCategories() từ CategoryModel để lấy danh sách danh mục.
        // Kết quả trả về là một mảng các đối tượng danh mục.
        $categories = $this->categoryModel->getCategories();
        
        // Gọi hàm getProducts() từ ProductModel để lấy danh sách sản phẩm.
        $products = $this->productModel->getProducts();

        // Đóng gói dữ liệu vào một mảng liên hợp $data.
        // Mảng này sẽ được gửi sang View.
        $data = [
            'categories' => $categories, // Danh sách danh mục
            'products' => $products      // Danh sách sản phẩm
        ];

        // Gọi hàm view() để hiển thị giao diện.
        // - Tham số 1: Đường dẫn tới file view ('menu/index').
        // - Tham số 2: Mảng dữ liệu $data.
        $this->view('menu/index', $data);
    }
}