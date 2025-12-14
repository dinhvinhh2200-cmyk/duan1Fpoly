<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thống kê chuyên cần</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/sidebar.css">
</head>
<body>

<div class="wrapper">
    <?php require_once APPROOT . '/views/Layouts/sidebar.php'; ?>

    <div id="content">
        <nav class="navbar navbar-light bg-white shadow-sm px-3 mb-4">
            <button type="button" id="sidebarCollapse" class="btn btn-primary me-3"><i class="fas fa-bars"></i></button>
            <h4 class="text-primary mb-0 fw-bold">📋 THỐNG KÊ CHUYÊN CẦN (QR)</h4>
        </nav>

        <div class="container-fluid px-4">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
<form action="" method="GET" class="row g-3 align-items-end">
    <div class="col-md-2">
        <label class="form-label fw-bold text-secondary small">Từ ngày:</label>
        <input type="date" name="from" class="form-control" value="<?php echo $data['from_date']; ?>">
    </div>

    <div class="col-md-2">
        <label class="form-label fw-bold text-secondary small">Đến ngày:</label>
        <input type="date" name="to" class="form-control" value="<?php echo $data['to_date']; ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-bold text-secondary small">Tìm nhân viên:</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Nhập tên nhân viên..." value="<?php echo htmlspecialchars($data['search_keyword']); ?>">
        </div>
    </div>

    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100 fw-bold">
            <i class="fas fa-filter me-1"></i> Xem dữ liệu
        </button>
    </div>

    <div class="col-md-2">
        <a href="<?php echo URLROOT; ?>/AdminAttendance" class="btn btn-light border w-100" title="Đặt lại về tháng này">
            <i class="fas fa-sync-alt text-muted"></i> Đặt lại
        </a>
    </div>
</form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body"> <div class="d-flex justify-content-end mb-3 gap-2">
                        <button onclick="exportAttendanceExcel()" class="btn btn-success btn-sm text-white shadow-sm">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </button>
                        <button onclick="exportAttendanceCSV()" class="btn btn-info btn-sm text-white shadow-sm">
                            <i class="fas fa-file-csv me-1"></i> CSV
                        </button>
                        <button onclick="exportAttendancePDF()" class="btn btn-danger btn-sm text-white shadow-sm">
                            <i class="fas fa-file-pdf me-1"></i> PDF
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="exportTable" class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-primary">
                                <tr>
                                    <th>Ngày</th>
                                    <th class="text-start ps-5">Họ tên nhân viên</th>
                                    <th>Giờ vào</th>
                                    <th>Giờ ra</th>
                                    <th>Tổng thời gian</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($data['logs'])): ?>
                                    <?php foreach($data['logs'] as $log): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($log->created_at)); ?></td>
                                        
                                        <td class="text-start ps-5 fw-bold text-primary">
                                            <?php echo htmlspecialchars($log->staff_name); ?>
                                        </td>
                                        
                                        <td>
                                            <span class="badge bg-light text-dark border fs-6">
                                                <?php echo date('H:i', strtotime($log->check_in_time)); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if($log->check_out_time): ?>
                                                <span class="badge bg-light text-dark border fs-6">
                                                    <?php echo date('H:i', strtotime($log->check_out_time)); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">--:--</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php 
                                                if($log->check_in_time && $log->check_out_time) {
                                                    $in = strtotime($log->check_in_time);
                                                    $out = strtotime($log->check_out_time);
                                                    $diff = round(abs($out - $in) / 3600, 1); // Đổi ra giờ
                                                    echo "<strong class='text-success'>{$diff} giờ</strong>";
                                                } else {
                                                    echo "...";
                                                }
                                            ?>
                                        </td>

                                        <td>
                                            <?php if($log->check_out_time): ?>
                                                <span class="badge bg-success">Hoàn thành</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark blink">Đang làm việc</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="py-5 text-muted">Không có dữ liệu chấm công nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // --- [THÊM MỚI] Script xử lý xuất file ---
    
    // Hàm tạo tên file theo ngày giờ hiện tại
    function getAttendanceFileName(extension) {
        const today = new Date();
        // Lấy ngày hiện tại format YYYY-MM-DD
        const dateStr = today.toISOString().split('T')[0]; 
        return `Bao_cao_cham_cong_${dateStr}.${extension}`;
    }

    // 1. Xuất Excel
    function exportAttendanceExcel() {
        const table = document.getElementById("exportTable");
        // Tạo workbook từ bảng HTML
        const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
        // Tải file xuống
        XLSX.writeFile(wb, getAttendanceFileName('xlsx'));
    }

    // 2. Xuất CSV
    function exportAttendanceCSV() {
        const table = document.getElementById("exportTable");
        const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
        XLSX.writeFile(wb, getAttendanceFileName('csv'));
    }

    // 3. Xuất PDF
    function exportAttendancePDF() {
        const element = document.getElementById('exportTable');
        
        // Cấu hình giao diện PDF
        const opt = {
            margin:       10,
            filename:     getAttendanceFileName('pdf'),
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 }, // Tăng độ nét
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' } 
        };

        // Ẩn cột thao tác nếu có (trong bảng này không có cột thao tác nên không cần ẩn)
        // Tạo PDF và tải xuống
        html2pdf().set(opt).from(element).save();
    }
</script>
</body>
</html>