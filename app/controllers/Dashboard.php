<?php
//Vai trò: Controller này là trung tâm chỉ huy của trang Báo cáo (Dashboard) dành cho Admin.

//Nhiệm vụ chính:

//Nhận bộ lọc: Lấy ngày bắt đầu, ngày kết thúc, loại báo cáo (ngày/tháng/năm) từ URL.

//Tổng hợp số liệu: Gọi các Model để lấy doanh thu, số đơn hàng, top sản phẩm.

//Xử lý dữ liệu biểu đồ: Chuẩn bị dữ liệu để vẽ biểu đồ đường (Line Chart) và biểu đồ tròn (Pie Chart).

//Phân trang: Chia danh sách đơn hàng thành nhiều trang.

//API: Cung cấp dữ liệu chi tiết đơn hàng (JSON) cho popup xem nhanh.
// Định nghĩa class Dashboard.
// 'extends Controller': Kế thừa các tính năng cơ bản (load model, view, bảo mật) từ Controller cha.
class Dashboard extends Controller {
    
    // Khai báo 2 thuộc tính (biến) để chứa các Model sẽ dùng.
    // 'private': Chỉ dùng nội bộ trong class này.
    private $dashboardModel; // Xử lý thống kê (doanh thu, biểu đồ).
    private $orderModel;     // Xử lý dữ liệu đơn hàng (danh sách, chi tiết).

    // --- HÀM KHỞI TẠO (Constructor) ---
    // Chạy ngay lập tức khi vào trang Dashboard.
    public function __construct() {
        // 1. BẢO MẬT: Gọi hàm kiểm tra quyền Admin từ class cha.
        // Nếu là nhân viên thường -> Đá về trang Login hoặc POS.
        $this->restrictToAdmin();

        // 2. LOAD MODEL: Nạp các file Model vào để sử dụng.
        // $this->model('...') là hàm của Controller cha.
        $this->dashboardModel = $this->model('DashboardModel');
        $this->orderModel = $this->model('OrderModel');
    }

    // --- HÀM CHÍNH (Hiển thị trang Dashboard) ---
    public function index() {
        // ============================================================
        // 1. LẤY THAM SỐ TỪ URL (Bộ lọc & Tìm kiếm)
        // ============================================================
        
        // isset($_GET['from']): Kiểm tra xem trên thanh địa chỉ có tham số 'from' không? (ví dụ: ?from=2023-11-01)
        // Dùng toán tử 3 ngôi (Điều kiện ? Đúng : Sai).
        // Nếu có -> Lấy giá trị đó.
        // Nếu không -> Lấy ngày đầu tháng hiện tại (date('Y-m-01')).
        $fromDate = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01'); 
        
        // Tương tự, nếu không có ngày kết thúc -> Lấy ngày hôm nay (date('Y-m-d')).
        $toDate = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
        
        // Lấy từ khóa tìm kiếm, dùng trim() để cắt khoảng trắng thừa.
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Lấy loại biểu đồ (xem theo ngày, tháng hay năm). Mặc định là 'day'.
        $filterType = isset($_GET['type']) ? $_GET['type'] : 'day'; 

        // ============================================================
        // 2. XỬ LÝ PHÂN TRANG (PAGINATION)
        // ============================================================
        
        // Lấy trang hiện tại. Ép kiểu (int) để đảm bảo nó là số nguyên.
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // Nếu người dùng nhập số trang < 1 -> Gán lại bằng 1.
        if ($page < 1) $page = 1;
        
        $limit = 10; // Quy định: Mỗi trang hiện 10 đơn hàng.
        
        // Tính vị trí bắt đầu lấy dữ liệu trong Database (OFFSET).
        // Công thức: (Trang hiện tại - 1) * Số lượng mỗi trang.
        // Ví dụ: Trang 1 -> offset 0. Trang 2 -> offset 10.
        $offset = ($page - 1) * $limit;

        // Gọi Model đếm tổng số đơn hàng thỏa mãn điều kiện lọc (để tính tổng số trang).
        $totalOrders = $this->orderModel->countOrders($fromDate, $toDate, $search);
        
        // Tính tổng số trang: ceil() là hàm làm tròn lên (ví dụ 1.2 -> 2 trang).
        $totalPages = ceil($totalOrders / $limit);
        
        // Gọi Model lấy danh sách đơn hàng cho trang hiện tại.
        $allOrders = $this->orderModel->getAllOrders($fromDate, $toDate, $limit, $offset, $search);

        // ============================================================
        // 3. THỐNG KÊ TỔNG QUAN (4 Ô THẺ - CARDS)
        // ============================================================
        
        // Gọi các hàm trong DashboardModel để lấy số liệu:
        $revenueToday = $this->dashboardModel->getRevenueToday();       // Doanh thu hôm nay
        $ordersToday  = $this->dashboardModel->getOrdersCountToday();   // Số đơn hôm nay
        $revenueMonth = $this->dashboardModel->getRevenueThisMonth();   // Doanh thu tháng này
        $topProducts  = $this->dashboardModel->getTopProductsByDateRange($fromDate, $toDate); // Top món bán chạy (Biểu đồ tròn)

        // ============================================================
        // 4. XỬ LÝ BIỂU ĐỒ DOANH THU (BIỂU ĐỒ ĐƯỜNG)
        // ============================================================
        
        // Lấy dữ liệu thô từ Database (Các ngày có phát sinh doanh thu).
        $rawChartData = $this->dashboardModel->getRevenueChartData($fromDate, $toDate, $filterType);
        
        // --- CHUẨN BỊ KHUNG DỮ LIỆU ĐẦY ĐỦ ---
        // Vấn đề: Database chỉ trả về ngày có bán hàng. Ngày không bán được gì sẽ bị thiếu.
        // Giải pháp: Tạo một vòng lặp từ ngày bắt đầu đến ngày kết thúc để điền số 0 vào những ngày trống.
        
        $chartData = []; // Mảng chứa dữ liệu cuối cùng.
        $start = new DateTime($fromDate); // Tạo đối tượng thời gian bắt đầu.
        $end = new DateTime($toDate);     // Tạo đối tượng thời gian kết thúc.
        
        // Cấu hình bước nhảy thời gian (Interval) tùy theo loại lọc.
        if ($filterType == 'month') {
            // Xem theo THÁNG
            $interval = new DateInterval('P1M'); // P1M: Period 1 Month (Mỗi bước nhảy 1 tháng).
            $end->modify('first day of next month'); // Kéo dài ngày kết thúc thêm 1 chút để vòng lặp bao gồm cả tháng cuối.
            $formatKey = 'Y-m'; // Định dạng khóa để so sánh (VD: 2023-11).
            $formatLabel = 'm/Y'; // Định dạng hiển thị trên biểu đồ (VD: 11/2023).
        } elseif ($filterType == 'year') {
            // Xem theo NĂM
            $interval = new DateInterval('P1Y'); // P1Y: Period 1 Year.
            $end->modify('+1 year');
            $formatKey = 'Y';
            $formatLabel = 'Y';
        } else {
            // Xem theo NGÀY (Mặc định)
            $interval = new DateInterval('P1D'); // P1D: Period 1 Day.
            $end->modify('+1 day'); // Cộng thêm 1 ngày vào ngày kết thúc để vòng lặp chạy đủ.
            $formatKey = 'Y-m-d';
            $formatLabel = 'd/m';
        }

        // --- TẠO KHUNG XƯƠNG (SKELETON) ---
        // DatePeriod: Tạo một tập hợp các mốc thời gian từ Start đến End với bước nhảy Interval.
        $period = new DatePeriod($start, $interval, $end);
        
        // Duyệt qua từng mốc thời gian và gán doanh thu bằng 0 trước.
        foreach ($period as $dt) {
            $chartData[$dt->format($formatKey)] = 0; 
            // Ví dụ: chartData['2023-11-25'] = 0;
        }

        // --- ĐIỀN DỮ LIỆU THẬT ---
        // Duyệt qua dữ liệu lấy từ Database ($rawChartData).
        foreach ($rawChartData as $row) {
            // Nếu ngày trong DB có tồn tại trong khung xương của chúng ta...
            if (isset($chartData[$row->date_label])) {
                // ...thì ghi đè số 0 bằng doanh thu thực tế.
                $chartData[$row->date_label] = (int)$row->total;
            }
        }

        // --- TÁCH DỮ LIỆU CHO CHART.JS ---
        // Thư viện vẽ biểu đồ cần 2 mảng riêng biệt: Nhãn (Labels) và Giá trị (Values).
        $chartLabels = []; 
        $chartValues = [];
        
        // Duyệt qua mảng $chartData đã hoàn thiện.
        foreach ($chartData as $key => $val) {
            // Tạo đối tượng ngày từ cái key (VD: '2023-11-25') để format lại cho đẹp.
            $dateObj = DateTime::createFromFormat($formatKey, $key);
            
            // Format lại nhãn hiển thị tùy theo loại lọc.
            if ($filterType == 'month') {
                $chartLabels[] = "Thg " . $dateObj->format('m/Y'); // VD: Thg 11/2023
            } elseif ($filterType == 'year') {
                $chartLabels[] = "Năm " . $dateObj->format('Y'); // VD: Năm 2023
            } else {
                $chartLabels[] = $dateObj->format('d/m'); // VD: 25/11
            }
            
            $chartValues[] = $val; // Đưa doanh thu vào mảng giá trị.
        }

        // ============================================================
        // 5. ĐÓNG GÓI DỮ LIỆU (PACKING DATA)
        // ============================================================
        
        // Tạo mảng $data chứa tất cả mọi thứ View cần dùng.
        $data = [
            'revenue_today' => $revenueToday,
            'orders_today'  => $ordersToday,
            'revenue_month' => $revenueMonth,
            'orders'        => $allOrders,      // Danh sách đơn hàng
            'top_products'  => $topProducts,    // Top 5 món bán chạy
            'chart_labels'  => $chartLabels,    // Nhãn biểu đồ (Trục hoành)
            'chart_values'  => $chartValues,    // Giá trị biểu đồ (Trục tung)
            'from_date'     => $fromDate,       // Để giữ lại giá trị trên ô input
            'to_date'       => $toDate,
            'filter_type'   => $filterType,     // Để active đúng option trong dropdown
            'current_page'  => $page,           // Trang hiện tại
            'total_pages'   => $totalPages,     // Tổng số trang
            'search_keyword'=> $search          // Từ khóa tìm kiếm cũ
        ];

        // Gọi View hiển thị giao diện.
        $this->view('admin/dashboard/index', $data);
    }

    // ============================================================
    // 6. API AJAX: LẤY CHI TIẾT ĐƠN HÀNG
    // ============================================================
    // Hàm này được gọi bởi JavaScript (fetch API) khi bấm nút "Mắt".
    // Trả về dữ liệu dạng JSON chứ không phải giao diện HTML.
    public function get_order_detail($id) {
        // Kiểm tra xem Model đã được nạp chưa (đề phòng lỗi).
        if (!isset($this->orderModel)) {
            $this->orderModel = $this->model('OrderModel');
        }
        
        // Gọi Model lấy thông tin chi tiết đơn hàng (Info + Items).
        $data = $this->orderModel->getOrderDetail($id);
        
        // Đặt header báo cho trình duyệt biết đây là dữ liệu JSON.
        header('Content-Type: application/json');
        
        // json_encode: Biến mảng PHP thành chuỗi JSON để JS đọc được.
        echo json_encode($data);
        
        // exit: Dừng chương trình ngay lập tức, không cho chạy thêm gì nữa (để JSON sạch).
        exit;
    }
}