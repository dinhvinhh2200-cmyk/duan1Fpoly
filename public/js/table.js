/**
 * TABLE MANAGEMENT JAVASCRIPT
 * File: public/js/table.js
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initSearch();
    initDeleteAction();
    initRestoreAction();
});

// 1. Sidebar Toggle
function initSidebar() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if(sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
}

// 2. Tìm kiếm nhanh
function initSearch() {
    const searchInput = document.getElementById('searchTable');
    const tableBody = document.getElementById('tableTableBody');
    if(searchInput && tableBody) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');
            Array.from(rows).forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
}

// 3. Xử lý nút XÓA (Click trực tiếp vào nút trên bảng)
function initDeleteAction() {
    const tableBody = document.getElementById('tableTableBody');
    if(!tableBody) return;

    tableBody.addEventListener('click', function(e) {
        // Tìm nút delete gần nhất (trong trường hợp click vào icon bên trong nút)
        const btn = e.target.closest('.btn-delete');
        
        if (btn) {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');

            Swal.fire({
                title: 'Xóa bàn này?',
                html: `Bàn <b>${name}</b> sẽ bị ẩn khỏi sơ đồ POS.<br><small class="text-muted">(Dữ liệu đơn hàng cũ vẫn được giữ nguyên)</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `${URLROOT}/table/delete/${id}`;
                }
            });
        }
    });
}

// 4. Xử lý nút KHÔI PHỤC
function initRestoreAction() {
    const tableBody = document.getElementById('tableTableBody');
    if(!tableBody) return;

    tableBody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-restore');
        if (btn) {
            e.preventDefault();
            const url = btn.getAttribute('href');

            Swal.fire({
                title: 'Khôi phục bàn?',
                text: "Bàn sẽ hiển thị trở lại ở trạng thái Trống.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    });
}