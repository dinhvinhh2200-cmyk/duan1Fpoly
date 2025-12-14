/**
 * DISCOUNT MANAGEMENT JAVASCRIPT
 * File: public/js/discount.js
 * Chức năng: Xử lý logic giao diện cho trang Quản lý Mã giảm giá.
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initDiscountTypeLogic(); // Xử lý logic loại giảm giá (VNĐ/%)
    initConditionLogic();    // Xử lý logic điều kiện đơn tối thiểu
    initDeleteAction();      // Xử lý nút xóa
    initRestoreAction();     // Xử lý nút khôi phục
});

// ============================================================
// 1. INITIALIZATION MODULES (Khởi tạo)
// ============================================================

/**
 * Module: Xử lý Toggle Sidebar (Thu gọn menu)
 */
function initSidebar() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', () => sidebar.classList.toggle('active'));
    }
}

/**
 * Module: Xử lý logic Loại giảm giá (Tiền mặt / Phần trăm)
 * - Thay đổi đơn vị hiển thị (VNĐ / %)
 * - Ẩn/Hiện ô nhập "Giảm tối đa" (Chỉ hiện khi chọn %)
 */
function initDiscountTypeLogic() {
    const typeSelect = document.querySelector('select[name="type"]');
    const unitSpan = document.getElementById('value-unit');
    const boxMax = document.getElementById('box-max-discount');

    if (typeSelect) {
        const handleTypeChange = () => {
            if (typeSelect.value === 'percentage') {
                // Nếu chọn Phần trăm (%)
                if(unitSpan) unitSpan.innerText = '%';
                if(boxMax) boxMax.style.display = 'block'; // Hiện ô nhập tối đa
            } else {
                // Nếu chọn Tiền mặt (fixed)
                if(unitSpan) unitSpan.innerText = 'VNĐ';
                if(boxMax) {
                    boxMax.style.display = 'none'; // Ẩn ô nhập tối đa
                    document.getElementById('max_discount_amount').value = ''; // Xóa giá trị cũ nếu có
                }
            }
        };

        typeSelect.addEventListener('change', handleTypeChange);
        // Gọi 1 lần ngay khi tải trang để set đúng trạng thái ban đầu
        handleTypeChange();
    }
}

/**
 * Module: Xử lý logic Điều kiện áp dụng (Có điều kiện / Không)
 * - Ẩn/Hiện ô nhập "Đơn hàng tối thiểu"
 */
function initConditionLogic() {
    const radioNone = document.getElementById('cond_none');
    const radioMin = document.getElementById('cond_min');
    const boxMin = document.getElementById('box-min-value');

    if(radioNone && radioMin && boxMin) {
        const toggleBox = () => {
            const input = boxMin.querySelector('input');
            if (radioMin.checked) {
                boxMin.style.display = 'block'; // Hiện ô nhập
                if(input) input.setAttribute('required', 'required'); // Bắt buộc nhập
            } else {
                boxMin.style.display = 'none'; // Ẩn ô nhập
                if(input) {
                    input.removeAttribute('required');
                    input.value = ''; // Xóa giá trị
                }
            }
        };
        radioNone.addEventListener('change', toggleBox);
        radioMin.addEventListener('change', toggleBox);
        // Gọi 1 lần để set trạng thái ban đầu
        toggleBox();
    }
}

/**
 * [ĐÃ SỬA] Hàm đổ dữ liệu vào form để Sửa
 * @param {Object} data - Dữ liệu mã giảm giá (JSON)
 */
function editDiscount(data) {
    // 1. Đổ dữ liệu cơ bản
    document.getElementById('discount_id').value = data.discount_id;
    document.getElementById('code').value = data.code;
    document.getElementById('type').value = data.type;
    document.getElementById('value').value = data.value;

    // [CŨ] Đổ dữ liệu Giảm tối đa (nếu có > 0)
    const maxVal = parseFloat(data.max_discount_amount);
    document.getElementById('max_discount_amount').value = (maxVal > 0) ? maxVal : '';

    // [MỚI] Đổ dữ liệu Thời gian hiệu lực (Ngày bắt đầu & Kết thúc)
    // Lưu ý: Input datetime-local yêu cầu format 'YYYY-MM-DDTHH:mm'
    // Dữ liệu từ PHP thường là 'YYYY-MM-DD HH:mm:ss' -> cần thay khoảng trắng bằng 'T' và cắt bỏ giây
    if (data.start_date) {
        document.getElementById('start_date').value = data.start_date.replace(' ', 'T').slice(0, 16);
    } else {
        document.getElementById('start_date').value = '';
    }

    if (data.end_date) {
        document.getElementById('end_date').value = data.end_date.replace(' ', 'T').slice(0, 16);
    } else {
        document.getElementById('end_date').value = '';
    }

    // Trigger event change để cập nhật giao diện (đơn vị tiền tệ, ẩn/hiện ô tối đa)
    document.getElementById('type').dispatchEvent(new Event('change'));

    // 2. Xử lý điều kiện (Min order value)
    const minVal = parseFloat(data.min_order_value);
    if (minVal > 0) {
        document.getElementById('cond_min').checked = true;
        document.getElementById('min_order_value').value = minVal;
    } else {
        document.getElementById('cond_none').checked = true;
        document.getElementById('min_order_value').value = '';
    }
    // Trigger event change để hiện/ẩn ô nhập tiền tối thiểu
    document.getElementById('cond_min').dispatchEvent(new Event('change'));

    // 3. Đổi giao diện nút Lưu -> Cập nhật
    const btnSave = document.getElementById('btnSave');
    btnSave.innerHTML = '<i class="fas fa-sync-alt"></i> Cập nhật';
    btnSave.classList.replace('btn-primary', 'btn-warning');
    btnSave.classList.add('text-white');

    // 4. Đổi Action của form sang Edit
    const form = document.getElementById('discountForm');
    form.action = `${URLROOT}/discount/edit/${data.discount_id}`;
    
    // Cuộn lên đầu trang để thấy form
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * [ĐÃ SỬA] Hàm Reset Form về trạng thái Thêm mới
 */
function resetDiscountForm() {
    // Reset inputs về rỗng
    document.getElementById('discountForm').reset();
    document.getElementById('discount_id').value = '';
    
    // [MỚI] Reset các trường ngày tháng về rỗng
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';

    // Reset Loại giảm giá về mặc định (Tiền mặt) để ẩn ô tối đa
    const typeSelect = document.getElementById('type');
    typeSelect.value = 'fixed';
    typeSelect.dispatchEvent(new Event('change'));

    // Reset giao diện điều kiện về mặc định (Không điều kiện)
    const condNone = document.getElementById('cond_none');
    condNone.checked = true;
    condNone.dispatchEvent(new Event('change'));
    
    // Reset Action về Add
    document.getElementById('discountForm').action = `${URLROOT}/discount/add`;
    
    // Reset nút Lưu về trạng thái ban đầu
    const btnSave = document.getElementById('btnSave');
    btnSave.innerHTML = 'Lưu mã giảm giá';
    btnSave.classList.replace('btn-warning', 'btn-primary');
    btnSave.classList.remove('text-white');
}

/**
 * Module: Xử lý nút Xóa mã giảm giá (Xóa mềm)
 */
function initDeleteAction() {
    // Chọn tất cả các nút có class .btn-delete-discount
    const deleteBtns = document.querySelectorAll('.btn-delete-discount');

    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault(); // Ngăn chặn chuyển trang ngay lập tức
            
            const deleteUrl = this.getAttribute('href'); // Lấy link xóa

            Swal.fire({
                title: 'XÓA MÃ GIẢM GIÁ?',
                text: "Mã này sẽ được chuyển vào thùng rác.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b', // Màu đỏ
                cancelButtonColor: '#858796',  // Màu xám
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Nếu đồng ý -> Chuyển trang đến link xóa
                    window.location.href = deleteUrl;
                }
            });
        });
    });
}

/**
 * Module: Xử lý nút Khôi phục mã giảm giá
 */
function initRestoreAction() {
    const restoreBtns = document.querySelectorAll('.btn-restore');
    restoreBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');

            Swal.fire({
                title: 'Khôi phục mã giảm giá?',
                text: "Mã này sẽ xuất hiện lại trong danh sách.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Khôi phục',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
}