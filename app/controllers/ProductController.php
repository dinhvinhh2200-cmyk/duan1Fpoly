<?php
//Vai trò: Controller này quản lý toàn bộ các thao tác liên quan đến Sản phẩm (Món ăn/Đồ uống).

//Nhiệm vụ chính:

//Hiển thị: Danh sách món ăn (bao gồm cả món đã xóa mềm).

//Thêm mới: Nhận dữ liệu từ form, upload ảnh, lưu vào Database.

//Cập nhật: Sửa thông tin món, thay đổi ảnh.

//Xóa & Khôi phục: Xóa mềm (đưa vào thùng rác) và khôi phục lại.

//Validation: Kiểm tra tên, giá, trùng lặp trước khi lưu.
// Định nghĩa class ProductController.
// 'class': Tạo một lớp đối tượng.
// 'extends Controller': Kế thừa từ class cha Controller để sử dụng các tính năng chung.
class ProductController extends Controller {
    
    // Khai báo các thuộc tính (biến) để chứa Model.
    // 'private': Biến này chỉ được dùng trong nội bộ class này.
    private $productModel;  // Model xử lý sản phẩm.
    private $categoryModel; // Model xử lý danh mục (để lấy danh sách danh mục cho dropdown).

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Chạy tự động ngay khi Controller được gọi.
    public function __construct() {
        // 1. BẢO MẬT: Gọi hàm kiểm tra quyền Admin từ class cha.
        // Chỉ cho phép Admin truy cập. Nếu không phải -> Chuyển hướng.
        $this->restrictToAdmin();
        
        // 2. LOAD MODEL: Nạp các file Model cần thiết.
        // Gán vào biến để dùng cho các hàm bên dưới.
        $this->productModel = $this->model('ProductModel');
        $this->categoryModel = $this->model('CategoryModel');
    }

    // --- HÀM MẶC ĐỊNH (INDEX) ---
    // Hiển thị danh sách sản phẩm.
    public function index() {
        // Gọi hàm getAllProductsIncludingDeleted() từ ProductModel.
        // Hàm này lấy tất cả sản phẩm (bao gồm cả đã xóa mềm) để Admin quản lý.
        $products = $this->productModel->getAllProductsIncludingDeleted();
        
        // Gọi hàm getAllCategoriesIncludingDeleted() từ CategoryModel.
        // Lấy danh sách danh mục để hiển thị (ví dụ: bộ lọc hoặc form thêm mới).
        $categories = $this->categoryModel->getAllCategoriesIncludingDeleted();

        // Đóng gói dữ liệu vào mảng $data.
        $data = [
            'products' => $products,
            'categories' => $categories
        ];

        // Gọi View hiển thị giao diện.
        $this->view('admin/products/product_index', $data);
    }

    // --- HÀM KHÔI PHỤC SẢN PHẨM (RESTORE) ---
    // Nhận $id từ URL.
    public function restore($id) {
        // Gọi hàm restoreProduct() từ Model.
        // Hàm này sẽ set is_deleted = 0 (Hồi sinh) và is_available = 0 (Tắt bán để an toàn).
        if ($this->productModel->restoreProduct($id)) {
            // Thành công -> Quay lại trang danh sách.
            header('location: ' . URLROOT . '/product');
        } else {
            // Thất bại -> Báo lỗi chết chương trình.
            die('Lỗi khi khôi phục sản phẩm');
        }
    }

    // --- HÀM THÊM MÓN MỚI ---
    public function add() {
        // Kiểm tra xem người dùng có submit form không.
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // --- 1. LỌC DỮ LIỆU ĐẦU VÀO ---
            // filter_input_array: Làm sạch dữ liệu $_POST (chống mã độc cơ bản).
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            // Lấy dữ liệu từ form:
            $name = trim($_POST['product_name']); // Tên món (cắt khoảng trắng).
            $price = (float)$_POST['price'];      // Giá bán (ép kiểu số thực).
            $categoryId = trim($_POST['category_id']); // ID danh mục.
            
            // Trạng thái (1: Đang bán, 0: Ngừng bán).
            // isset(): Kiểm tra checkbox có được tích không.
            $isAvailable = 1;

            // --- 2. VALIDATE DỮ LIỆU (Kiểm tra lỗi) ---

            // A. Kiểm tra tên món rỗng
            if (empty($name)) {
                // Lưu lỗi vào Session.
                $_SESSION['error_product_name'] = "Vui lòng nhập tên món!";
                // Quay lại trang danh sách.
                header('location: ' . URLROOT . '/product');
                return; // Dừng hàm.
            }

            // Kiểm tra trùng tên (Gọi Model)
            if ($this->productModel->checkNameExists($name)) {
                $_SESSION['error_product_name'] = "Món '$name' đã tồn tại!";
                header('location: ' . URLROOT . '/product');
                return;
            }

            // B. Kiểm tra giá bán (Phải dương)
            if ($price <= 0) {
                $_SESSION['error_product_price'] = "Giá bán phải lớn hơn 0!";
                header('location: ' . URLROOT . '/product');
                return;
            }

            // --- 3. XỬ LÝ DỮ LIỆU ---
            // Gom dữ liệu vào mảng $data.
            $data = [
                'name' => $name,
                'category_id' => $categoryId,
                'price' => $price,
               
                'image' => '' // Mặc định ảnh rỗng.
            ];

            // --- 4. XỬ LÝ UPLOAD ẢNH ---
            // Kiểm tra xem có file ảnh được gửi lên không và có lỗi gì không (error === 0).
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                // Tạo tên file mới: Thêm thời gian hiện tại vào trước tên file gốc để tránh trùng.
                // time(): Lấy timestamp hiện tại.
                $imgName = time() . '_' . $_FILES['image']['name'];
                
                // Đường dẫn lưu file trên server.
                $uploadPath = '../public/uploads/' . $imgName;
                
                // Di chuyển file từ thư mục tạm (tmp_name) sang thư mục uploads.
                if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    // Nếu thành công -> Lưu tên ảnh vào mảng dữ liệu.
                    $data['image'] = $imgName;
                }
            }

            // --- 5. LƯU VÀO DB ---
            // Gọi hàm addProduct() từ Model.
            if ($this->productModel->addProduct($data)) {
                // Thành công -> Quay lại trang danh sách.
                header('location: ' . URLROOT . '/product');
            } else {
                die('Lỗi hệ thống khi thêm sản phẩm');
            }
        } else {
            // Nếu không phải POST (truy cập trực tiếp) -> Quay lại trang danh sách.
            header('location: ' . URLROOT . '/product');
        }
    }

    // --- HÀM CẬP NHẬT MÓN ---
    public function edit($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Lọc dữ liệu
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $name = trim($_POST['product_name']);
            $price = (float)$_POST['price'];

            // --- 1. VALIDATE DỮ LIỆU ---

            // Validate giá bán
            if ($price <= 0) {
                $_SESSION['error_product_price'] = "Giá bán phải lớn hơn 0!";
                header('location: ' . URLROOT . '/product');
                return;
            }

            // Validate trùng tên (QUAN TRỌNG: Trừ chính món đang sửa ra).
            // Gọi hàm checkNameExists với tham số thứ 2 là $id.
            if ($this->productModel->checkNameExists($name, $id)) {
                $_SESSION['error_product_name'] = "Tên món \"$name\" đã tồn tại! Vui lòng đặt tên khác.";
                header('location: ' . URLROOT . '/product');
                return;
            }

            // --- 2. XỬ LÝ DỮ LIỆU ---
            $data = [
                'id' => $id,
                'name' => $name,
                'category_id' => trim($_POST['category_id']),
                'price' => $price,
                
                'image' => '' // Mặc định rỗng (nếu không up ảnh mới thì Model sẽ giữ ảnh cũ).
            ];

            // Xử lý upload ảnh mới (Tương tự hàm Add)
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $imgName = time() . '_' . $_FILES['image']['name'];
                $uploadPath = '../public/uploads/' . $imgName;
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $data['image'] = $imgName;
                }
            }

            // Gọi Model cập nhật
            if ($this->productModel->updateProduct($data)) {
                header('location: ' . URLROOT . '/product');
            } else {
                die('Lỗi cập nhật sản phẩm');
            }
        } else {
            header('location: ' . URLROOT . '/product');
        }
    }

// --- HÀM XÓA MÓN (XÓA MỀM) ---
public function delete($id) {
    // Gọi hàm deleteProduct() từ Model (Cập nhật is_deleted = 1).
    // Không cần lấy thông tin sản phẩm để xóa ảnh nữa.
    if ($this->productModel->deleteProduct($id)) {
        // Thành công -> Quay lại trang danh sách.
        header('location: ' . URLROOT . '/product');
    } else {
        die('Có lỗi khi xóa sản phẩm');
    }
}
}