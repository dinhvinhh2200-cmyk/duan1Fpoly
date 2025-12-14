<?php
/*
 * DISCOUNT MODEL
 * Vai trò: Quản lý Mã giảm giá (Thêm, Sửa, Xóa mềm).
 */
class DiscountModel {
    private $db; 

    public function __construct() {
        $this->db = new Database(); 
    }

    // --- 1. TÌM MÃ GIẢM GIÁ (Để áp dụng) ---
    public function getDiscountByCode($code) {
        // [QUERY] Tìm dòng mã giảm giá trong bảng 'discounts' dựa vào code.
        // Điều kiện:
        // - code = :code: Mã phải khớp.
        // - is_deleted = 0: Chưa bị xóa vào thùng rác.
        // [ĐÃ SỬA] Bỏ điều kiện is_active = 1
        // [MỚI] Kiểm tra thời hạn sử dụng (start_date và end_date)
        $sql = "SELECT * FROM discounts 
                WHERE code = :code 
                AND is_deleted = 0
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())";
        
        $this->db->query($sql); 
        $this->db->bind(':code', $code); 
        
        return $this->db->single(); 
    }

    // Lấy thông tin chi tiết mã theo ID (Dùng khi bấm nút Sửa)
    public function getDiscountById($id) {
        // [QUERY] Lấy tất cả thông tin của mã có discount_id cụ thể.
        $sql = "SELECT * FROM discounts WHERE discount_id = :id";
        
        $this->db->query($sql); 
        $this->db->bind(':id', $id); 
        return $this->db->single(); 
    }

    // --- 2. KIỂM TRA TRÙNG MÃ (VALIDATION) ---
    // Dùng khi Thêm mới hoặc Cập nhật để tránh tạo 2 mã giống hệt nhau.
    public function checkCodeExists($code, $excludeId = null) {
        // [QUERY] Tìm xem có ID nào đang dùng mã code này không.
        $sql = "SELECT discount_id FROM discounts WHERE code = :code";
        
        if ($excludeId) {
            $sql .= " AND discount_id != :id";
        }
        
        $this->db->query($sql); 
        $this->db->bind(':code', $code); 
        
        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }
        
        $this->db->single(); 
        return $this->db->rowCount() > 0;
    }

    // --- 3. CẬP NHẬT MÃ GIẢM GIÁ (UPDATE) ---
    public function updateDiscount($data) {
        // [QUERY] Cập nhật các thông tin của mã giảm giá có ID tương ứng.
        // [ĐÃ SỬA] Xóa is_active khỏi câu lệnh UPDATE, thêm max_discount_amount
        // [MỚI] Thêm cập nhật start_date và end_date
        $sql = "UPDATE discounts 
                SET code = :code,            -- Cập nhật mã code mới
                    type = :type,            -- Loại (tiền mặt / phần trăm)
                    value = :val,            -- Giá trị giảm
                    min_order_value = :min_order, -- Điều kiện đơn tối thiểu
                    max_discount_amount = :max_disc, -- Điều kiện giảm tối đa
                    start_date = :s_date,    -- Ngày bắt đầu
                    end_date = :e_date       -- Ngày kết thúc
                WHERE discount_id = :id";   
        
        $this->db->query($sql); 
        
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':type', $data['type']);
        $this->db->bind(':val', $data['value']);
        $this->db->bind(':min_order', $data['min_order_value']);
        $this->db->bind(':max_disc', $data['max_discount_amount']); // Bind dữ liệu mới
        $this->db->bind(':s_date', $data['start_date']); // Bind dữ liệu ngày bắt đầu
        $this->db->bind(':e_date', $data['end_date']);   // Bind dữ liệu ngày kết thúc
        // [QUAN TRỌNG] Không còn bind :active nữa
        
        return $this->db->execute(); 
    }

    // --- 4. THÊM MÃ GIẢM GIÁ MỚI (CREATE) ---
    public function addDiscount($data) {
        // [QUERY] Thêm dòng mới vào bảng discounts.
        // [ĐÃ SỬA] Xóa is_active khỏi INSERT, thêm max_discount_amount
        // [MỚI] Thêm start_date và end_date
        $sql = "INSERT INTO discounts (code, type, value, min_order_value, max_discount_amount, start_date, end_date) 
                VALUES (:code, :type, :val, :min_order, :max_disc, :s_date, :e_date)";
        
        $this->db->query($sql); 
        
        $this->db->bind(':code', $data['code']);
        $this->db->bind(':type', $data['type']);
        $this->db->bind(':val', $data['value']);
        $this->db->bind(':min_order', $data['min_order_value']);
        $this->db->bind(':max_disc', $data['max_discount_amount']); // Bind dữ liệu mới
        $this->db->bind(':s_date', $data['start_date']); // Bind dữ liệu ngày bắt đầu
        $this->db->bind(':e_date', $data['end_date']);   // Bind dữ liệu ngày kết thúc
        // [QUAN TRỌNG] Không còn bind :active nữa
        
        return $this->db->execute(); 
    }

    // --- 5. XÓA MỀM (SOFT DELETE) ---
    public function deleteDiscount($id) {
        // [QUERY] Thay vì xóa hẳn, ta đánh dấu is_deleted = 1 (Đã xóa).
        // [ĐÃ SỬA] Chỉ cập nhật is_deleted = 1, bỏ cập nhật is_active
        $sql = "UPDATE discounts SET is_deleted = 1 WHERE discount_id = :id";
        
        $this->db->query($sql); 
        $this->db->bind(':id', $id); 
        
        return $this->db->execute(); 
    }

    // --- 6. LẤY TẤT CẢ BAO GỒM ĐÃ XÓA (ADMIN VIEW) ---
    public function getAllDiscountsIncludingDeleted() {
        // [QUERY] Lấy hết danh sách để Admin quản lý.
        // Sắp xếp: Mã chưa xóa lên trên (is_deleted ASC), Mã mới nhất lên trên (discount_id DESC).
        $sql = "SELECT * FROM discounts ORDER BY is_deleted ASC, discount_id DESC";
        
        $this->db->query($sql); 
        return $this->db->resultSet(); 
    }

    // --- 7. KHÔI PHỤC MÃ ĐÃ XÓA (RESTORE) ---
    public function restoreDiscount($id) {
        // [QUERY] Sửa lại is_deleted = 0 (Hồi sinh).
        // [ĐÃ SỬA] Chỉ cập nhật is_deleted = 0, bỏ cập nhật is_active
        $sql = "UPDATE discounts SET is_deleted = 0 WHERE discount_id = :id";
        
        $this->db->query($sql); 
        $this->db->bind(':id', $id); 
        
        return $this->db->execute(); 
    }

    // --- 8. LẤY DANH SÁCH MÃ KHẢ DỤNG (POS) ---
    // Hàm này dùng để hiển thị dropdown chọn mã trên màn hình bán hàng.
    public function getAvailableDiscounts() {
        // [QUERY] Chỉ lấy mã Chưa xóa (is_deleted=0) và Còn hạn sử dụng.
        // [ĐÃ SỬA] Bỏ điều kiện is_active = 1
        // [MỚI] Thêm điều kiện thời gian
        $sql = "SELECT * FROM discounts 
                WHERE is_deleted = 0 
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
                ORDER BY discount_id DESC";
        
        $this->db->query($sql); 
        return $this->db->resultSet(); 
    }
}