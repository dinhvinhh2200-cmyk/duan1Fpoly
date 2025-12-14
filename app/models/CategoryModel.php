<?php
/*
 * CATEGORY MODEL
 * Vai trò: Quản lý các nhóm món ăn (Ví dụ: Cà phê, Trà sữa, Ăn vặt...).

Nhiệm vụ chính:

Lấy danh sách danh mục để hiển thị lên Menu hoặc POS.

Thêm/Sửa tên danh mục.

Xóa danh mục (Xóa mềm - chỉ ẩn đi chứ không mất hẳn).

Khôi phục danh mục đã xóa.
 */
class CategoryModel {
    // Biến $db để giữ kết nối với cơ sở dữ liệu
    private $db;

    // --- HÀM KHỞI TẠO ---
    public function __construct() {
        // Gọi class Database để mở kết nối MySQL.
        // Biến $this->db sẽ là "cái chìa khóa" để mở kho dữ liệu.
        $this->db = new Database();
    }

    // =========================================================================
    // 1. LẤY DANH SÁCH DANH MỤC (READ)
    // =========================================================================
    public function getCategories() {
        // [QUERY] Soạn lệnh SQL:
        // - SELECT *: Lấy tất cả thông tin.
        // - FROM categories: Từ bảng danh mục.
        // - WHERE is_deleted = 0: Chỉ lấy những cái CHƯA bị xóa.
        // - ORDER BY ... DESC: Cái nào mới tạo (ID lớn) thì đưa lên đầu.
        $sql = "SELECT * FROM categories WHERE is_deleted = 0 ORDER BY category_id DESC";
        
        // [GỬI LỆNH] Nộp phiếu yêu cầu.
        $this->db->query($sql);
        
        // [LẤY KẾT QUẢ] Nhận về danh sách nhiều dòng.
        return $this->db->resultSet();
    }

    // =========================================================================
    // 2. THÊM DANH MỤC MỚI (CREATE)
    // =========================================================================
    public function addCategory($name) {
        // [QUERY] Soạn lệnh INSERT:
        // Thêm tên vào cột 'category_name'. Dùng :name để giữ chỗ.
        $sql = "INSERT INTO categories (category_name) VALUES (:name)";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền tên thật vào chỗ trống :name.
        $this->db->bind(':name', $name);
        
        // [EXECUTE] Bấm nút chạy. Trả về True nếu thêm thành công.
        return $this->db->execute();
    }

    // =========================================================================
    // 3. KIỂM TRA TRÙNG TÊN (VALIDATION)
    // =========================================================================
    // Hàm này để xem tên danh mục nhập vào có bị trùng không.
    public function checkNameExists($name, $excludeId = null) {
        // [QUERY] Tìm ID của danh mục có tên giống tên nhập vào (:name).
        $sql = "SELECT category_id FROM categories WHERE category_name = :name";
        
        // Nếu đang Sửa ($excludeId có giá trị), ta thêm điều kiện "Trừ chính nó ra".
        // Để không bị báo lỗi trùng với tên cũ của chính mình.
        if ($excludeId) {
            $sql .= " AND category_id != :id";
        }
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền tên cần kiểm tra.
        $this->db->bind(':name', $name);
        
        // [BIND] Điền ID cần loại trừ (nếu có).
        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }
        
        // [THỰC THI] Lấy 1 dòng kết quả.
        $this->db->single();
        
        // [KẾT QUẢ] Đếm số dòng tìm thấy. 
        // > 0 nghĩa là CÓ trùng -> Trả về true.
        return $this->db->rowCount() > 0;
    }

    // =========================================================================
    // 4. LẤY TẤT CẢ BAO GỒM ĐÃ XÓA (ADMIN VIEW)
    // =========================================================================
    public function getAllCategoriesIncludingDeleted() {
        // [QUERY] Lấy hết tất cả.
        // Sắp xếp: is_deleted ASC (Cái chưa xóa lên trên, cái đã xóa xuống dưới).
        $sql = "SELECT * FROM categories ORDER BY is_deleted ASC, category_id DESC";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [LẤY KẾT QUẢ]
        return $this->db->resultSet();
    }

    // =========================================================================
    // 5. CẬP NHẬT TÊN DANH MỤC (UPDATE)
    // =========================================================================
    public function updateCategory($id, $name) {
        // [QUERY] Sửa cột category_name thành tên mới, tại dòng có category_id là :id.
        $sql = "UPDATE categories 
                SET category_name = :name 
                WHERE category_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền ID và Tên mới.
        $this->db->bind(':id', $id);
        $this->db->bind(':name', $name);
        
        // [EXECUTE] Chạy lệnh.
        return $this->db->execute();
    }

    // =========================================================================
    // 6. XÓA MỀM (SOFT DELETE)
    // =========================================================================
    public function deleteCategory($id) {
        // [QUERY] Thay vì xóa hẳn, ta đánh dấu is_deleted = 1 (ẩn đi).
        $sql = "UPDATE categories SET is_deleted = 1 WHERE category_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền ID cần xóa.
        $this->db->bind(':id', $id);
        
        // [EXECUTE] Chạy lệnh.
        return $this->db->execute();
    }

    // =========================================================================
    // 7. KHÔI PHỤC DANH MỤC (RESTORE)
    // =========================================================================
    public function restoreCategory($id) {
        // [QUERY] Đánh dấu lại is_deleted = 0 (hiện lại).
        $sql = "UPDATE categories SET is_deleted = 0 WHERE category_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền ID cần khôi phục.
        $this->db->bind(':id', $id);
        
        // [EXECUTE] Chạy lệnh.
        return $this->db->execute();
    }
}