<?php
//Vai trò: Controller này đóng vai trò "Nhạc trưởng" cho phần quản lý Danh mục sản phẩm (Category).

//Nhiệm vụ chính:

//Hiển thị: Lấy danh sách danh mục và gửi sang giao diện (View).

//Xử lý Form: Nhận dữ liệu từ form Thêm mới hoặc Cập nhật.

//Validation: Kiểm tra tính hợp lệ (rỗng, trùng tên) trước khi lưu.
// Định nghĩa class Category.
// 'class': Tạo một đối tượng (Object).
// 'extends Controller': Kế thừa từ class cha Controller (trong core/Controller.php).
// Việc kế thừa giúp class này có thể dùng các công cụ có sẵn như: $this->model(), $this->view(), $this->restrictToAdmin().
class Category extends Controller {
    
    // Khai báo thuộc tính (biến) $categoryModel.
    // 'private': Biến này là tài sản riêng, chỉ dùng được bên trong class Category này.
    // Nó dùng để chứa đối tượng CategoryModel (để thao tác với Database).
    private $categoryModel;

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Hàm này tự động chạy ngay khi Controller được gọi.
    public function __construct() {
        // Gọi hàm restrictToAdmin() từ class cha Controller.
        // Hàm này kiểm tra quyền hạn. Nếu không phải Admin -> Chặn lại và đá về trang Login.
        // Đây là lớp bảo mật đầu tiên.
        $this->restrictToAdmin(); 
        
        // Gọi hàm model() từ class cha để nạp file 'app/models/CategoryModel.php'.
        // Gán kết quả vào biến $this->categoryModel để dùng xuyên suốt class này.
        $this->categoryModel = $this->model('CategoryModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Khi truy cập /category, hàm này sẽ chạy.
    // Nhiệm vụ: Lấy danh sách danh mục và hiển thị ra màn hình.
    public function index() {
        // Gọi hàm getAllCategoriesIncludingDeleted() từ Model.
        // Hàm này lấy tất cả danh mục (bao gồm cả những cái đã xóa mềm) từ Database.
        $categories = $this->categoryModel->getAllCategoriesIncludingDeleted();
        
        // Gọi hàm view() để hiển thị giao diện.
        // - 'admin/categories/index': Đường dẫn tới file view.
        // - ['categories' => $categories]: Mảng dữ liệu gửi sang view.
        $this->view('admin/categories/index', ['categories' => $categories]);
    }

    // --- HÀM THÊM DANH MỤC MỚI ---
    // Khi submit form thêm mới, hàm này sẽ chạy.
    public function add() {
        // Kiểm tra xem người dùng có gửi dữ liệu bằng phương thức POST không.
        // $_SERVER['REQUEST_METHOD']: Biến hệ thống chứa kiểu request (GET/POST).
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy tên danh mục từ form ($_POST['category_name']).
            // trim(): Cắt bỏ khoảng trắng thừa ở đầu và cuối chuỗi (ví dụ " Cafe " -> "Cafe").
            $name = trim($_POST['category_name']);
            
            // Kiểm tra: Nếu tên không rỗng.
            if (!empty($name)) {
                
                // --- 1. KIỂM TRA TRÙNG TÊN (Validation) ---
                // Gọi hàm checkNameExists() từ Model để xem tên này đã có chưa.
                if ($this->categoryModel->checkNameExists($name)) {
                    // Nếu trùng tên -> Lưu thông báo lỗi vào Session.
                    // $_SESSION: Biến toàn cục lưu trữ dữ liệu phiên làm việc.
                    $_SESSION['error_category'] = "Lỗi: Tên danh mục '$name' đã tồn tại!";
                } else {
                    // --- 2. THÊM MỚI ---
                    // Nếu không trùng -> Gọi hàm addCategory() từ Model để lưu vào Database.
                    $this->categoryModel->addCategory($name);
                    
                    // Xóa thông báo lỗi cũ (nếu có) để giao diện sạch sẽ.
                    unset($_SESSION['error_category']);
                }
            }
            
            // --- 3. ĐIỀU HƯỚNG ---
            // Sau khi xử lý xong, chuyển hướng người dùng quay lại trang danh sách danh mục.
            // Hàm redirect() được định nghĩa trong helpers/url_helper.php.
            redirect('category');
        }
    }

    // --- HÀM CẬP NHẬT DANH MỤC ---
    // Nhận tham số $id từ URL (ví dụ: /category/edit/5 -> $id = 5).
    public function edit($id) {
        // Kiểm tra xem có phải là request POST không.
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Lấy tên mới từ form.
            $name = trim($_POST['category_name']);
            
            // --- 1. KIỂM TRA RỖNG ---
            // Nếu người dùng xóa hết tên rồi bấm lưu -> Báo lỗi.
            if (empty($name)) {
                $_SESSION['error_category'] = "Vui lòng nhập tên danh mục!";
                redirect('category'); // Quay lại trang danh sách.
            }

            // --- 2. KIỂM TRA TRÙNG TÊN ---
            // Gọi hàm checkNameExists() với tham số thứ 2 là $id.
            // Ý nghĩa: Kiểm tra xem tên này có bị trùng với danh mục KHÁC không (trừ chính nó ra).
            if ($this->categoryModel->checkNameExists($name, $id)) {
                $_SESSION['error_category'] = "Tên danh mục '$name' đã tồn tại! Vui lòng chọn tên khác.";
                redirect('category');
            }
            
            // --- 3. CẬP NHẬT ---
            // Nếu hợp lệ -> Gọi hàm updateCategory() từ Model.
            if ($this->categoryModel->updateCategory($id, $name)) {
                // Nếu cập nhật thành công -> Xóa lỗi cũ.
                unset($_SESSION['error_category']);
                redirect('category');
            } else {
                // Nếu lỗi hệ thống (Database chết chẳng hạn) -> Dừng chương trình và báo lỗi.
                die('Lỗi hệ thống khi cập nhật danh mục');
            }
        }
    }

    // --- HÀM XÓA DANH MỤC ---
    // Nhận $id từ URL.
    public function delete($id) {
        // Gọi hàm deleteCategory() từ Model.
        // Lưu ý: Đây là Xóa mềm (Soft Delete), chỉ ẩn đi chứ không mất hẳn.
        $this->categoryModel->deleteCategory($id);
        
        // Quay lại trang danh sách.
        redirect('category');
    }

    // --- HÀM KHÔI PHỤC DANH MỤC ---
    // Nhận $id từ URL.
    public function restore($id) {
        // Gọi hàm restoreCategory() từ Model để hồi sinh danh mục đã xóa.
        if ($this->categoryModel->restoreCategory($id)) {
            // Nếu thành công -> Quay lại trang danh sách.
            redirect('category');
        } else {
            // Nếu thất bại -> Báo lỗi.
            die('Lỗi hệ thống khi khôi phục danh mục');
        }
    }
}