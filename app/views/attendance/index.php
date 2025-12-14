<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chấm Công - ForTime Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #e0e5ec; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .attendance-card { width: 100%; max-width: 400px; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #6f4e37, #5a3e2b); color: white; padding: 30px 20px; text-align: center; }
        .btn-checkin { background-color: #1cc88a; color: white; height: 60px; font-size: 1.2rem; font-weight: bold; border: none; transition: 0.3s; }
        .btn-checkin:hover { background-color: #169b6b; transform: translateY(-2px); }
        .btn-checkout { background-color: #e74a3b; color: white; height: 60px; font-size: 1.2rem; font-weight: bold; border: none; transition: 0.3s; }
        .btn-checkout:hover { background-color: #be2617; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="container px-3">
    <div class="card attendance-card mx-auto">
        <div class="card-header">
            <i class="fas fa-mug-hot fa-3x mb-2"></i>
            <h4 class="m-0 fw-bold">FORTIME COFFEE</h4>
            <small class="opacity-75">Hệ thống chấm công nhân viên</small>
        </div>
        
        <div class="card-body p-4">
            
            <?php if(!empty($data['message'])): ?>
                <div class="alert alert-<?php echo ($data['message_type'] == 'success') ? 'success' : 'danger'; ?> text-center mb-4 shadow-sm" role="alert">
                    <?php echo $data['message']; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="attendanceForm">
                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary"><i class="fas fa-user-tag me-2"></i>Họ và Tên của bạn:</label>
                    <input type="text" name="staff_name" id="staff_name" 
                           class="form-control form-control-lg bg-light" 
                           placeholder="Ví dụ: Nguyễn Văn A..." required autocomplete="off">
                </div>

                <div class="d-grid gap-3">
                    <button type="button" onclick="confirmAction('checkin')" class="btn btn-checkin rounded-pill shadow-sm">
                        <i class="fas fa-sign-in-alt me-2"></i> VÀO CA
                    </button>
                    
                    <button type="button" onclick="confirmAction('checkout')" class="btn btn-checkout rounded-pill shadow-sm">
                        <i class="fas fa-sign-out-alt me-2"></i> KẾT CA
                    </button>
                </div>
                
                <input type="hidden" name="action" id="action_input">
            </form>
        </div>
        <div class="card-footer bg-white text-center py-3 border-0">
            <small class="text-muted">Chúc bạn một ngày làm việc vui vẻ!</small>
        </div>
    </div>
</div>

<script>
    // 1. Tự động điền tên cũ (như cũ)
    document.addEventListener("DOMContentLoaded", function() {
        var savedName = localStorage.getItem("my_staff_name_v2");
        if (savedName) {
            document.getElementById("staff_name").value = savedName;
        }
    });

    // 2. [MỚI] Hàm xử lý Popup xác nhận
    function confirmAction(type) {
        // Lấy tên nhân viên
        var name = document.getElementById("staff_name").value.trim();
        
        // Kiểm tra nếu chưa nhập tên
        if (!name) {
            Swal.fire({
                icon: 'error',
                title: 'Thiếu thông tin!',
                text: 'Vui lòng nhập đầy đủ Họ và Tên của bạn.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        // Xác định nội dung thông báo
        var titleText = (type === 'checkin') ? 'XÁC NHẬN VÀO CA?' : 'XÁC NHẬN KẾT CA?';
        var btnColor = (type === 'checkin') ? '#1cc88a' : '#e74a3b';

        // Hiện Popup SweetAlert2
        Swal.fire({
            title: titleText,
            html: `Bạn đang điểm danh với tên:<br><b style="font-size: 1.5rem; color: #4e73df;">${name}</b><br><br><span style="color:red">⚠️ Vui lòng kiểm tra kỹ chính tả trước khi bấm xác nhận!</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#858796',
            confirmButtonText: 'Đúng, tôi là ' + name,
            cancelButtonText: 'Kiểm tra lại'
        }).then((result) => {
            if (result.isConfirmed) {
                // Lưu tên vào bộ nhớ
                localStorage.setItem("my_staff_name_v2", name);
                
                // Gán action vào input ẩn và gửi form
                document.getElementById('action_input').value = type;
                document.getElementById('attendanceForm').submit();
            }
        });
    }
</script>

</body>
</html>