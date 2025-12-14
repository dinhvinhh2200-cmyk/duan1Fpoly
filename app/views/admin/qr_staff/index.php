<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách nhân viên QR</title>
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
            <h4 class="text-primary mb-0 fw-bold">📋 DANH SÁCH NHÂN VIÊN (CHECK-IN)</h4>
        </nav>

        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold text-primary">
                            <i class="fas fa-user-plus me-1"></i> Thêm nhân viên mới
                        </div>
                        <div class="card-body">
                            <form action="<?php echo URLROOT; ?>/QrStaff/add" method="post">
                                <div class="mb-3">
                                    <label class="form-label">Họ và Tên đầy đủ</label>
                                    <input type="text" name="full_name" class="form-control" placeholder="VD: Nguyễn Văn A" required>
                                    <small class="text-muted">Nhân viên phải nhập chính xác tên này để chấm công.</small>
                                </div>
                                <button type="submit" class="btn btn-success w-100 fw-bold">Lưu lại</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold">
                            <i class="fas fa-list me-1"></i> Danh sách hiện có
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Họ và Tên</th>
                                        <th class="text-end pe-4">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($data['staff_list'])): ?>
                                        <?php foreach($data['staff_list'] as $staff): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($staff->full_name); ?></td>
                                            <td class="text-end pe-4">
                                                <a href="<?php echo URLROOT; ?>/QrStaff/delete/<?php echo $staff->id; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Xóa nhân viên này? Họ sẽ không thể chấm công nữa.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center py-3 text-muted">Chưa có nhân viên nào.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarCollapse').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });
</script>
</body>
</html>