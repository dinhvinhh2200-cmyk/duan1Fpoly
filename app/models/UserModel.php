<?php
/*
 * USER MODEL
 * Vai trò: Quản lý tài khoản đăng nhập (Admin & Staff).

Nhiệm vụ chính:

Xử lý Đăng nhập (Kiểm tra Username/Password).

CRUD tài khoản (Thêm nhân viên mới, Sửa thông tin, Xóa mềm).

Đổi mật khẩu cá nhân.

Khôi phục tài khoản đã xóa.
 */
class UserModel {
    private $db;

    public function __construct() {
        $this->db = new Database(); // Kết nối DB
    }

    // =========================================================================
    // 1. XỬ LÝ ĐĂNG NHẬP (LOGIN)
    // =========================================================================
    public function login($username, $password) {
        // Gọi hàm tìm user bên dưới để lấy thông tin dòng user đó
        $row = $this->findUserByUsername($username);
        
        // Kiểm tra: Nếu không tìm thấy dòng nào -> Sai Username
        if ($row == false) return false;
        
        // Kiểm tra mật khẩu: So sánh pass nhập vào với pass đã mã hóa (Hash) trong DB
        if (password_verify($password, $row->password_hash)) {
            // Kiểm tra thêm: Tài khoản có bị xóa không (is_deleted = 1)?
            if ($row->is_deleted == 1) {
                return false; // Bị khóa/xóa
            }
            return $row; // Thành công -> Trả về thông tin user
        }
        return false; // Sai Password
    }

    // =========================================================================
    // 2. TÌM USER THEO USERNAME (HELPER)
    // =========================================================================
    public function findUserByUsername($username) {
        // [QUERY] Tìm tất cả thông tin của user có username này
        $sql = "SELECT * FROM users WHERE username = :username";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền username thật
        $this->db->bind(':username', $username);
        
        // [LẤY KẾT QUẢ] Trả về 1 dòng duy nhất (Object)
        return $this->db->single();
    }

    // =========================================================================
    // 3. TÌM USER THEO ID (HELPER)
    // =========================================================================
    public function findUserById($id) {
        // [QUERY] Lấy thông tin user theo ID (Dùng cho trang Profile)
        $sql = "SELECT * FROM users WHERE user_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND]
        $this->db->bind(':id', $id);
        
        // [LẤY KẾT QUẢ]
        return $this->db->single();
    }

    // =========================================================================
    // 4. ĐỔI MẬT KHẨU (CHANGE PASSWORD)
    // =========================================================================
    public function changePassword($id, $newPassHash) {
        // [QUERY] Cập nhật cột password_hash mới cho user có ID này
        $sql = "UPDATE users SET password_hash = :pass WHERE user_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền mật khẩu MỚI (đã mã hóa) và ID user
        $this->db->bind(':pass', $newPassHash);
        $this->db->bind(':id', $id);
        
        // [EXECUTE] Chạy lệnh
        return $this->db->execute();
    }

    // =========================================================================
    // 5. LẤY DANH SÁCH NHÂN VIÊN (READ ALL)
    // =========================================================================
    public function getAllUsers() {
        // [QUERY] Lấy user (u) và tên chức vụ (r.role_name)
        // JOIN bảng roles để biết user là Admin hay Staff
        // Sắp xếp: User chưa xóa (is_deleted=0) lên trên đầu
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                ORDER BY u.is_deleted ASC, u.user_id DESC";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [LẤY KẾT QUẢ] Trả về danh sách nhiều dòng
        return $this->db->resultSet();
    }

    // =========================================================================
    // 6. THÊM TÀI KHOẢN MỚI (CREATE)
    // =========================================================================
    public function addUser($data) {
        // [QUERY] Thêm dòng mới vào bảng users
        // [ĐÃ SỬA] Xóa bỏ :active trong phần VALUES để khớp với số lượng bind
        $sql = "INSERT INTO users (username, password_hash, full_name, role_id) 
                VALUES (:user, :pass, :name, :role)";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền dữ liệu từ form vào
        $this->db->bind(':user', $data['username']);
        $this->db->bind(':pass', $data['password']); // Pass này đã hash ở Controller rồi
        $this->db->bind(':name', $data['full_name']);
        $this->db->bind(':role', $data['role_id']);
        
        // [EXECUTE] Chạy lệnh
        return $this->db->execute();
    }

    // =========================================================================
    // 7. CẬP NHẬT THÔNG TIN (UPDATE)
    // =========================================================================
    public function updateUser($data) {
        // Logic: Nếu người dùng nhập mật khẩu mới -> Cập nhật cả password
        if (!empty($data['password'])) {
            // [QUERY 1] Update kèm password
            $sql = "UPDATE users 
                    SET full_name = :name, 
                        role_id = :role, 
                        password_hash = :pass 
                    WHERE user_id = :id";
            $this->db->query($sql);
            $this->db->bind(':pass', $data['password']);
        } else {
            // [QUERY 2] Update giữ nguyên password cũ
            $sql = "UPDATE users 
                    SET full_name = :name, 
                        role_id = :role
                    WHERE user_id = :id";
            $this->db->query($sql);
        }

        // [BIND] Điền các thông tin chung
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':name', $data['full_name']);
        $this->db->bind(':role', $data['role_id']);

        // [EXECUTE] Chạy lệnh
        return $this->db->execute();
    }

    // =========================================================================
    // 8. XÓA MỀM (SOFT DELETE)
    // =========================================================================
    public function deleteUser($id) {
        // [QUERY] Đánh dấu xóa (is_deleted=1)
        // Để user này không thể đăng nhập được nữa ngay lập tức.
        $sql = "UPDATE users SET is_deleted = 1 WHERE user_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND]
        $this->db->bind(':id', $id);
        
        // [EXECUTE]
        return $this->db->execute();
    }

    // =========================================================================
    // 9. KHÔI PHỤC (RESTORE)
    // =========================================================================
    public function restoreUser($id) {
        // [QUERY] Bỏ đánh dấu xóa (is_deleted=0)
        $sql = "UPDATE users SET is_deleted = 0 WHERE user_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND]
        $this->db->bind(':id', $id);
        
        // [EXECUTE]
        return $this->db->execute();
    }
}