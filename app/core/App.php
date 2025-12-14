<?php
/*
 * APP CORE CLASS
 * Vai trò: Router & Dispatcher (Bộ định tuyến & Điều phối)
 * Giải thích: Đây là phần chú thích (Comment) đầu file để lập trình viên biết file này làm gì.
 * * Chức năng chính:
 * 1. Lấy URL từ thanh địa chỉ trình duyệt.
 * 2. Cắt URL đó ra theo cấu trúc: trang-web.com / [Controller] / [Method] / [Params]
 * Ví dụ: trang-web.com / Product / Edit / 5
 * 3. Tự động khởi tạo Controller (Product) và chạy hàm tương ứng (Edit) với tham số (5).
 */

class App {
    // ============================================================
    // PHẦN 1: KHAI BÁO CÁC THUỘC TÍNH (BIẾN) MẶC ĐỊNH
    // ============================================================

    // 'protected': Phạm vi truy cập. Nghĩa là biến này chỉ dùng được trong class này hoặc class con kế thừa nó.
    // $currentController: Biến chứa tên Controller đang được gọi.
    // Mặc định là 'AuthController' (Trang đăng nhập) nếu người dùng không gõ gì trên URL.
    protected $currentController = 'AuthController'; 

    // $currentMethod: Biến chứa tên hàm (hành động) cần chạy.
    // Mặc định là 'login'. Tức là nếu vào AuthController mà không nói làm gì, nó sẽ chạy hàm login.
    protected $currentMethod = 'login';              

    // $params: Biến mảng (array) chứa các tham số truyền vào (ví dụ: ID sản phẩm, trang số 2...).
    // Mặc định là mảng rỗng [] (không có tham số).
    protected $params = [];

    // ============================================================
    // PHẦN 2: HÀM KHỞI TẠO (CONSTRUCTOR) - CHẠY ĐẦU TIÊN
    // ============================================================
    
    // __construct(): Đây là "Hàm dựng". Nó TỰ ĐỘNG CHẠY ngay khi file index.php gọi lệnh "new App()".
    public function __construct() {
        
        // 1. LẤY VÀ CẮT URL
        // Gọi hàm getUrl() (được viết ở cuối file này) để lấy địa chỉ web người dùng đang gõ.
        // Kết quả $url sẽ là một mảng. Ví dụ: ['Product', 'Edit', '5']
        $url = $this->getUrl();

        // 2. XỬ LÝ CONTROLLER (Phần tử đầu tiên của URL - $url[0])
        // Kiểm tra xem $url[0] có tồn tại không (người dùng có gõ gì sau tên miền không?)
        if (isset($url[0])) {
            
            // ucwords(): Hàm làm viết hoa chữ cái đầu (vd: 'product' -> 'Product').
            // Để khớp với tên file Controller (thường viết hoa đầu).
            $u_name = ucwords($url[0]);
            
            // file_exists(): Hàm kiểm tra xem file có tồn tại trên ổ cứng máy tính không.
            // Kiểm tra theo đường dẫn: "../app/controllers/" + TênController + "Controller.php"
            // Ví dụ: Kiểm tra xem file "../app/controllers/ProductController.php" có thật không?
            if (file_exists('../app/controllers/' . $u_name . 'Controller.php')) {
                
                // Nếu file tồn tại, gán nó vào biến $currentController của hệ thống.
                $this->currentController = $u_name . 'Controller';
                
                // Reset lại method về mặc định là 'index'. 
                // Để tránh trường hợp giữ lại method 'login' của AuthController nếu chuyển trang.
                $this->currentMethod = 'index'; 
                
                // unset(): Xóa phần tử $url[0] khỏi mảng $url.
                // Vì ta đã xử lý xong phần Controller rồi, xóa đi để lát nữa xử lý phần sau dễ hơn.
                unset($url[0]);
            } 
            // Đây là đoạn code dự phòng (Fallback) cho các file Controller tên cũ (không có đuôi 'Controller')
            elseif (file_exists('../app/controllers/' . $u_name . '.php')) {
                $this->currentController = $u_name;
                $this->currentMethod = 'index';
                unset($url[0]);
            }
        }

        // require_once: Lệnh nạp file Controller vào bộ nhớ để sử dụng.
        // Lúc này PHP mới thực sự đọc nội dung file Controller đó.
        require_once '../app/controllers/' . $this->currentController . '.php';

        // new ... : Khởi tạo đối tượng (Instance) từ class Controller vừa nạp.
        // Ví dụ: $this->currentController = new ProductController();
        // Lúc này "người nhân viên" (Controller) đã sẵn sàng làm việc.
        $this->currentController = new $this->currentController;

        // 3. XỬ LÝ METHOD (Phần tử thứ hai của URL - $url[1])
        // Kiểm tra xem người dùng có nhập tên hàm không (vd: /edit)
        if (isset($url[1])) {
            
            // method_exists(): Kiểm tra xem trong Controller kia có cái hàm tên này không?
            // Ví dụ: Trong ProductController có hàm 'edit' không?
            if (method_exists($this->currentController, $url[1])) {
                
                // Nếu có, gán tên hàm đó vào biến $currentMethod.
                $this->currentMethod = $url[1];
                
                // Xóa phần tử $url[1] khỏi mảng $url vì đã xử lý xong.
                unset($url[1]);
            }
        }

        // 4. XỬ LÝ THAM SỐ (PARAMS) (Phần còn lại của URL)
        // array_values(): Vì nãy giờ ta đã unset [0] và [1], mảng có thể bị lủng lỗ.
        // Hàm này sắp xếp lại mảng để lấy tất cả những gì còn sót lại làm tham số.
        // Nếu $url rỗng (không còn gì), thì gán là mảng rỗng [].
        $this->params = $url ? array_values($url) : [];

        // 5. CHẠY CHƯƠNG TRÌNH (CALLBACK)
        // Đây là dòng code QUAN TRỌNG NHẤT.
        // call_user_func_array: Hàm đặc biệt của PHP để gọi một hàm trong một class với các tham số.
        // Dịch nôm na: "Hãy chạy hàm [$currentMethod] của đối tượng [$currentController] và đưa cho nó các [$params]"
        // Ví dụ thực tế: (new ProductController)->edit(5);
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    // ============================================================
    // PHẦN 3: HÀM HỖ TRỢ - XỬ LÝ URL
    // ============================================================
    
    // Hàm này giúp lấy URL sạch sẽ từ trình duyệt
    public function getUrl() {
        // Kiểm tra xem trên thanh địa chỉ có tham số 'url' không (do file .htaccess cấu hình)
        if (isset($_GET['url'])) {
            
            // rtrim(): Cắt bỏ dấu gạch chéo '/' thừa ở cuối đường dẫn (nếu có).
            // Ví dụ: "product/add/"  ---> "product/add"
            $url = rtrim($_GET['url'], '/');
            
            // filter_var(): Lọc bỏ các ký tự lạ, không an toàn khỏi URL (chống hack cơ bản).
            $url = filter_var($url, FILTER_SANITIZE_URL);
            
            // explode(): Cắt chuỗi thành mảng dựa vào dấu gạch chéo '/'.
            // Ví dụ: "Product/Edit/5" ---> Thành mảng ['Product', 'Edit', '5']
            return explode('/', $url);
        }
        // Nếu không có URL nào, trả về mảng rỗng.
        return [];
    }
}