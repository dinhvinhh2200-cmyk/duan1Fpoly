<?php
/*
 * DASHBOARD MODEL
 * Vai trò: Xử lý các truy vấn thống kê cho trang quản trị
 * Chức năng:
 * 1. Tính toán doanh thu, số lượng đơn hàng.
 * 2. Lấy dữ liệu vẽ biểu đồ (Chart).
 * 3. Thống kê top sản phẩm bán chạy.
 */
class DashboardModel {
    // Khai báo thuộc tính $db để lưu đối tượng kết nối cơ sở dữ liệu.
    // 'private' để đảm bảo biến này chỉ được sử dụng trong class này.
    private $db;

    // Hàm khởi tạo (Constructor) - Chạy đầu tiên khi tạo mới đối tượng DashboardModel.
    public function __construct() {
        // Khởi tạo class Database (từ core) và gán vào biến $this->db.
        // Đây là bước mở kết nối đến MySQL.
        $this->db = new Database();
    }

    // -------------------------------------------------------------------------
    // THỐNG KÊ TỔNG QUAN (CARDS)
    // -------------------------------------------------------------------------

    // 1. Tổng doanh thu hôm nay
    // Lưu ý: Chỉ tính đơn hàng đã thanh toán (status = 'paid')
    public function getRevenueToday() {
        // [QUERY] Soạn lệnh SQL để tính tổng tiền:
        // - SUM(final_amount): Cộng dồn cột số tiền thực thu.
        // - FROM orders: Lấy từ bảng đơn hàng.
        // - WHERE DATE(order_time) = CURDATE(): Chỉ lấy các đơn có ngày trùng với ngày hiện tại.
        // - AND status = 'paid': Và trạng thái phải là 'đã thanh toán'.
        $sql = "SELECT SUM(final_amount) as total 
                FROM orders 
                WHERE DATE(order_time) = CURDATE() 
                AND status = 'paid'";
        
        $this->db->query($sql); // Đưa câu lệnh vào bộ chuẩn bị của Database.
        $row = $this->db->single(); // Thực thi lệnh và lấy về 1 dòng kết quả duy nhất.
        
        // Trả về giá trị 'total' từ kết quả. 
        // Toán tử '?? 0' nghĩa là: nếu kết quả là null (chưa bán được gì), thì trả về số 0.
        return $row->total ?? 0;
    }

    // 2. Số lượng đơn hàng hôm nay
    public function getOrdersCountToday() {
        // [QUERY] Soạn lệnh SQL để đếm số lượng:
        // - COUNT(*): Đếm tổng số dòng (đơn hàng).
        // - Các điều kiện WHERE giống hệt hàm trên (Ngày hôm nay và Đã thanh toán).
        $sql = "SELECT COUNT(*) as count 
                FROM orders 
                WHERE DATE(order_time) = CURDATE() 
                AND status = 'paid'";
        
        $this->db->query($sql); // Chuẩn bị câu lệnh query.
        $row = $this->db->single(); // Thực thi và lấy 1 dòng kết quả.
        
        // Trả về số lượng đếm được ('count'), hoặc 0 nếu không có.
        return $row->count ?? 0;
    }

    // 3. Tổng doanh thu tháng này
    public function getRevenueThisMonth() {
        // [QUERY] Tính tổng doanh thu theo tháng:
        // - MONTH(order_time) = MONTH(CURRENT_DATE()): So sánh Tháng của đơn hàng với Tháng hiện tại.
        // - YEAR(order_time) = YEAR(CURRENT_DATE()): So sánh Năm (để tránh lấy nhầm tháng này của năm ngoái).
        $sql = "SELECT SUM(final_amount) as total 
                FROM orders 
                WHERE MONTH(order_time) = MONTH(CURRENT_DATE()) 
                AND YEAR(order_time) = YEAR(CURRENT_DATE())
                AND status = 'paid'";
        
        $this->db->query($sql); // Chuẩn bị query.
        $row = $this->db->single(); // Thực thi và lấy kết quả.
        
        return $row->total ?? 0; // Trả về tổng tiền hoặc 0.
    }

    // 4. Lấy 5 đơn hàng mới nhất để hiển thị widget
    public function getRecentOrders() {
        // [QUERY] Lấy danh sách đơn hàng mới:
        // - SELECT *: Lấy tất cả thông tin đơn hàng.
        // - ORDER BY order_time DESC: Sắp xếp theo thời gian giảm dần (mới nhất lên đầu).
        // - LIMIT 5: Chỉ lấy đúng 5 dòng đầu tiên.
        $sql = "SELECT * FROM orders 
                WHERE status = 'paid' 
                ORDER BY order_time DESC 
                LIMIT 5";
        
        $this->db->query($sql); // Chuẩn bị query.
        
        // Thực thi và trả về một danh sách (mảng các Object) chứa 5 đơn hàng.
        return $this->db->resultSet();
    }

    // -------------------------------------------------------------------------
    // DỮ LIỆU BIỂU ĐỒ & TOP SẢN PHẨM
    // -------------------------------------------------------------------------

    // 5. Lấy dữ liệu biểu đồ doanh thu theo khoảng thời gian tùy chọn
    // Tham số: $fromDate (Ngày bắt đầu), $toDate (Ngày kết thúc), $type (Kiểu xem: ngày/tháng/năm)
    public function getRevenueChartData($fromDate, $toDate, $type = 'day') {
        // Dùng cấu trúc switch để xác định cách gom nhóm dữ liệu (GROUP BY) trong SQL.
        switch ($type) {
            case 'month':
                // Nếu xem theo THÁNG: Gom nhóm theo định dạng 'Năm-Tháng' (VD: 2023-11).
                $groupBy = "DATE_FORMAT(order_time, '%Y-%m')";
                break;
            case 'year':
                // Nếu xem theo NĂM: Gom nhóm theo định dạng 'Năm' (VD: 2023).
                $groupBy = "DATE_FORMAT(order_time, '%Y')";
                break;
            default: // 'day'
                // Mặc định xem theo NGÀY: Dùng hàm DATE() để lấy ngày (VD: 2023-11-25).
                $groupBy = "DATE(order_time)";
                break;
        }

        // [QUERY] Soạn lệnh SQL lấy dữ liệu vẽ biểu đồ:
        // - $groupBy as date_label: Lấy cột thời gian đã định dạng ở trên làm nhãn (trục hoành).
        // - SUM(final_amount): Tính tổng tiền cho mốc thời gian đó (trục tung).
        // - WHERE ... BETWEEN :from AND :to: Lọc dữ liệu trong khoảng thời gian người dùng chọn.
        // - GROUP BY date_label: Gom các đơn hàng cùng ngày/tháng lại với nhau.
        // - ORDER BY date_label ASC: Sắp xếp thời gian từ cũ đến mới để vẽ biểu đồ đúng chiều.
        $sql = "SELECT $groupBy as date_label, SUM(final_amount) as total 
                FROM orders 
                WHERE status = 'paid' 
                AND DATE(order_time) BETWEEN :from AND :to
                GROUP BY date_label
                ORDER BY date_label ASC";
        
        $this->db->query($sql); // Chuẩn bị query.
        $this->db->bind(':from', $fromDate); // Điền giá trị ngày bắt đầu vào tham số :from.
        $this->db->bind(':to', $toDate);     // Điền giá trị ngày kết thúc vào tham số :to.
        
        return $this->db->resultSet(); // Trả về danh sách dữ liệu để JS vẽ biểu đồ.
    }

    // 6. Top sản phẩm bán chạy theo khoảng thời gian
    public function getTopProductsByDateRange($fromDate, $toDate) {
        // [QUERY] Soạn lệnh SQL thống kê sản phẩm phức tạp:
        // - SELECT p.product_name: Lấy tên sản phẩm.
        // - SUM(od.quantity * od.unit_price) as revenue: Tính tổng doanh thu của sản phẩm đó (Số lượng x Đơn giá).
        // - JOIN: Nối 3 bảng (order_details -> products -> orders) để lấy đủ thông tin (tên món, ngày bán, trạng thái đơn).
        // - GROUP BY p.product_id: Gom nhóm theo từng mã sản phẩm.
        // - ORDER BY revenue DESC: Sắp xếp doanh thu từ cao xuống thấp.
        // - LIMIT 5: Chỉ lấy Top 5 món cao nhất.
        $sql = "SELECT p.product_name, SUM(od.quantity * od.unit_price) as revenue 
                FROM order_details od
                JOIN products p ON od.product_id = p.product_id
                JOIN orders o ON od.order_id = o.order_id
                WHERE o.status = 'paid' 
                AND DATE(o.order_time) BETWEEN :from AND :to
                GROUP BY p.product_id 
                ORDER BY revenue DESC 
                LIMIT 5";
        
        $this->db->query($sql); // Chuẩn bị query.
        $this->db->bind(':from', $fromDate); // Gán tham số ngày bắt đầu.
        $this->db->bind(':to', $toDate);     // Gán tham số ngày kết thúc.
        
        return $this->db->resultSet(); // Trả về danh sách top 5 sản phẩm.
    }

    // 7. (Dự phòng) Biểu đồ doanh thu 7 ngày gần nhất
    // Hàm này giữ lại để tương thích ngược nếu giao diện cũ cần dùng
    public function getRevenueLast7Days() {
        // [QUERY] Lấy doanh thu 7 ngày qua:
        // - DATE(NOW()) - INTERVAL 6 DAY: Lấy ngày hiện tại trừ đi 6 ngày (tạo khoảng 7 ngày).
        $sql = "SELECT DATE(order_time) as date, SUM(final_amount) as total 
                FROM orders 
                WHERE status = 'paid' 
                AND order_time >= DATE(NOW()) - INTERVAL 6 DAY
                GROUP BY DATE(order_time)
                ORDER BY date ASC";
        
        $this->db->query($sql); // Chuẩn bị query.
        return $this->db->resultSet(); // Trả về kết quả.
    }
    
    // 8. (Dự phòng) Top món bán chạy toàn thời gian
    // Hàm này tính top sản phẩm từ trước đến nay (không lọc ngày)
    public function getTopProducts() {
        // [QUERY] Tương tự hàm số 6 nhưng bỏ điều kiện ngày tháng.
        $sql = "SELECT p.product_name, SUM(od.quantity * od.unit_price) as revenue 
                FROM order_details od
                JOIN products p ON od.product_id = p.product_id
                JOIN orders o ON od.order_id = o.order_id
                WHERE o.status = 'paid' 
                GROUP BY p.product_id 
                ORDER BY revenue DESC 
                LIMIT 4"; // Lấy top 4.
        
        $this->db->query($sql); // Chuẩn bị query.
        return $this->db->resultSet(); // Trả về kết quả.
    }
}