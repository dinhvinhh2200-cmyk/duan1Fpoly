<?php
/*
 * QR STAFF MODEL
 * Vai trò: Quản lý "Danh sách trắng" (Whitelist) các nhân viên được phép chấm công.

Nhiệm vụ chính:

Lấy danh sách để Admin xem.

Thêm tên nhân viên mới vào danh sách cho phép.

Kiểm tra tên nhân viên nhập vào có hợp lệ không (quan trọng nhất).
 */
class QrStaffModel {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Kết nối DB
    }

    // =========================================================================
    // 1. LẤY DANH SÁCH (READ)
    // =========================================================================
    public function getAll() {
        // [QUERY] Lấy tất cả từ bảng qr_allowed_staff, mới nhất lên đầu
        $this->db->query("SELECT * FROM qr_allowed_staff ORDER BY id DESC");
        
        // [LẤY KẾT QUẢ]
        return $this->db->resultSet();
    }

    // =========================================================================
    // 2. THÊM TÊN MỚI (CREATE)
    // =========================================================================
    public function add($name) {
        // [QUERY] Thêm tên vào bảng
        $this->db->query("INSERT INTO qr_allowed_staff (full_name) VALUES (:name)");
        
        // [BIND] Điền tên
        $this->db->bind(':name', $name);
        
        // [EXECUTE]
        return $this->db->execute();
    }

    // =========================================================================
    // 3. XÓA TÊN (DELETE)
    // =========================================================================
    public function delete($id) {
        // [QUERY] Xóa vĩnh viễn dòng này (vì bảng này đơn giản, xóa cứng luôn)
        $this->db->query("DELETE FROM qr_allowed_staff WHERE id = :id");
        
        // [BIND]
        $this->db->bind(':id', $id);
        
        // [EXECUTE]
        return $this->db->execute();
    }

    // =========================================================================
    // 4. KIỂM TRA TÊN HỢP LỆ (VALIDATION) - QUAN TRỌNG
    // =========================================================================
    // Hàm này được gọi khi nhân viên nhập tên để Check-in
    public function checkNameExists($name) {
        // [QUERY] Tìm xem có ID nào ứng với tên này không
        $this->db->query("SELECT id FROM qr_allowed_staff WHERE full_name = :name");
        
        // [BIND]
        $this->db->bind(':name', $name);
        
        // [THỰC THI] Lấy 1 dòng
        $this->db->single();
        
        // [KẾT QUẢ] Nếu tìm thấy (>0) thì là tên hợp lệ -> Trả về true
        return $this->db->rowCount() > 0;
    }
    
    // Hàm phụ: Dùng để kiểm tra trùng khi Admin thêm mới (gọi lại hàm trên)
    public function isDuplicate($name) {
        return $this->checkNameExists($name);
    }
}