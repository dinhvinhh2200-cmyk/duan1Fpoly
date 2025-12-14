<?php
/*
 * POS CONTROLLER
 * Vai trò: Điều khiển trung tâm của màn hình bán hàng.
 * Nhiệm vụ: Tiếp nhận yêu cầu từ giao diện (JS), gọi Model xử lý dữ liệu và trả về kết quả JSON.
 */
class PosController extends Controller {
    
    // --- KHAI BÁO CÁC MODEL SẼ DÙNG ---
    private $tableModel;    // Quản lý Bàn
    private $categoryModel; // Quản lý Danh mục
    private $productModel;  // Quản lý Sản phẩm
    private $cashModel;     // Quản lý Ca làm việc (Tiền nong)
    private $userModel;     // Quản lý User
    private $discountModel; // Quản lý Mã giảm giá

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Chạy tự động khi Controller được gọi
    public function __construct() {
        
        // 1. Kiểm tra đăng nhập
        // Nếu không có session user_id (chưa đăng nhập)
        if (!isset($_SESSION['user_id'])) {
            header('location: ' . URLROOT . '/auth/login'); // Chuyển hướng về trang Login
            exit; // Dừng chương trình
        }

        // 2. Khởi tạo các Model (Kết nối Database)
        $this->tableModel = $this->model('TableModel');
        $this->categoryModel = $this->model('CategoryModel');
        $this->productModel = $this->model('ProductModel');
        $this->userModel = $this->model('UserModel');
        $this->cashModel = $this->model('CashModel'); 
        $this->discountModel = $this->model('DiscountModel'); 

        // 3. Kiểm tra Ca làm việc (Đối với nhân viên)
        // Nếu không phải Admin (Role ID != 1)
        if ($_SESSION['role_id'] != 1) { 
            // Kiểm tra xem có ca nào đang mở không
            $activeSession = $this->cashModel->getCurrentSession();
            
            // Nếu chưa mở ca -> Bắt buộc chuyển sang trang Mở ca
            if (!$activeSession) {
                header('location: ' . URLROOT . '/shift/open');
                exit;
            }
        }
    }

// --- HÀM INDEX: HIỂN THỊ GIAO DIỆN POS ---
    public function index() {
        // [ĐÃ SỬA] Đổi từ getTables() thành getActiveTables()
        // Để chỉ lấy danh sách bàn đang hoạt động, bỏ qua bàn đã xóa.
        $tables = $this->tableModel->getActiveTables(); 
        
        // Lấy danh sách danh mục sản phẩm
        $categories = $this->categoryModel->getCategories();
        // Lấy danh sách sản phẩm
        $products = $this->productModel->getProducts();
        // Lấy danh sách mã giảm giá đang hoạt động
        $discounts = $this->discountModel->getAvailableDiscounts();

        // Đóng gói dữ liệu để gửi sang View
        $data = [
            'tables' => $tables,
            'categories' => $categories,
            'products' => $products,
            'discounts' => $discounts
        ];

        // Gọi View hiển thị giao diện
        $this->view('pos/index', $data);
    }

    // --- API: LẤY THÔNG TIN ĐƠN HÀNG CỦA BÀN ---
    public function getTableOrder($tableId) {
        // Khởi tạo Model đơn hàng
        $orderModel = $this->model('OrderModel');
        
        // Tìm đơn hàng chưa thanh toán (pending) của bàn này
        $order = $orderModel->getUnpaidOrder($tableId); 
        
        // Nếu tìm thấy đơn hàng
        if ($order) {
            // Lấy chi tiết các món trong đơn
            $items = $orderModel->getOrderDetails($order->order_id);
            $total = $order->total_amount; // Tổng tiền tạm tính
            $discountAmount = 0; // Số tiền giảm giá mặc định là 0
            
            // Nếu đơn hàng có áp dụng mã giảm giá
            if ($order->discount_id) {
                // Kiểm tra loại giảm giá (Phần trăm hay Tiền mặt)
                if ($order->discount_type == 'percentage') {
                    // Tính số tiền giảm theo %
                    $discountAmount = $total * ($order->discount_value / 100);

                    // Kiểm tra giới hạn giảm tối đa (nếu có)
                    if (isset($order->max_discount_amount) && $order->max_discount_amount > 0 && $discountAmount > $order->max_discount_amount) {
                        $discountAmount = $order->max_discount_amount; // Gán bằng mức tối đa nếu vượt quá
                    }
                } else {
                    // Giảm theo số tiền cố định
                    $discountAmount = $order->discount_value;
                }
            }
            
            // Tính tổng tiền khách phải trả
            $finalAmount = $total - $discountAmount;
            if ($finalAmount < 0) $finalAmount = 0; // Không để số tiền bị âm

            // Trả về kết quả JSON cho Client (JS)
            echo json_encode([
                'status' => 'success', 
                'order_id' => $order->order_id,
                'items' => $items,
                'total' => $total,
                'discount_amount' => $discountAmount,
                'discount_code' => $order->discount_code,
                'final_amount' => $finalAmount
            ]);
        } else {
            // Nếu không có đơn -> Trả về trạng thái bàn trống
            echo json_encode(['status' => 'empty']);
        }
    }

    // --- API: THÊM MÓN VÀO ĐƠN ---
    public function addToOrder() {
        // Chỉ xử lý khi request là POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Kiểm tra lại ca làm việc để đảm bảo an toàn
            if (!$this->cashModel->getCurrentSession()) {
                echo json_encode(['status' => 'error', 'message' => 'Chưa mở ca làm việc!']);
                return;
            }

            // Lấy dữ liệu từ form gửi lên
            $tableId = $_POST['table_id'];
            $productId = $_POST['product_id'];
            $price = $_POST['price']; 
            $userId = $_SESSION['user_id'];
            
            // [ĐÃ SỬA] Ghi chú mặc định là rỗng (trước đây là 'Size M')
            $defaultNote = ''; 

            $orderModel = $this->model('OrderModel');
            
            // Kiểm tra xem bàn đã có đơn chưa
            $order = $orderModel->getUnpaidOrder($tableId);
            $isNewOrder = false; // Cờ đánh dấu đơn mới

            // Nếu chưa có đơn -> Tạo đơn mới
            if (!$order) {
                $orderId = $orderModel->createOrder($userId, $tableId);
                $isNewOrder = true;
            } else {
                // Nếu có rồi -> Lấy ID đơn cũ
                $orderId = $order->order_id;
            }

            // Thực hiện thêm món vào đơn
            if ($orderId) {
                $orderModel->addNumItem($orderId, $productId, $price, $defaultNote);
                // Trả về thành công
                echo json_encode(['status' => 'success', 'is_new_order' => $isNewOrder]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Lỗi tạo đơn']);
            }
        }
    }

    // --- API: CẬP NHẬT MÓN (Chỉ cập nhật Ghi chú) ---
    // Hàm này đã được viết lại gọn gàng để bỏ logic Size/Topping
    public function updateOrderItem() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Kiểm tra ca làm việc
            if (!$this->cashModel->getCurrentSession()) {
                echo json_encode(['status' => 'error', 'message' => 'Chưa mở ca làm việc!']);
                return;
            }

            // Lấy dữ liệu ID món và ID đơn
            $detailId = $_POST['detail_id'];
            $orderId = $_POST['order_id'];
            // Lấy giá gốc (được gửi từ JS)
            $basePrice = (int)$_POST['base_price'];
            
            // Lấy ghi chú người dùng nhập từ popup (nếu có), cắt khoảng trắng thừa
            $newNote = isset($_POST['custom_note']) ? trim($_POST['custom_note']) : '';

            // Giá giữ nguyên là giá gốc (vì không còn phụ thu Size/Topping)
            $finalPrice = $basePrice;

            $orderModel = $this->model('OrderModel');
            
            // Gọi Model để cập nhật vào Database
            if ($orderModel->updateOrderDetail($detailId, $orderId, $finalPrice, $newNote)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    }

    // --- API: XÓA MÓN KHỎI ĐƠN ---
    public function deleteItem() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $detailId = $_POST['detail_id'];
            $orderId = $_POST['order_id'];
            $orderModel = $this->model('OrderModel');
            
            // Gọi hàm xóa trong Model
            if ($orderModel->deleteOrderDetail($detailId, $orderId)) {
                // Kiểm tra xem đơn hàng sau khi xóa có bị hủy luôn không (nếu hết món -> hủy đơn)
                $db = new Database();
                $db->query("SELECT status FROM orders WHERE order_id = :oid");
                $db->bind(':oid', $orderId);
                $check = $db->single();
                
                // Nếu trạng thái là 'canceled' -> Đơn rỗng
                $isEmpty = ($check->status == 'canceled');

                echo json_encode(['status' => 'success', 'is_empty' => $isEmpty]);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    }

// --- API: ÁP DỤNG / HỦY MÃ GIẢM GIÁ ---
    public function applyDiscount() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tableId = $_POST['table_id'];
            $code = trim($_POST['code']); // Lấy mã code
            
            $orderModel = $this->model('OrderModel');
            $this->discountModel = $this->model('DiscountModel'); 
            
            // ... (Giữ nguyên đoạn lấy đơn hàng và kiểm tra mã rỗng) ...
            
            // Lấy đơn hàng hiện tại
            $order = $orderModel->getUnpaidOrder($tableId);
            if (!$order) {
                echo json_encode(['status' => 'error', 'message' => 'Bàn trống!']);
                return;
            }

            // Nếu mã rỗng -> Người dùng muốn HỦY mã đang dùng
            if (empty($code)) {
                $orderModel->removeDiscount($order->order_id);
                echo json_encode(['status' => 'success', 'message' => 'Đã hủy mã giảm giá']);
                return;
            }

            // Tìm thông tin mã giảm giá trong DB
            $discount = $this->discountModel->getDiscountByCode($code);
            
            if ($discount) {
                // Kiểm tra điều kiện đơn tối thiểu
                if (isset($discount->min_order_value) && $discount->min_order_value > 0) {
                    $currentTotal = $order->total_amount;
                    
                    // [ĐÃ SỬA] Thông báo rõ ràng số tiền cần đạt được
                    if ($currentTotal < $discount->min_order_value) {
                        echo json_encode([
                            'status' => 'error', 
                            // number_format: Định dạng số thêm dấu phẩy (VD: 1,000,000)
                            'message' => 'Mã này chỉ áp dụng cho đơn từ ' . number_format($discount->min_order_value) . 'đ trở lên!'
                        ]);
                        return; // Dừng nếu không đủ điều kiện
                    }
                }
                
                // Áp dụng mã thành công
                $orderModel->applyDiscount($order->order_id, $discount->discount_id);
                echo json_encode(['status' => 'success', 'message' => 'Áp dụng thành công!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Mã không hợp lệ hoặc hết hạn!']);
            }
        }
    }

    // --- API: THANH TOÁN (CHECKOUT) ---
    public function checkout() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Kiểm tra ca làm việc
            if (!$this->cashModel->getCurrentSession()) {
                echo json_encode(['status' => 'error', 'message' => 'Chưa mở ca!']);
                return;
            }

            $tableId = $_POST['table_id'];
            // Lấy phương thức thanh toán (mặc định là 'cash')
            $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';

            $orderModel = $this->model('OrderModel');
            $order = $orderModel->getUnpaidOrder($tableId);
            
            if ($order) {
                // Tính toán lại tổng tiền lần cuối
                $total = $order->total_amount;
                $discountAmount = 0;
                
                // Tính giảm giá
                if ($order->discount_id) {
                     if ($order->discount_type == 'percentage') {
                        $discountAmount = $total * ($order->discount_value / 100);
                        // Kiểm tra giới hạn tối đa
                        if (isset($order->max_discount_amount) && $order->max_discount_amount > 0 && $discountAmount > $order->max_discount_amount) {
                            $discountAmount = $order->max_discount_amount;
                        }
                    } else {
                        $discountAmount = $order->discount_value;
                    }
                }
                $finalAmount = $total - $discountAmount;
                if($finalAmount < 0) $finalAmount = 0;

                // Gọi Model thực hiện thanh toán (đóng đơn, giải phóng bàn)
                if ($orderModel->checkoutOrder($order->order_id, $finalAmount, $paymentMethod)) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Lỗi Database']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn']);
            }
        }
    }

    // --- API: TĂNG/GIẢM SỐ LƯỢNG MÓN ---
    public function updateQuantity() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $detailId = $_POST['detail_id'];
            $action = $_POST['action']; // 'inc' (tăng) hoặc 'dec' (giảm)
            
            $orderModel = $this->model('OrderModel');
            
            // Gọi Model cập nhật số lượng
            if ($orderModel->updateItemQuantity($detailId, $action)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error']);
            }
        }
    }
}
?>