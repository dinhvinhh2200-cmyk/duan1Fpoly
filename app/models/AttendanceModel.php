<?php
/*
 * ATTENDANCE MODEL
 * Vai trò: Ghi nhận lịch sử Vào/Ra (Log) của nhân viên.

Nhiệm vụ chính:

Check-in: Ghi giờ bắt đầu làm.

Check-out: Ghi giờ kết thúc (Cập nhật vào dòng đã Check-in hoặc tạo mới nếu quên).

Lịch sử: Lấy danh sách chấm công để Admin xem báo cáo.
 */
class AttendanceModel {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Kết nối DB
    }

    // =========================================================================
    // 1. CHẤM CÔNG VÀO (CHECK-IN)
    // =========================================================================
    public function checkIn($name) {
        $today = date("Y-m-d"); // Lấy ngày hôm nay
        $now = date("H:i:s");   // Lấy giờ hiện tại

        // [QUERY] Tạo một dòng chấm công mới
        // Chỉ điền giờ vào (check_in_time), giờ ra để trống
        $this->db->query("INSERT INTO staff_attendance (staff_name, check_in_time, created_at) 
                          VALUES (:name, :time, :date)");
        
        // [BIND] Điền dữ liệu
        $this->db->bind(':name', $name);
        $this->db->bind(':time', $now);
        $this->db->bind(':date', $today);
        
        // [EXECUTE]
        return $this->db->execute();
    }

    // =========================================================================
public function checkOut($name) {
        $today = date("Y-m-d");
        $now = date("H:i:s");

        // BƯỚC 1: Tìm xem hôm nay nhân viên này đã Check-in chưa?
        // Điều kiện: Cùng tên + Cùng ngày + Giờ ra còn trống (chưa out)
        $this->db->query("SELECT id FROM staff_attendance 
                          WHERE staff_name = :name AND created_at = :date AND check_out_time IS NULL 
                          ORDER BY id DESC LIMIT 1");
        
        $this->db->bind(':name', $name);
        $this->db->bind(':date', $today);
        $row = $this->db->single();

        // BƯỚC 2: Xử lý Logic
        if ($row) {
            // TRƯỜNG HỢP A: Tìm thấy dòng Check-in hợp lệ -> CẬP NHẬT giờ ra
            $this->db->query("UPDATE staff_attendance SET check_out_time = :time WHERE id = :id");
            $this->db->bind(':time', $now);
            $this->db->bind(':id', $row->id);
            return $this->db->execute(); // Trả về true (Thành công)
        } else {
            // TRƯỜNG HỢP B: Không thấy Check-in -> TRẢ VỀ FALSE (Thất bại)
            // (Trước đây là tạo dòng mới, giờ ta chặn lại)
            return false;
        }
    }

// =========================================================================
// 3. XEM LỊCH SỬ (HISTORY - REPORT) - [CẬP NHẬT: LỌC THEO KHOẢNG NGÀY]
// =========================================================================
public function getHistory($fromDate = null, $toDate = null, $keyword = '') {
    // [QUERY] Kỹ thuật "WHERE 1=1" giúp nối chuỗi điều kiện dễ dàng hơn
    $sql = "SELECT * FROM staff_attendance WHERE 1=1";
    
    // Nếu có lọc theo khoảng thời gian
    if ($fromDate && $toDate) {
        // Sử dụng BETWEEN để lấy dữ liệu trong khoảng
        $sql .= " AND created_at BETWEEN :from AND :to";
    }
    
    // Nếu có tìm kiếm tên -> Thêm điều kiện LIKE
    if (!empty($keyword)) {
        $sql .= " AND staff_name LIKE :keyword";
    }
    
    // Sắp xếp: Mới nhất lên đầu
    $sql .= " ORDER BY created_at DESC, check_in_time DESC"; 
    
    // [GỬI LỆNH]
    $this->db->query($sql);
    
    // [BIND] Điền các tham số lọc
    if ($fromDate && $toDate) {
        $this->db->bind(':from', $fromDate);
        $this->db->bind(':to', $toDate);
    }
    if (!empty($keyword)) $this->db->bind(':keyword', "%$keyword%");
    
    // [LẤY KẾT QUẢ]
    return $this->db->resultSet();
}
}