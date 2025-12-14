<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Bàn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/pos.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/sidebar.css">
</head>
<body class="dashboard-page">

<div class="wrapper dashboard-wrapper">
    <?php require_once APPROOT . '/views/Layouts/sidebar.php'; ?>

    <div id="content">
        <nav class="navbar navbar-light bg-white shadow-sm px-3 mb-4 sticky-top">
            <div class="d-flex align-items-center w-100">
                <button type="button" id="sidebarCollapse" class="btn btn-primary me-3"><i class="fas fa-bars"></i></button>
                <h4 class="text-primary mb-0 fw-bold">QUẢN LÝ BÀN</h4>
            </div>
        </nav>

        <div class="container-fluid px-4 pb-5">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <form action="<?php echo URLROOT; ?>/table/add" method="POST">
                        <button type="submit" class="btn btn-success fw-bold shadow-sm">
                            <i class="fas fa-plus-circle me-2"></i> THÊM BÀN MỚI
                        </button>
                    </form>

                    <div class="input-group w-auto">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchTable" class="form-control border-start-0 ps-0" placeholder="Tìm tên bàn...">
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 text-start">Tên bàn</th>
                                    <th>Trạng thái</th>
                                    <th class="pe-4 text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody id="tableTableBody">
                                <?php if (!empty($data['tables'])): ?>
                                    <?php foreach($data['tables'] as $table): ?>
                                    
                                    <tr class="<?php echo ($table->is_deleted == 1) ? 'table-secondary text-muted' : ''; ?>">
                                        
                                        <td class="ps-4 text-start fw-bold fs-5">
                                            <?php echo htmlspecialchars($table->table_name); ?>
                                            <?php if($table->is_deleted == 1): ?>
                                                <span class="badge bg-danger ms-2" style="font-size: 0.6rem;">Đã xóa</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <?php if($table->is_deleted == 1): ?>
                                                <span class="text-muted small">--</span>
                                            <?php else: ?>
                                                <?php if($table->status == 'empty'): ?>
                                                    <span class="badge bg-success-subtle text-success rounded-pill px-3">Trống</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-3">Có khách</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="pe-4 text-end">
                                            <?php if($table->is_deleted == 1): ?>
                                                <a href="<?php echo URLROOT; ?>/table/restore/<?php echo $table->table_id; ?>" 
                                                   class="btn btn-sm btn-success fw-bold btn-restore text-white shadow-sm">
                                                    <i class="fas fa-trash-restore me-1"></i> Khôi phục
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-danger fw-bold btn-delete shadow-sm" 
                                                        data-id="<?php echo $table->table_id; ?>"
                                                        data-name="<?php echo htmlspecialchars($table->table_name); ?>">
                                                    <i class="fas fa-trash-alt me-1"></i> Xóa
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">Chưa có bàn nào. Hãy bấm "Thêm bàn mới".</td>
                                    </tr>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>const URLROOT = '<?php echo URLROOT; ?>';</script>
<script src="<?php echo URLROOT; ?>/js/table.js"></script>

<?php if(isset($_SESSION['msg_type']) && isset($_SESSION['msg_text'])): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $_SESSION['msg_type']; ?>',
            title: 'Thông báo',
            text: '<?php echo $_SESSION['msg_text']; ?>',
            confirmButtonColor: '#1cc88a',
            timer: 1500
        });
    </script>
    <?php unset($_SESSION['msg_type'], $_SESSION['msg_text']); ?>
<?php endif; ?>

</body>
</html>