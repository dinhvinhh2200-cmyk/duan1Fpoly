/**
 * SHIFT REPORT JAVASCRIPT
 * File: public/js/shift.js
 */

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initCloseShiftAction(); // Kích hoạt sự kiện chốt ca
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

function initCloseShiftAction() {
    const form = document.getElementById('closeShiftForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const actualCashInput = form.querySelector('input[name="actual_cash"]');
            const actualMoney = actualCashInput ? actualCashInput.value : '0';

            if (!actualMoney || actualMoney < 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chưa nhập tiền!',
                    text: 'Vui lòng kiểm đếm và nhập số tiền thực tế trong két.',
                    confirmButtonColor: '#f6c23e'
                });
                return;
            }

            Swal.fire({
                title: 'CHỐT CA & ĐĂNG XUẤT?',
                html: `Bạn xác nhận trong két đang có: <b class="text-success fs-4">${parseInt(actualMoney).toLocaleString('vi-VN')}đ</b><br>Hành động này sẽ kết thúc phiên làm việc.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Đồng ý chốt ca',
                cancelButtonText: 'Kiểm tra lại'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    }
}

// ============================================================
// 2. HELPER FUNCTIONS
// ============================================================

const formatMoney = (amount) => parseInt(amount || 0).toLocaleString('vi-VN') + 'đ';

// ============================================================
// 3. GLOBAL FUNCTIONS (Gọi từ HTML onclick)
// ============================================================

// --- A. XEM CHI TIẾT CA CŨ (Dành cho Admin) ---
function viewSessionDetail(element) {
    const start = element.getAttribute('data-start');
    const end = element.getAttribute('data-end');
    const id = element.getAttribute('data-id');
    const note = element.getAttribute('data-note');

    const idEl = document.getElementById('modalSessionId');
    if (idEl) idEl.innerText = id;

    const noteContainer = document.getElementById('modalSessionNote');
    if (noteContainer) {
        if (note && note.trim() !== "") {
            noteContainer.innerHTML = `
                <div class="alert alert-warning small fst-italic mb-3 shadow-sm">
                    <i class="fas fa-comment-alt me-2"></i> <strong>Ghi chú chốt ca:</strong> ${note}
                </div>`;
            noteContainer.style.display = 'block';
        } else {
            noteContainer.innerHTML = '';
            noteContainer.style.display = 'none';
        }
    }

    const tbody = document.getElementById('modalItemsBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Đang tải dữ liệu...</td></tr>';
    }
    
    const modalEl = document.getElementById('sessionDetailModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    const formData = new FormData();
    formData.append('start', start);
    formData.append('end', end);

    fetch(`${URLROOT}/shift/get_session_details`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (!tbody) return;

        if(data.items && data.items.length > 0) {
            const rowsHtml = data.items.map(item => {
                let noteHtml = '';
                if (item.note && item.note.trim() !== '') {
                    noteHtml = `<div class="small text-muted fst-italic mt-1"><i class="fas fa-level-up-alt fa-rotate-90 me-1"></i> ${item.note}</div>`;
                }

                return `
                    <tr>
                        <td class="text-start ps-3 align-middle">
                            <div class="fw-bold text-primary">${item.product_name}</div>
                            ${noteHtml}
                        </td>
                        <td class="text-center fw-bold align-middle">${item.qty}</td>
                        <td class="text-end fw-bold text-danger pe-3 align-middle">${formatMoney(item.subtotal)}</td>
                    </tr>
                `;
            }).join('');
            
            tbody.innerHTML = rowsHtml;
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Không có món nào được bán trong ca này.</td></tr>';
        }
    })
    .catch(err => {
        console.error(err);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-3">Lỗi tải dữ liệu.</td></tr>';
        }
    });
}

// --- B. [MỚI] XEM CHI TIẾT ĐƠN HÀNG (Dành cho Staff xem lại đơn mình bán) ---
function showMyOrderDetail(orderId) {
    document.body.style.cursor = 'wait';

    // Gọi API của Shift Controller
    fetch(`${URLROOT}/shift/get_order_detail/${orderId}`)
        .then(response => response.json())
        .then(data => {
            document.body.style.cursor = 'default';
            renderMyModalData(data); // Gọi hàm render bên dưới
        })
        .catch(error => {
            console.error('Lỗi chi tiết:', error);
            document.body.style.cursor = 'default';
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Không thể tải chi tiết đơn hàng.'
            });
        });
}

function renderMyModalData(data) {
    const info = data.info || {};
    const items = data.items || [];

    // Gán dữ liệu vào Modal (Modal này có sẵn ID từ file modal_detail.php được include)
    const idEl = document.getElementById('modalOrderId');
    const staffEl = document.getElementById('modalStaffName');
    const timeEl = document.getElementById('modalTime');
    const totalEl = document.getElementById('modalTotal');
    
    if(idEl) idEl.innerText = '#' + (info.order_id || 'N/A');
    if(staffEl) staffEl.innerText = info.staff_name || '...';
    
    // Format thời gian
    const date = new Date(info.order_time);
    if(timeEl) timeEl.innerText = !isNaN(date) ? date.toLocaleString('vi-VN') : info.order_time;

    // Tổng tiền
    const total = parseInt(info.total_amount || 0);
    const final = parseInt(info.final_amount || total);

    if (totalEl) {
        if (total > final) {
            totalEl.innerHTML = `
                <div class="d-flex flex-column align-items-end">
                    <small class="text-decoration-line-through text-muted" style="font-size: 0.85rem;">${formatMoney(total)}</small>
                    <span class="text-danger fw-bold">${formatMoney(final)}</span>
                </div>`;
        } else {
            totalEl.innerText = formatMoney(final);
        }
    }

    // Render danh sách món
    const tbody = document.getElementById('modalOrderItems');
    if (tbody) {
        if (items.length > 0) {
            const itemsHtml = items.map(item => {
                const subtotal = item.quantity * item.unit_price;
                let productName = item.product_name;
                if (item.note) productName += ` <span class="text-success fst-italic small">(${item.note})</span>`;

                return `
                    <tr>
                        <td class="fw-bold text-primary">${productName}</td>
                        <td class="text-center fw-bold">${item.quantity}</td>
                        <td class="text-end text-muted">${formatMoney(item.unit_price)}</td>
                        <td class="text-end fw-bold text-dark">${formatMoney(subtotal)}</td>
                    </tr>`;
            }).join('');
            
            tbody.innerHTML = itemsHtml;

            // Thêm dòng giảm giá nếu có
            if (total > final) {
                tbody.insertAdjacentHTML('beforeend', `
                    <tr class="bg-light">
                        <td colspan="3" class="text-end fw-bold text-success fst-italic">Giảm giá:</td>
                        <td class="text-end fw-bold text-success">-${formatMoney(total - final)}</td>
                    </tr>`);
            }
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Không có thông tin món.</td></tr>';
        }
    }

    // Mở modal (đã được include trong View)
    const modalElement = document.getElementById('orderDetailModal');
    if(modalElement) {
        let myModal = bootstrap.Modal.getInstance(modalElement);
        if (!myModal) myModal = new bootstrap.Modal(modalElement);
        myModal.show();
    }
}