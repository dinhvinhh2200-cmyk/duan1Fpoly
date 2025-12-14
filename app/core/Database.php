<?php
/*
 * DATABASE CORE CLASS
 * Vai trò: Lớp bao đóng (Wrapper) cho PDO để kết nối MySQL dễ dàng và an toàn hơn.
 */
class Database {
    // ============================================================
    // PHẦN 1: KHAI BÁO THÔNG TIN KẾT NỐI
    // ============================================================
    
    // Lấy các hằng số từ file config/config.php
    private $host = DB_HOST; // Địa chỉ máy chủ (localhost)
    private $user = DB_USER; // Tên đăng nhập (root)
    private $pass = DB_PASS; // Mật khẩu (rỗng)
    private $dbname = DB_NAME; // Tên cơ sở dữ liệu (f_coffee)

    // Các biến kỹ thuật để giữ kết nối
    private $dbh;  // Database Handler (Cái ống nước nối vào DB)
    private $stmt; // Statement (Câu lệnh SQL đang chuẩn bị chạy)
    private $error; // Chứa lỗi nếu kết nối thất bại

    // ============================================================
    // PHẦN 2: KẾT NỐI NGAY KHI KHỞI TẠO
    // ============================================================

    public function __construct() {
        // Tạo chuỗi DSN (Data Source Name) - địa chỉ nhà của DB
        // Dạng: mysql:host=localhost;dbname=f_coffee;charset=utf8
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8';
        
        // Cấu hình tùy chọn cho PDO
        $options = array(
            PDO::ATTR_PERSISTENT => true, // Giữ kết nối liên tục để chạy nhanh hơn
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Nếu lỗi thì báo Exception (dễ bắt lỗi)
        );

        // Bắt đầu thử kết nối (Try - Catch)
        try {
            // Tạo kết nối PDO mới
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
            
            // Cài đặt múi giờ cho MySQL khớp với Việt Nam (+07:00)
            // Để hàm NOW() trong SQL trả về đúng giờ VN.
            $this->dbh->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            // Nếu kết nối thất bại (sai pass, sai tên DB...)
            $this->error = $e->getMessage();
            die("Lỗi kết nối Cơ sở dữ liệu: " . $this->error);
        }
    }

    // ============================================================
    // PHẦN 3: CÁC HÀM THAO TÁC DỮ LIỆU (CRUD)
    // ============================================================

    // 1. Chuẩn bị câu lệnh (Prepare)
    // Thay vì chạy ngay, ta "soạn thảo" trước. 
    // Ví dụ: "SELECT * FROM users WHERE id = :id"
    // :id là một cái "ghế giữ chỗ" (placeholder), chưa có giá trị thật.
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
    }

    // 2. Gán giá trị (Bind)
    // Đây là bước điền giá trị thật vào cái "ghế giữ chỗ" ở trên.
    // Việc này cực kỳ quan trọng để chống hack SQL Injection.
    // $param: Tên ghế (VD: :id)
    // $value: Giá trị thật (VD: 5)
    // $type: Kiểu dữ liệu (Số, Chuỗi...)
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            // Tự động đoán kiểu dữ liệu nếu không truyền vào
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT; // Là số nguyên
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL; // Là đúng/sai
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL; // Là rỗng
                    break;
                default:
                    $type = PDO::PARAM_STR; // Mặc định là chuỗi văn bản
            }
        }
        // Gắn giá trị vào câu lệnh đã chuẩn bị
        $this->stmt->bindValue($param, $value, $type);
    }

    // 3. Thực thi (Execute)
    // Sau khi chuẩn bị và gán giá trị, hàm này bấm nút "CHẠY".
    // Thường dùng cho các lệnh INSERT, UPDATE, DELETE (không cần trả về dữ liệu).
    public function execute() {
        return $this->stmt->execute();
    }

    // 4. Lấy danh sách nhiều dòng (ResultSet)
    // Dùng cho lệnh SELECT lấy danh sách (VD: Danh sách tất cả món ăn).
    // Trả về: Một mảng chứa nhiều Object.
    public function resultSet() {
        $this->execute(); // Chạy lệnh
        return $this->stmt->fetchAll(PDO::FETCH_OBJ); // Lấy tất cả kết quả dạng Object
    }

    // 5. Lấy một dòng duy nhất (Single)
    // Dùng cho lệnh SELECT lấy 1 người, 1 món ăn cụ thể.
    // Trả về: Một Object duy nhất.
    public function single() {
        $this->execute(); // Chạy lệnh
        return $this->stmt->fetch(PDO::FETCH_OBJ); // Lấy dòng đầu tiên tìm thấy
    }

    // 6. Đếm số dòng bị ảnh hưởng (RowCount)
    // Dùng để kiểm tra: Có bao nhiêu dòng bị xóa? Có bao nhiêu dòng tìm thấy?
    public function rowCount() {
        return $this->stmt->rowCount();
    }
}