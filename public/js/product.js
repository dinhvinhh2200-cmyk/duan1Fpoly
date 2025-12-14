/**
 * PRODUCT MANAGEMENT JAVASCRIPT
 * File: public/js/product.js
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initSearch();
    initFormActions(); // Gọi đúng tên hàm tại đây
    initRestoreAction(); // [MỚI] Gọi hàm
});

// ============================================================
// 1. INITIALIZATION MODULES
// ============================================================

/**
 * Module: Xử lý Toggle Sidebar
 */
function initSidebar() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if(sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
}

/**
 * Module: Tìm kiếm nhanh trên bảng
 */
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('productTableBody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');
            
            Array.from(rows).forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
}

/**
 * Module: Xử lý các nút trong Form (Sửa, Xóa) với SweetAlert2
 */
function initFormActions() {
    const btnEdit = document.getElementById('btnEdit');
    const btnDelete = document.getElementById('btnDelete');
    const productIdInput = document.getElementById('product_id');
    const form = document.getElementById('productForm');

    // 1. Xử lý nút SỬA
    if (btnEdit) {
        btnEdit.addEventListener('click', function() {
            const id = productIdInput.value;
            
            // Kiểm tra nếu chưa chọn món
            if (!id) { 
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn món!',
                    text: 'Vui lòng click vào một dòng trong bảng để sửa.',
                    confirmButtonColor: '#f6c23e',
                    confirmButtonText: 'Đã hiểu'
                });
                return; 
            }
            
            // Đổi action của form sang route Edit và submit
            form.action = `${URLROOT}/product/edit/${id}`;
            form.submit();
        });
    }

    // 2. Xử lý nút XÓA
    if (btnDelete) {
        btnDelete.addEventListener('click', function() {
            const id = productIdInput.value;
            
            // Kiểm tra nếu chưa chọn món
            if (!id) { 
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa chọn món!',
                    text: 'Vui lòng chọn món cần xóa!',
                    confirmButtonColor: '#e74a3b',
                    confirmButtonText: 'Đã hiểu'
                });
                return; 
            }
            
            // Hộp thoại xác nhận xóa
            Swal.fire({
                title: 'Xác nhận xóa?',
                text: "Hành động này sẽ chuyển món vào thùng rác (xóa mềm).",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Chuyển hướng để xóa
                    window.location.href = `${URLROOT}/product/delete/${id}`;
                }
            });
        });
    }
}

// ============================================================
// 2. GLOBAL FUNCTIONS (Gọi từ HTML onclick)
// ============================================================

/**
 * Chọn sản phẩm từ bảng để đổ dữ liệu vào form
 */
function selectProduct(row, product) {
    // 1. Highlight dòng được chọn
    document.querySelectorAll('.table-row').forEach(r => r.classList.remove('active'));
    row.classList.add('active');

    // 2. Đổ dữ liệu vào Form
    document.getElementById('product_id').value = product.product_id;
    document.getElementById('product_name').value = product.product_name;
    document.getElementById('category_id').value = product.category_id;
    document.getElementById('price').value = product.price;
 
    
    // 3. Hiển thị ảnh preview
    const previewDiv = document.getElementById('current_image_preview');
    if (previewDiv) {
        if (product.image) {
            previewDiv.innerHTML = `
                <img src="${URLROOT}/public/uploads/${product.image}" 
                     style="width: 80px; border-radius: 5px; border: 1px solid #ddd; object-fit: cover;">
                <div class="small text-muted mt-1">Ảnh hiện tại</div>
            `;
        } else {
            previewDiv.innerHTML = '<span class="text-muted small fst-italic">Chưa có hình ảnh</span>';
        }
    }
}

/**
 * Làm mới form để thêm món mới
 */
function resetForm() {
    // Reset các input
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    
    // Reset action về mặc định (Thêm mới)
    document.getElementById('productForm').action = `${URLROOT}/product/add`;

    // Xóa highlight trên bảng
    document.querySelectorAll('.table-row').forEach(r => r.classList.remove('active'));
    
    // Xóa ảnh preview
    const previewDiv = document.getElementById('current_image_preview');
    if (previewDiv) previewDiv.innerHTML = "";
}
/**
 * [MỚI] Xử lý nút Khôi phục món
 */
function initRestoreAction() {
    const restoreBtns = document.querySelectorAll('.btn-restore');
    restoreBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Chặn chuyển trang ngay
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Khôi phục món này?',
                text: "Món ăn sẽ xuất hiện trở lại trong danh sách nhưng ở trạng thái 'Ngừng bán'.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a', // Màu xanh lá
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