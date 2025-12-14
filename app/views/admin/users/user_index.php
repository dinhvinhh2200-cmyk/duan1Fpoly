<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/pos.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/admin.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/sidebar.css">
</head>
<body>

<div class="wrapper">
    <?php require_once APPROOT . '/views/Layouts/sidebar.php'; ?>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3 mb-3">
            <button type="button" id="sidebarCollapse" class="btn btn-primary">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand mb-0 h1 fw-bold text-primary ms-3">👥 QUẢN LÝ NHÂN SỰ</span>
        </nav>

        <div class="container-fluid px-4">
            <div class="row g-3">
                
                <div class="col-md-4">
                    <div class="card-box shadow-sm p-3 bg-white rounded">
                        <h5 class="text-primary mb-4 border-bottom pb-2">Thông tin tài khoản</h5>
                        <form id="userForm" method="post">
                            <div class="mb-3">
                                <label class="form-label">ID</label>
                                <input type="text" name="user_id" id="user_id" class="form-control bg-light" readonly placeholder="Tự động">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Tên đăng nhập</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                                
                                <?php if(isset($_SESSION['error_username'])): ?>
                                    <div class="text-danger small mt-1 fw-bold">
                                        <i class="fas fa-exclamation-triangle me-1"></i> 
                                        <?php 
                                            echo $_SESSION['error_username']; 
                                            unset($_SESSION['error_username']); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3" id="div-password">
                                <label class="form-label" id="lbl-password">Mật khẩu <span class="text-danger" id="req-pass">*</span></label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Nhập mật khẩu...">
                                
                                <?php if(isset($_SESSION['error_password'])): ?>
                                    <div class="text-danger small mt-1 fw-bold">
                                        <i class="fas fa-exclamation-triangle me-1"></i> 
                                        <?php 
                                            echo $_SESSION['error_password']; 
                                            unset($_SESSION['error_password']); 
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <small class="text-muted" id="pass_hint">Bắt buộc khi tạo mới.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Họ và tên</label>
                                <input type="text" name="full_name" id="full_name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Chức vụ</label>
                                <select name="role_id" id="role_id" class="form-select">
                                    <option value="2">Nhân viên (Staff)</option>
                                    <option value="1">Quản trị viên (Admin)</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" formaction="<?php echo URLROOT; ?>/staff/add" class="btn btn-info text-white flex-grow-1"><i class="fas fa-plus"></i> THÊM</button>
                                <button type="button" id="btnEdit" class="btn btn-warning text-white flex-grow-1"><i class="fas fa-edit"></i> SỬA</button>
                                <button type="button" id="btnDelete" class="btn btn-danger text-white flex-grow-1"><i class="fas fa-trash"></i> XÓA</button>
                            </div>
                            <div class="mt-3 text-center">
                                <button type="button" onclick="resetForm()" class="btn btn-sm btn-outline-secondary">Làm mới form</button>
                            </div>
                        </form>
                    </div>
                </div>

<div class="col-md-8">
    <div class="card-box shadow-sm p-3 bg-white rounded">
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h5 class="text-primary mb-0">Danh sách nhân viên</h5>
            <input type="text" id="searchStaff" class="form-control w-50" placeholder="Tìm tên, username, chức vụ...">
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Username</th>
                        <th>Họ tên</th>
                        <th>Chức vụ</th>
                        <th>Trạng thái</th> 
                    </tr>
                </thead>
                <tbody id="staffTableBody">
                    <?php if (!empty($data['users'])): ?>
                        <?php foreach($data['users'] as $user): ?>
                        
                        <tr class="table-row <?php echo ($user->is_deleted == 1) ? 'table-secondary text-muted' : ''; ?>" 
                            onclick='<?php echo ($user->is_deleted == 0) ? 'selectUser(this, ' . htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') . ')' : ''; ?>'>
                            
                            <td><?php echo $user->user_id; ?></td>
                            
                            <td class="fw-bold">
                                <?php echo htmlspecialchars($user->username); ?>
                                <?php if($user->is_deleted == 1): ?>
                                    <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Đã xóa</span>
                                <?php endif; ?>
                            </td>
                            
                            <td><?php echo htmlspecialchars($user->full_name); ?></td>
                            
                            <td>
                                <?php if($user->role_id == 1): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">Staff</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if($user->is_deleted == 1): ?>
                                    <span class="badge bg-secondary me-2">Thùng rác</span>
                                    <a href="<?php echo URLROOT; ?>/staff/restore/<?php echo $user->user_id; ?>" 
                                       class="btn btn-sm btn-success fw-bold btn-restore py-0"
                                       title="Khôi phục tài khoản"
                                       onclick="event.stopPropagation();">
                                        <i class="fas fa-trash-restore"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-success">Đang hoạt động</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Chưa có nhân viên nào.</td>
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
</div>
<?php if(isset($_SESSION['msg_type']) && isset($_SESSION['msg_text'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $_SESSION['msg_type']; ?>', // 'error' hoặc 'success'
                title: 'Thông báo',
                text: '<?php echo $_SESSION['msg_text']; ?>',
                confirmButtonColor: '<?php echo ($_SESSION['msg_type'] == 'error') ? '#e74a3b' : '#1cc88a'; ?>',
                confirmButtonText: 'Đã hiểu'
            });
        });
    </script>
    <?php 
        // Xóa session ngay sau khi hiển thị để không hiện lại khi F5
        unset($_SESSION['msg_type']);
        unset($_SESSION['msg_text']);
    ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>const URLROOT = '<?php echo URLROOT; ?>';</script>
<script src="<?php echo URLROOT; ?>/js/staff.js"></script>

</body>
</html>