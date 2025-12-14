<?php
/*
 * CASH MODEL
 * Class này chịu trách nhiệm làm việc với bảng 'cash_sessions' trong Database.
 * Chức năng: Quản lý toàn bộ quy trình của một Ca làm việc: Mở ca -> Bán hàng (tính tiền) -> Chốt ca.
 */
class CashModel {
    private $db; 

    public function __construct() {
        $this->db = new Database(); 
    }

    // --- 1. LẤY CA ĐANG HOẠT ĐỘNG ---
    public function getCurrentSession() {
        $sql = "SELECT * FROM cash_sessions WHERE end_time IS NULL ORDER BY start_time DESC LIMIT 1";
        $this->db->query($sql); 
        return $this->db->single(); 
    }

    // --- 2. MỞ CA MỚI ---
    public function startSession($userId, $openingCash) {
        $sql = "INSERT INTO cash_sessions (user_id, opening_cash, start_time) 
                VALUES (:uid, :cash, NOW())";
        $this->db->query($sql); 
        $this->db->bind(':uid', $userId);      
        $this->db->bind(':cash', $openingCash); 
        
        return $this->db->execute(); 
    }

    // --- 3. TÍNH DOANH THU CHI TIẾT ---
    public function getCurrentSessionSalesBreakdown($startTime) {
        $sql = "SELECT 
                    COALESCE(SUM(final_amount), 0) as total_all, 
                    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN final_amount ELSE 0 END), 0) as total_cash,
                    COALESCE(SUM(CASE WHEN payment_method = 'transfer' THEN final_amount ELSE 0 END), 0) as total_transfer
                FROM orders 
                WHERE status = 'paid'          
                AND payment_time >= :start_time"; 

        $this->db->query($sql); 
        $this->db->bind(':start_time', $startTime); 
        return $this->db->single(); 
    }

    // --- 4. CHỐT CA ---
    public function closeSession($sessionId, $userId, $totalSales, $actualCash, $note) {
        $sql = "UPDATE cash_sessions 
                SET end_time = NOW(), 
                    total_sales = :sales, 
                    close_user_id = :uid, 
                    actual_cash = :actual, 
                    note = :note 
                WHERE session_id = :sid";
        
        $this->db->query($sql); 
        $this->db->bind(':sales', $totalSales);
        $this->db->bind(':uid', $userId);
        $this->db->bind(':actual', $actualCash);
        $this->db->bind(':note', $note);
        $this->db->bind(':sid', $sessionId);
        
        return $this->db->execute(); 
    }

    // --- 5. LẤY LỊCH SỬ CÁC CA ĐÃ ĐÓNG (CẬP NHẬT LOGIC TÍNH TIỀN) ---
    public function getClosedSessions($limit = null) {
        // [CẬP NHẬT] Thêm subquery (cash_only) để tính tổng riêng tiền mặt trong ca đó
        // Mục đích: Để tính chênh lệch két tiền chính xác (không trừ tiền chuyển khoản)
        $sql = "SELECT cs.*, u.full_name,
                (
                    SELECT COALESCE(SUM(final_amount), 0)
                    FROM orders o
                    WHERE o.status = 'paid'
                    AND o.payment_method = 'cash'
                    AND o.payment_time >= cs.start_time
                    AND o.payment_time <= cs.end_time
                ) as cash_only
                FROM cash_sessions cs
                JOIN users u ON cs.user_id = u.user_id
                WHERE cs.end_time IS NOT NULL 
                ORDER BY cs.end_time DESC"; 
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }
        
        $this->db->query($sql); 
        
        if ($limit !== null) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT); 
        }
        
        return $this->db->resultSet(); 
    }

// --- 6. XEM CHI TIẾT MÓN TRONG CA ---
// --- 6. XEM CHI TIẾT MÓN TRONG CA (Đã tối ưu: Bỏ logic tách Topping) ---
    public function getItemsInSession($startTime, $endTime) {
        // Vì hệ thống không còn lưu Topping vào ghi chú nữa, ta chỉ cần dùng SQL thuần
        // để nhóm các món giống nhau (cùng Product ID và cùng Ghi chú).
        
        $sql = "SELECT p.product_name, 
                       od.note, 
                       SUM(od.quantity) as qty, 
                       SUM(od.quantity * od.unit_price) as subtotal
                FROM order_details od
                JOIN orders o ON od.order_id = o.order_id
                JOIN products p ON od.product_id = p.product_id
                WHERE o.status = 'paid' 
                AND o.payment_time >= :start_time 
                AND o.payment_time <= :end_time
                GROUP BY p.product_id, od.note  -- Gom nhóm theo Món và Ghi chú
                ORDER BY qty DESC"; 
        
        $this->db->query($sql); 
        $this->db->bind(':start_time', $startTime); 
        $this->db->bind(':end_time', $endTime); 
        
        return $this->db->resultSet(); 
    }

    // --- 7. LẤY DANH SÁCH ĐƠN HÀNG CỦA NHÂN VIÊN TRONG CA ---
    public function getStaffOrdersInSession($userId, $startTime, $endTime) {
        $sql = "SELECT * FROM orders 
                WHERE user_id = :uid 
                AND status = 'paid' 
                AND payment_time >= :start_time 
                AND payment_time <= :end_time 
                ORDER BY payment_time DESC";
        
        $this->db->query($sql);
        $this->db->bind(':uid', $userId);
        $this->db->bind(':start_time', $startTime);
        $this->db->bind(':end_time', $endTime);
        
        return $this->db->resultSet();
    }
}