<?php
/*
 * Vai trò: Quản lý toàn bộ vòng đời của Đơn hàng (Order) và Chi tiết đơn hàng (Order Details).
 * Kết nối với:
 * - PosController (để bán hàng).
 * - DashboardController (để báo cáo).
 * - Bảng orders (Lưu thông tin chung: bàn, người bán, tổng tiền, trạng thái).
 * - Bảng order_details (Lưu từng món: tên món, số lượng, giá, ghi chú).
 * - Bảng tables (Cập nhật trạng thái Trống/Có khách).
 * - Bảng discounts (Kiểm tra mã giảm giá).
 */

class OrderModel {
    // Khai báo biến $db để chứa đối tượng kết nối CSDL (Database).
    private $db;

    // Hàm khởi tạo (Constructor) - Chạy đầu tiên khi class được gọi.
    public function __construct() {
        // Mở kết nối đến Database thông qua class core/Database.php.
        $this->db = new Database();
    }

    // =========================================================================
    // 1. QUẢN LÝ ĐƠN HÀNG TRỰC TIẾP (POS)
    // =========================================================================

    // --- HÀM: LẤY ĐƠN HÀNG CHƯA THANH TOÁN CỦA MỘT BÀN ---
    public function getUnpaidOrder($tableId) {
        // Soạn lệnh SQL lấy thông tin đơn hàng (o.*) và thông tin mã giảm giá (d.*).
        // LEFT JOIN discounts: Để lấy thêm thông tin mã giảm giá (nếu đơn hàng có dùng mã).
        // WHERE table_id = :tid: Tìm đơn của bàn có ID này.
        // AND status = 'pending': Và đơn đó phải đang chờ thanh toán (chưa đóng).
        // [CẬP NHẬT] Thêm d.max_discount_amount để lấy giới hạn giảm giá
        $sql = "SELECT o.*, 
                       d.code as discount_code, 
                       d.type as discount_type, 
                       d.value as discount_value,
                       d.max_discount_amount 
                FROM orders o 
                LEFT JOIN discounts d ON o.discount_id = d.discount_id
                WHERE o.table_id = :tid AND o.status = 'pending'";
                
        $this->db->query($sql); // Chuẩn bị câu lệnh SQL.
        $this->db->bind(':tid', $tableId); // Điền ID bàn vào chỗ trống :tid.
        
        // Thực thi và trả về 1 dòng kết quả (Object đơn hàng) hoặc false (nếu bàn trống).
        return $this->db->single();
    }

    // --- HÀM: TẠO ĐƠN HÀNG MỚI ---
    public function createOrder($userId, $tableId) {
        // Bước 1: Tạo dòng mới trong bảng 'orders'.
        // Điền: Người tạo (:uid), Bàn số (:tid), Giờ tạo (NOW), Trạng thái ('pending').
        $this->db->query("INSERT INTO orders (user_id, table_id, order_time, status) 
                          VALUES (:uid, :tid, NOW(), 'pending')");
        $this->db->bind(':uid', $userId); // Gán ID nhân viên.
        $this->db->bind(':tid', $tableId); // Gán ID bàn.
        
        // Chạy lệnh INSERT. Nếu thành công (trả về true) thì làm tiếp:
        if ($this->db->execute()) {
            
            // Bước 2: Cập nhật trạng thái cái bàn đó thành 'occupied' (Có khách).
            // Để trên sơ đồ POS, bàn này sẽ hiện màu đỏ.
            $this->db->query("UPDATE tables SET status = 'occupied' WHERE table_id = :tid");
            $this->db->bind(':tid', $tableId);
            $this->db->execute(); // Chạy lệnh update bàn.
            
            // Bước 3: Lấy ID của cái đơn hàng vừa mới được tạo ra (LAST_INSERT_ID).
            // Để trả về cho Controller biết đơn này mã số bao nhiêu.
            $this->db->query("SELECT LAST_INSERT_ID() as id");
            
            return $this->db->single()->id; // Trả về con số ID (ví dụ: 105).
        }
        return false; // Nếu lỗi thì trả về false.
    }

    // --- HÀM: THÊM MÓN VÀO ĐƠN (Logic quan trọng) ---
    // Hàm này xử lý logic: Nếu món đã có -> Cộng dồn số lượng. Nếu chưa -> Thêm dòng mới.
    public function addNumItem($orderId, $productId, $price, $note = '') {
        // Bước 1: Kiểm tra xem trong đơn này, món này (với ghi chú y hệt) đã có chưa?
        $sql = "SELECT * FROM order_details 
                WHERE order_id = :oid AND product_id = :pid AND note = :note";
        
        $this->db->query($sql); // Chuẩn bị query kiểm tra.
        $this->db->bind(':oid', $orderId); // ID đơn hàng.
        $this->db->bind(':pid', $productId); // ID sản phẩm.
        $this->db->bind(':note', $note); // Ghi chú (VD: Size L, Ít đá).
        
        $existingItem = $this->db->single(); // Lấy kết quả tìm kiếm.

        // Nếu món đã tồn tại ($existingItem có dữ liệu)
        if ($existingItem) {
            // Trường hợp A: Cập nhật tăng số lượng (quantity) thêm 1.
            $this->db->query("UPDATE order_details 
                              SET quantity = quantity + 1 
                              WHERE order_detail_id = :did");
            $this->db->bind(':did', $existingItem->order_detail_id); // Dùng ID dòng chi tiết tìm được ở trên.
        } else {
            // Trường hợp B: Món chưa có -> Thêm dòng mới vào bảng chi tiết (INSERT).
            $this->db->query("INSERT INTO order_details (order_id, product_id, quantity, unit_price, note) 
                              VALUES (:oid, :pid, 1, :price, :note)");
            $this->db->bind(':oid', $orderId);
            $this->db->bind(':pid', $productId);
            $this->db->bind(':price', $price);
            $this->db->bind(':note', $note);
        }
        $this->db->execute(); // Chạy lệnh (Insert hoặc Update tùy trường hợp).
        
        // Bước 2: CỰC KỲ QUAN TRỌNG!
        // Sau khi thêm/bớt món, phải tính lại Tổng tiền của cả đơn hàng đó.
        $this->updateOrderTotal($orderId);
    }

    // --- HÀM: TÍNH LẠI TỔNG TIỀN ĐƠN HÀNG (CALCULATE TOTAL) ---
    // Hàm này được gọi mỗi khi thêm, sửa, xóa món.
    public function updateOrderTotal($orderId) {
        // Lệnh SQL thực hiện 2 việc:
        // 1. Tính tổng (SUM) của (số lượng * đơn giá) từ bảng chi tiết.
        // 2. Cập nhật (UPDATE) kết quả đó vào cột 'total_amount' của bảng đơn hàng chính.
        // COALESCE(..., 0): Nếu đơn rỗng (không có món), trả về 0.
        $sql = "UPDATE orders 
                SET total_amount = (
                    SELECT COALESCE(SUM(quantity * unit_price), 0) 
                    FROM order_details 
                    WHERE order_id = :oid
                ) 
                WHERE order_id = :oid";
        
        $this->db->query($sql); // Chuẩn bị lệnh.
        $this->db->bind(':oid', $orderId); // Điền ID đơn hàng.
        $this->db->execute(); // Chạy lệnh.

        // Gọi thêm hàm phụ để kiểm tra lại Mã giảm giá (nếu có).
        // Ví dụ: Đơn đang 100k dùng mã giảm giá 10k (yêu cầu đơn > 90k).
        // Nếu xóa bớt món, đơn còn 50k -> Mã giảm giá phải bị gỡ ra.
        $this->revalidateDiscount($orderId);
    }

    // --- HÀM: KIỂM TRA LẠI MÃ GIẢM GIÁ (Hàm nội bộ - private) ---
    private function revalidateDiscount($orderId) {
        // Lấy Tổng tiền hiện tại và Điều kiện tối thiểu (min_order_value) của mã đang dùng.
        $sql = "SELECT o.total_amount, d.discount_id, d.min_order_value 
                FROM orders o
                JOIN discounts d ON o.discount_id = d.discount_id
                WHERE o.order_id = :oid";
        
        $this->db->query($sql);
        $this->db->bind(':oid', $orderId);
        $data = $this->db->single(); // Lấy kết quả.

        // Nếu đơn hàng này ĐANG CÓ dùng mã giảm giá ($data không rỗng)
        if ($data) {
            // Kiểm tra điều kiện:
            // Nếu mã có yêu cầu tối thiểu (min > 0) VÀ Tổng tiền hiện tại < Mức tối thiểu đó.
            if ($data->min_order_value > 0 && $data->total_amount < $data->min_order_value) {
                // Gọi hàm gỡ mã giảm giá ra khỏi đơn hàng.
                $this->removeDiscount($orderId);
            }
        }
    }

    // --- HÀM: GỠ MÃ GIẢM GIÁ ---
    public function removeDiscount($orderId) {
        // Update cột discount_id thành NULL (Xóa liên kết với mã giảm giá).
        $this->db->query("UPDATE orders SET discount_id = NULL WHERE order_id = :oid");
        $this->db->bind(':oid', $orderId);
        return $this->db->execute();
    }

    // --- HÀM: THANH TOÁN (CHECKOUT) ---
    public function checkoutOrder($orderId, $finalAmount, $paymentMethod = 'cash') {
        // Bước 1: Cập nhật thông tin thanh toán vào đơn hàng.
        // - status = 'paid' (Đã thanh toán).
        // - final_amount = Số tiền khách thực trả.
        // - payment_method = 'cash' (Tiền mặt) hoặc 'transfer' (Chuyển khoản).
        // - payment_time = NOW() (Lưu thời điểm thanh toán thực tế).
        $sql = "UPDATE orders 
                SET status = 'paid', 
                    final_amount = :amount, 
                    payment_method = :method,
                    payment_time = NOW() 
                WHERE order_id = :oid";
        
        $this->db->query($sql);
        $this->db->bind(':amount', $finalAmount);
        $this->db->bind(':method', $paymentMethod);
        $this->db->bind(':oid', $orderId);
        
        // Nếu cập nhật đơn thành công...
        if ($this->db->execute()) {
            
            // Bước 2: Tìm xem đơn hàng này đang ngồi ở Bàn nào?
            $this->db->query("SELECT table_id FROM orders WHERE order_id = :oid");
            $this->db->bind(':oid', $orderId);
            $row = $this->db->single();
            
            // Nếu tìm thấy thông tin bàn...
            if ($row) {
                // Bước 3: Cập nhật bàn đó về trạng thái 'empty' (Trống).
                // Để trên sơ đồ POS, bàn này chuyển lại màu trắng/xanh.
                $this->db->query("UPDATE tables SET status = 'empty' WHERE table_id = :tid");
                $this->db->bind(':tid', $row->table_id);
                $this->db->execute();
            }
            return true; // Báo thành công.
        }
        return false; // Báo lỗi.
    }

    // =========================================================================
    // CÁC HÀM PHỤ TRỢ KHÁC (Sửa món, Xóa món, Tăng giảm SL...)
    // =========================================================================

    // Lấy danh sách chi tiết các món trong đơn (để in hóa đơn hoặc hiển thị lên POS)
    public function getOrderDetails($orderId) {
        // SELECT od.*: Lấy chi tiết đơn.
        // JOIN products p: Để lấy thêm Tên món (product_name) và Ảnh (image).
        $sql = "SELECT od.*, p.product_name, p.image, p.price 
                FROM order_details od 
                JOIN products p ON od.product_id = p.product_id 
                WHERE od.order_id = :oid";
        
        $this->db->query($sql);
        $this->db->bind(':oid', $orderId);
        return $this->db->resultSet(); // Trả về danh sách nhiều món.
    }

    // Áp dụng mã giảm giá (Cập nhật discount_id vào đơn hàng)
    public function applyDiscount($orderId, $discountId) {
        $this->db->query("UPDATE orders SET discount_id = :did WHERE order_id = :oid");
        $this->db->bind(':did', $discountId);
        $this->db->bind(':oid', $orderId);
        return $this->db->execute();
    }

    // Xóa một món khỏi đơn (Delete dòng chi tiết)
    public function deleteOrderDetail($detailId, $orderId) {
        // Xóa dòng có ID chi tiết này.
        $this->db->query("DELETE FROM order_details WHERE order_detail_id = :did");
        $this->db->bind(':did', $detailId);
        
        // Nếu xóa thành công...
        if ($this->db->execute()) {
            $this->updateOrderTotal($orderId); // Tính lại tổng tiền đơn hàng.

            // Kiểm tra xem đơn hàng còn món nào không?
            $this->db->query("SELECT COUNT(*) as count FROM order_details WHERE order_id = :oid");
            $this->db->bind(':oid', $orderId);
            $row = $this->db->single();

            // Nếu đơn trống trơn (count == 0) -> Hủy luôn đơn hàng (trả bàn về trống).
            if ($row->count == 0) {
                $this->cancelEmptyOrder($orderId);
            }
            return true;
        }
        return false;
    }

    // Hủy đơn hàng rỗng (Hàm nội bộ)
    private function cancelEmptyOrder($orderId) {
        // Tìm bàn của đơn này
        $this->db->query("SELECT table_id FROM orders WHERE order_id = :oid");
        $this->db->bind(':oid', $orderId);
        $orderInfo = $this->db->single();

        // Trả bàn về 'empty'
        if ($orderInfo) {
            $this->db->query("UPDATE tables SET status = 'empty' WHERE table_id = :tid");
            $this->db->bind(':tid', $orderInfo->table_id);
            $this->db->execute();
        }
        
        // Đổi trạng thái đơn thành 'canceled' (Đã hủy)
        $this->db->query("UPDATE orders SET status = 'canceled' WHERE order_id = :oid");
        $this->db->bind(':oid', $orderId);
        $this->db->execute();
    }

    // Cập nhật giá/ghi chú (Dùng khi Sửa món: đổi Size, thêm Topping)
    public function updateOrderDetail($detailId, $orderId, $newPrice, $newNote) {
        $sql = "UPDATE order_details 
                SET unit_price = :price, note = :note 
                WHERE order_detail_id = :did";
        
        $this->db->query($sql);
        $this->db->bind(':price', $newPrice);
        $this->db->bind(':note', $newNote);
        $this->db->bind(':did', $detailId);
        
        if ($this->db->execute()) {
            $this->updateOrderTotal($orderId); // Sửa xong nhớ tính lại tiền.
            return true;
        }
        return false;
    }

    // Tăng/Giảm số lượng món (+/-) khi bấm nút trên POS
    public function updateItemQuantity($detailId, $action) {
        // Tìm món đó để biết đang thuộc đơn nào và số lượng hiện tại là bao nhiêu.
        $this->db->query("SELECT order_id, quantity FROM order_details WHERE order_detail_id = :did");
        $this->db->bind(':did', $detailId);
        $item = $this->db->single();
        
        if (!$item) return false;
        $orderId = $item->order_id;

        if ($action == 'inc') {
             // Nếu hành động là 'inc' (increase) -> Cộng 1.
             $this->db->query("UPDATE order_details SET quantity = quantity + 1 WHERE order_detail_id = :did");
             $this->db->bind(':did', $detailId);
             $this->db->execute();
        } elseif ($action == 'dec') {
             // Nếu hành động là 'dec' (decrease) -> Trừ 1 (Nhưng chỉ khi số lượng > 1).
             // Nếu số lượng = 1 thì không trừ nữa (để tránh về 0 hoặc âm).
             if ($item->quantity > 1) {
                 $this->db->query("UPDATE order_details SET quantity = quantity - 1 WHERE order_detail_id = :did");
                 $this->db->bind(':did', $detailId);
                 $this->db->execute();
             }
        }
        
        $this->updateOrderTotal($orderId); // Tính lại tổng tiền.
        return true;
    }



    // =========================================================================
    // CÁC HÀM BÁO CÁO & LỊCH SỬ (Dùng cho Admin Dashboard)
    // =========================================================================

    // Đếm số lượng đơn (Dùng cho phân trang)
    public function countOrders($fromDate = null, $toDate = null, $search = '') {
        // Đếm tổng số đơn đã thanh toán ('paid').
        // LEFT JOIN users: Để tìm kiếm theo tên nhân viên.
        $sql = "SELECT COUNT(*) as count FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE o.status = 'paid'";
        
        // Thêm điều kiện lọc ngày (nếu có).
        if ($fromDate && $toDate) $sql .= " AND DATE(o.order_time) BETWEEN :from AND :to";
        
        // Thêm điều kiện tìm kiếm từ khóa (Mã đơn hoặc Tên nhân viên).
        if (!empty($search)) $sql .= " AND (o.order_id LIKE :search OR u.full_name LIKE :search)";

        $this->db->query($sql);
        // Bind dữ liệu...
        if ($fromDate && $toDate) {
            $this->db->bind(':from', $fromDate);
            $this->db->bind(':to', $toDate);
        }
        if (!empty($search)) $this->db->bind(':search', "%$search%");

        $row = $this->db->single();
        return $row->count; // Trả về con số đếm được.
    }

    // Lấy danh sách đơn hàng đã thanh toán (Có phân trang LIMIT OFFSET)
    public function getAllOrders($fromDate = null, $toDate = null, $limit = 10, $offset = 0, $search = '') {
        $sql = "SELECT o.*, u.full_name as staff_name FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE o.status = 'paid'";
        
        // ... (Logic lọc giống hàm countOrders) ...
        if ($fromDate && $toDate) $sql .= " AND DATE(o.order_time) BETWEEN :from AND :to";
        if (!empty($search)) $sql .= " AND (o.order_id LIKE :search OR u.full_name LIKE :search)";
        
        $sql .= " ORDER BY o.order_time DESC LIMIT :limit OFFSET :offset"; // Sắp xếp mới nhất và phân trang.
        
        $this->db->query($sql);
        if ($fromDate && $toDate) {
            $this->db->bind(':from', $fromDate);
            $this->db->bind(':to', $toDate);
        }
        if (!empty($search)) $this->db->bind(':search', "%$search%");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->resultSet(); // Trả về danh sách đơn.
    }

    // Lấy thông tin chi tiết 1 đơn hàng (Dùng khi Admin bấm nút xem chi tiết)
    public function getOrderDetail($order_id) {
        // Lấy thông tin chung của đơn (Người bán, Ngày giờ...).
        $sqlOrder = "SELECT o.*, u.full_name as staff_name FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE o.order_id = :id";
        $this->db->query($sqlOrder);
        $this->db->bind(':id', $order_id);
        $order = $this->db->single();

        // Lấy danh sách các món trong đơn đó.
        $sqlItems = "SELECT od.*, p.product_name, p.image, p.price 
                     FROM order_details od
                     JOIN products p ON od.product_id = p.product_id
                     WHERE od.order_id = :id";
        
        $this->db->query($sqlItems);
        $this->db->bind(':id', $order_id);
        $items = $this->db->resultSet();

        // Trả về mảng chứa cả 2 phần: Thông tin chung và Danh sách món.
        return ['info' => $order, 'items' => $items];
    }
}