<?php
/*
 * PRODUCT MODEL
 * Vai trò: Quản lý Món ăn / Đồ uống (Sản phẩm)
 */
class ProductModel {
    // Khai báo biến $db để chứa đối tượng kết nối Database
    private $db;

    // --- HÀM KHỞI TẠO (Chạy đầu tiên khi gọi Model này) ---
    public function __construct() {
        // Khởi tạo class Database (file app/core/Database.php)
        // Lúc này biến $this->db sẽ nắm giữ "chìa khóa" kết nối MySQL.
        $this->db = new Database();
    }

    // =========================================================================
    // 1. LẤY DANH SÁCH SẢN PHẨM (READ)
    // =========================================================================
    public function getProducts() {
        // [QUERY] Soạn thảo câu lệnh SQL:
        // - Lấy tất cả cột bảng products (p.*) và tên danh mục (c.category_name).
        // - JOIN: Nối bảng products với bảng categories dựa trên mã danh mục.
        // - WHERE: Chỉ lấy những dòng có is_deleted = 0 (chưa bị xóa).
        // - ORDER BY: Sắp xếp sản phẩm mới nhất lên đầu (product_id giảm dần).
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE p.is_deleted = 0 
                ORDER BY p.product_id DESC";
        
        // [GỬI LỆNH] Đưa câu SQL trên vào bộ chuẩn bị của Database.
        $this->db->query($sql);
        
        // [LẤY KẾT QUẢ] Thực thi và trả về danh sách nhiều dòng (Mảng các Object).
        return $this->db->resultSet();
    }

    // =========================================================================
    // 2. LẤY DANH SÁCH TOPPING (READ - ĐẶC BIỆT)
    // =========================================================================
    public function getToppings() {
        // [QUERY] Soạn thảo SQL:
        // - Lấy sản phẩm (p.*)
        // - Điều kiện 1: Tên danh mục chứa chữ "Topping" (LIKE '%Topping%').
        // - Điều kiện 2: Chưa bị xóa (is_deleted = 0).
        // [ĐÃ SỬA] Xóa điều kiện is_available = 1
        $sql = "SELECT p.* FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                WHERE c.category_name LIKE '%Topping%' 
                AND p.is_deleted = 0";
        
        // [GỬI LỆNH] Chuẩn bị câu lệnh.
        $this->db->query($sql);
        
        // [LẤY KẾT QUẢ] Trả về danh sách các loại topping tìm được.
        return $this->db->resultSet();
    }

    // =========================================================================
    // 3. KIỂM TRA TRÙNG TÊN (VALIDATION)
    // =========================================================================
    // $name: Tên món cần kiểm tra.
    // $excludeId: ID cần loại trừ (dùng khi đang Sửa món chính nó).
    public function checkNameExists($name, $excludeId = null) {
        // [QUERY] Soạn thảo SQL cơ bản: Tìm ID món có tên trùng (kể cả đã xóa).
        // [QUAN TRỌNG] Bỏ điều kiện is_deleted = 0 để chặn trùng tên với cả món trong thùng rác.
        $sql = "SELECT product_id FROM products WHERE product_name = :name";
        
        // Logic phụ: Nếu có $excludeId (tức là đang Sửa), thêm đoạn "trừ chính nó ra".
        if ($excludeId) {
            $sql .= " AND product_id != :id";
        }
        
        // [GỬI LỆNH] Chuẩn bị câu lệnh hoàn chỉnh.
        $this->db->query($sql);
        
        // [BIND] Điền giá trị thật vào chỗ trống :name.
        $this->db->bind(':name', $name);
        
        // [BIND] Nếu có excludeId, điền nốt vào chỗ trống :id.
        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }
        
        // [THỰC THI] Chạy lệnh lấy 1 dòng kết quả (single) để kiểm tra.
        $this->db->single();
        
        // [KẾT QUẢ] Đếm số dòng tìm thấy. 
        // Nếu > 0 nghĩa là CÓ trùng tên -> Trả về true.
        return $this->db->rowCount() > 0;
    }

    // =========================================================================
    // 4. THÊM MÓN MỚI (CREATE)
    // =========================================================================
    public function addProduct($data) {
        // [QUERY] Soạn thảo lệnh INSERT.
        // [ĐÃ SỬA] Xóa is_available và :avail
        $sql = "INSERT INTO products (product_name, category_id, price, image) 
                VALUES (:name, :cat_id, :price, :image)";
        
        // [GỬI LỆNH] Chuẩn bị.
        $this->db->query($sql);

        // [BIND] Điền dữ liệu thật từ mảng $data vào các chỗ trống tương ứng.
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':cat_id', $data['category_id']);
        $this->db->bind(':price', $data['price']);
        $this->db->bind(':image', $data['image']);
        // [QUAN TRỌNG] Không còn bind :avail nữa

        // [EXECUTE] Bấm nút chạy lệnh INSERT.
        // Trả về true nếu thêm thành công, false nếu lỗi.
        return $this->db->execute();
    }

    // =========================================================================
    // 5. CẬP NHẬT MÓN (UPDATE) - [ĐÃ SỬA LỖI]
    // =========================================================================
    public function updateProduct($data) {
        // Logic: Kiểm tra xem người dùng có upload ảnh mới không?
        if (!empty($data['image'])) {
            // [QUERY 1] Nếu có ảnh mới: Cập nhật cả cột 'image'.
            // [ĐÃ SỬA] Xóa đoạn "is_available = :avail" khỏi câu lệnh SQL
            $sql = "UPDATE products 
                    SET product_name = :name, 
                        category_id = :cat_id, 
                        price = :price, 
                        image = :image
                    WHERE product_id = :id";
        } else {
            // [QUERY 2] Nếu không có ảnh: Giữ nguyên ảnh cũ (bỏ cột image ra khỏi SQL).
            // [ĐÃ SỬA] Xóa đoạn "is_available = :avail" khỏi câu lệnh SQL
            $sql = "UPDATE products 
                    SET product_name = :name, 
                        category_id = :cat_id, 
                        price = :price
                    WHERE product_id = :id";
        }

        // [GỬI LỆNH] Chuẩn bị câu lệnh đã chọn ở trên.
        $this->db->query($sql);
        
        // [BIND] Điền dữ liệu chung.
        $this->db->bind(':id', $data['id']); // Quan trọng: WHERE product_id = :id
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':cat_id', $data['category_id']);
        $this->db->bind(':price', $data['price']);
        // [QUAN TRỌNG] Không bind :avail ở đây nữa

        // [BIND] Điền dữ liệu ảnh (chỉ khi có ảnh mới).
        if (!empty($data['image'])) {
            $this->db->bind(':image', $data['image']);
        }

        // [EXECUTE] Chạy lệnh UPDATE.
        return $this->db->execute();
    }

    // =========================================================================
    // 6. XÓA MỀM (SOFT DELETE)
    // =========================================================================
    public function deleteProduct($id) {
        // [QUERY] Thay vì xóa vĩnh viễn (DELETE FROM), ta chỉ sửa trạng thái is_deleted thành 1.
        $sql = "UPDATE products SET is_deleted = 1 WHERE product_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền ID món cần xóa.
        $this->db->bind(':id', $id);

        // [EXECUTE] Chạy lệnh UPDATE.
        return $this->db->execute();
    }
    
    // =========================================================================
    // 7. LẤY CHI TIẾT 1 MÓN (READ SINGLE)
    // =========================================================================
    public function getProductById($id) {
        // [QUERY] Lấy tất cả thông tin của món có ID cụ thể.
        $sql = "SELECT * FROM products WHERE product_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền ID.
        $this->db->bind(':id', $id);
        
        // [LẤY KẾT QUẢ] Chạy và trả về đúng 1 dòng dữ liệu (Object).
        return $this->db->single();
    }

    // =========================================================================
    // 8. LẤY TẤT CẢ BAO GỒM ĐÃ XÓA (ADMIN VIEW)
    // =========================================================================
    public function getAllProductsIncludingDeleted() {
        // [QUERY] Lấy hết, sắp xếp món chưa xóa lên trên, món mới lên trên.
        $sql = "SELECT p.*, c.category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.category_id 
                ORDER BY p.is_deleted ASC, p.product_id DESC";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [LẤY KẾT QUẢ] Trả về danh sách đầy đủ.
        return $this->db->resultSet();
    }

    // =========================================================================
    // 9. KHÔI PHỤC MÓN (RESTORE)
    // =========================================================================
    public function restoreProduct($id) {
        // [QUERY] Sửa lại is_deleted = 0 (Hồi sinh).
        // [ĐÃ SỬA] Xóa đoạn "is_available = 0"
        $sql = "UPDATE products SET is_deleted = 0 WHERE product_id = :id";
        
        // [GỬI LỆNH]
        $this->db->query($sql);
        
        // [BIND] Điền ID.
        $this->db->bind(':id', $id);

        // [EXECUTE] Chạy lệnh UPDATE.
        return $this->db->execute();
    }
}