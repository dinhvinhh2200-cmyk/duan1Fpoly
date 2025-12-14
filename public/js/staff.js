/**
 * STAFF MANAGEMENT JAVASCRIPT
 * -------------------------------------------------------------------------
 * - Chức năng: Xử lý sự kiện cho trang Quản lý Nhân viên
 * - Có thêm: Chức năng tìm kiếm nhanh
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initSearch(); // [MỚI] Khởi tạo tìm kiếm
    initFormActions();
    initRestoreAction(); 
});

// ============================================================
// 1. INITIALIZATION MODULES
// ============================================================

function initSidebar() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if(sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
}

/**
 * [MỚI] Module: Tìm kiếm nhanh trên bảng
 */
function initSearch() {
    const searchInput = document.getElementById('searchStaff');
    const tableBody = document.getElementById('staffTableBody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase(); // Lấy từ khóa, chuyển về chữ thường
            const rows = tableBody.getElementsByTagName('tr');
            
            // Duyệt qua từng dòng trong bảng
            Array.from(rows).forEach(row => {
                // Lấy toàn bộ nội dung text trong dòng đó
                const text = row.innerText.toLowerCase();
                
                // Nếu có chứa từ khóa -> Hiện, ngược lại -> Ẩn
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
}

function initFormActions() {
    const btnEdit = document.getElementById('btnEdit');
    const btnDelete = document.getElementById('btnDelete');
    const userIdInput = document.getElementById('user_id');
    const form = document.getElementById('userForm');

    // 1. Xử lý nút SỬA
    if (btnEdit) {
        btnEdit.addEventListener('click', function() {
            const id = userIdInput.value;
            if(!id) { 
                Swal.fire({
                    icon: 'warning', title: 'Chưa chọn nhân viên!', text: 'Vui lòng click vào một dòng trong danh sách để sửa.', confirmButtonColor: '#f6c23e', confirmButtonText: 'Đã hiểu'
                });
                return; 
            }
            form.action = `${URLROOT}/staff/edit/${id}`;
            form.submit();
        });
    }

    // 2. Xử lý nút XÓA
    if (btnDelete) {
        btnDelete.addEventListener('click', function() {
            const id = userIdInput.value;
            const username = document.getElementById('username').value; 

            if(!id) { 
                Swal.fire({
                    icon: 'warning', title: 'Chưa chọn nhân viên!', text: 'Vui lòng chọn nhân viên cần xóa!', confirmButtonColor: '#e74a3b', confirmButtonText: 'Đã hiểu'
                });
                return; 
            }

            Swal.fire({
                title: 'XÓA TÀI KHOẢN?',
                html: `Bạn có chắc chắn muốn xóa nhân viên <b>${username}</b> không?<br>Tài khoản này sẽ bị vô hiệu hóa và chuyển vào thùng rác.`,
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74a3b', cancelButtonColor: '#858796', confirmButtonText: 'Xóa ngay', cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${URLROOT}/staff/delete/${id}`;
                }
            });
        });
    }
}

// ============================================================
// 2. GLOBAL FUNCTIONS (Gọi từ HTML onclick)
// ============================================================

function selectUser(row, user) {
    document.querySelectorAll('.table-row').forEach(r => r.classList.remove('active'));
    row.classList.add('active');

    document.getElementById('user_id').value = user.user_id;
    
    const usernameInput = document.getElementById('username');
    usernameInput.value = user.username;
    usernameInput.readOnly = true; 
    
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('role_id').value = user.role_id;
    
    // [ĐÃ SỬA] Hiển thị ô nhập mật khẩu nhưng để trống (Tính năng Reset Password)
    const divPass = document.getElementById('div-password');
    const passInput = document.getElementById('password');
    const passHint = document.getElementById('pass_hint');
    const reqStar = document.getElementById('req-pass');

    if (divPass) {
        divPass.style.display = 'block'; // Luôn hiện
        passInput.value = ''; // Xóa trắng
        passInput.placeholder = 'Nhập mật khẩu mới để cấp lại (hoặc để trống)';
        
        // Đổi chú thích
        if(passHint) passHint.innerText = 'Để trống nếu không muốn đổi mật khẩu.';
        // Ẩn dấu sao đỏ (vì không bắt buộc khi sửa)
        if(reqStar) reqStar.style.display = 'none';
    }

    // [MỚI] Ẩn nút Xóa nếu là Admin
    const btnDelete = document.getElementById('btnDelete');
    if (btnDelete) {
        if (user.role_id == 1) {
            btnDelete.style.display = 'none';
        } else {
            btnDelete.style.display = 'inline-block';
        }
    }
}

function resetForm() {
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    document.getElementById('username').readOnly = false; 
    
    document.querySelectorAll('.table-row').forEach(r => r.classList.remove('active'));
    
    // Khôi phục trạng thái form Thêm mới
    const divPass = document.getElementById('div-password');
    const passInput = document.getElementById('password');
    const passHint = document.getElementById('pass_hint');
    const reqStar = document.getElementById('req-pass');

    if (divPass) {
        divPass.style.display = 'block';
        passInput.placeholder = 'Nhập mật khẩu...';
        if(passHint) passHint.innerText = 'Bắt buộc khi tạo mới.';
        if(reqStar) reqStar.style.display = 'inline';
    }

    const btnDelete = document.getElementById('btnDelete');
    if (btnDelete) btnDelete.style.display = 'inline-block'; 
}

function initRestoreAction() {
    const restoreBtns = document.querySelectorAll('.btn-restore');
    restoreBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); 
            e.stopPropagation(); 

            const url = this.getAttribute('href'); 

            Swal.fire({
                title: 'Khôi phục nhân viên?',
                text: "Tài khoản sẽ được kích hoạt và hoạt động trở lại ngay lập tức.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Khôi phục ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
}