/**
 * POS SYSTEM JAVASCRIPT
 * -------------------------------------------------------------------------
 * - Chức năng: Xử lý logic bán hàng.
 * - Luồng hoạt động: 
 * 1. Click món -> Thêm nhanh.
 * 2. Hóa đơn: Tăng/Giảm/Sửa/Xóa.
 * 3. Tự động đăng xuất khi hết ca làm việc.
 * - Controller kết nối: PosController.php
 * -------------------------------------------------------------------------
 */

// ============================================================
// 1. GLOBAL VARIABLES & HELPERS
// ============================================================

let currentTableId = null;
let currentOrderId = null;

const formatMoney = (amount) => parseInt(amount || 0).toLocaleString('vi-VN') + 'đ';
const notify = (msg, type = 'warning') => {
    Swal.fire({
        text: msg,
        icon: type,
        confirmButtonColor: '#4e73df',
        confirmButtonText: 'Đã hiểu'
    });
};

// ============================================================
// 2. INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    initClock();
    initAutoLogout();      // Kích hoạt bộ đếm ngược đăng xuất
    initTableLogic();
    initProductLogic();    
    initBillActions();     
    initCheckoutActions(); 
    initDiscountActions(); 
    initSidebarAndSearch();
});

// ============================================================
// 3. LOGIC MODULES
// ============================================================

/**
 * MODULE 0: TỰ ĐỘNG ĐĂNG XUẤT (AUTO LOGOUT)
 * Kiểm tra thời gian còn lại của ca làm việc và reload trang khi hết giờ.
 */
function initAutoLogout() {
    const msInput = document.getElementById('auto-logout-ms');
    if (!msInput) return;

    const remainingMs = parseInt(msInput.value);

    // Nếu có thời gian giới hạn (lớn hơn 0)
    if (remainingMs > 0) {
        console.log(`Hệ thống sẽ tự động đăng xuất sau: ${remainingMs / 1000 / 60} phút.`);
        
        setTimeout(() => {
            alert('Ca làm việc của bạn đã kết thúc! Hệ thống sẽ tự động đăng xuất.');
            // Reload trang để kích hoạt logic kiểm tra trong Controller.php
            window.location.href = URLROOT + '/auth/logout'; 
        }, remainingMs);
    }
}

/**
 * MODULE 1: ĐỒNG HỒ
 */
function initClock() {
    function update() {
        const now = new Date();
        const timeEl = document.getElementById('clock-time');
        const dateEl = document.getElementById('clock-date');
        
        if(timeEl) timeEl.innerText = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        if(dateEl) dateEl.innerText = now.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    update();
    setInterval(update, 1000);
}

/**
 * MODULE 2: XỬ LÝ BÀN
 */
function initTableLogic() {
    const savedTableId = sessionStorage.getItem('reselect_table_id');
    if (savedTableId) {
        const targetTable = document.querySelector(`.table-box[data-id="${savedTableId}"]`);
        if (targetTable) selectTableUI(targetTable);
        sessionStorage.removeItem('reselect_table_id');
    }

    const tableBoxes = document.querySelectorAll('.table-box');
    tableBoxes.forEach(box => {
        box.addEventListener('click', function() {
            selectTableUI(this);
        });
    });
}

function selectTableUI(element) {
    document.querySelectorAll('.table-box').forEach(b => b.classList.remove('border', 'border-3', 'border-primary'));
    element.classList.add('border', 'border-3', 'border-primary');

    currentTableId = element.dataset.id;
    const tableName = element.querySelector('small').innerText;
    document.getElementById('selected-table-name').innerText = tableName;

    loadOrderDetails(currentTableId);
}

/**
 * MODULE 3: CHỌN MÓN (THÊM NHANH)
 */
function initProductLogic() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        card.addEventListener('click', function() {
            if (!currentTableId) { notify('Vui lòng chọn bàn trước!'); return; }

            this.style.transform = 'scale(0.95)';
            setTimeout(() => { this.style.transform = 'scale(1)'; }, 100);

            const formData = new FormData();
            formData.append('table_id', currentTableId);
            formData.append('product_id', this.dataset.id);
            formData.append('price', this.dataset.price);

            fetch(`${URLROOT}/pos/addToOrder`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.is_new_order) {
                        sessionStorage.setItem('reselect_table_id', currentTableId);
                        location.reload();
                    } else {
                        loadOrderDetails(currentTableId);
                    }
                } else {
                    notify(data.message || 'Lỗi thêm món');
                }
            })
            .catch(err => console.error('Lỗi:', err));
        });
    });
}

/**
 * MODULE 4: XỬ LÝ HÓA ĐƠN (TĂNG/GIẢM, SỬA GHI CHÚ, XÓA)
 */
function initBillActions() {
    const billBody = document.getElementById('bill-body');
    if (!billBody) return;

    billBody.addEventListener('click', function(e) {
        
        // A. TĂNG/GIẢM SỐ LƯỢNG
        const btnQty = e.target.closest('.btn-qty');
        if (btnQty) {
            const detailId = btnQty.dataset.id;
            const action = btnQty.dataset.action; // 'inc' hoặc 'dec'
            
            // Logic chặn: Nếu số lượng là 1 mà bấm Giảm -> Không làm gì
            if (action === 'dec') {
                const qtyDisplay = btnQty.parentElement.querySelector('span');
                const currentQty = parseInt(qtyDisplay.innerText);
                if (currentQty <= 1) return; 
            }
            
            const formData = new FormData();
            formData.append('detail_id', detailId);
            formData.append('action', action);

            fetch(`${URLROOT}/pos/updateQuantity`, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') loadOrderDetails(currentTableId);
                else console.error('Lỗi cập nhật số lượng');
            });
            return;
        }

        // B. XÓA MÓN
        const btnDelete = e.target.closest('.btn-delete');
        if (btnDelete) {
            Swal.fire({
                title: 'Xóa món này?',
                text: "Món này sẽ bị loại bỏ khỏi hóa đơn!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('detail_id', btnDelete.dataset.id);
                    formData.append('order_id', currentOrderId);

                    fetch(`${URLROOT}/pos/deleteItem`, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if(data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã xóa!',
                                showConfirmButton: false,
                                timer: 1000,
                                position: 'top-end',
                                toast: true
                            });

                            if (data.is_empty) {
                                sessionStorage.setItem('reselect_table_id', currentTableId);
                                location.reload();
                            } else {
                                loadOrderDetails(currentTableId);
                            }
                        } else {
                            notify('Lỗi: Không thể xóa món!', 'error');
                        }
                    })
                    .catch(err => console.error(err));
                }
            });
            return;
        }

        // C. SỬA MÓN (CHỈ GHI CHÚ - KHÔNG POPUP PHỨC TẠP)
        const btnEdit = e.target.closest('.btn-edit-item');
        if (btnEdit) {
            const detailId = btnEdit.dataset.id;
            const name = btnEdit.dataset.name;
            const basePrice = btnEdit.dataset.baseprice;
            let currentNote = btnEdit.dataset.note || '';

            // Lọc bỏ "Size M", "Size L" khỏi ghi chú để hiển thị cho gọn
            let displayNote = currentNote
                .replace(/Size [ML],? ?/g, '') // Xóa chữ Size M, Size L
                .replace(/^, /, '') // Xóa dấu phẩy đầu nếu có
                .trim();

            // Hiển thị hộp thoại nhập ghi chú đơn giản
            Swal.fire({
                title: `Ghi chú cho: ${name}`,
                input: 'text',
                inputValue: displayNote,
                inputPlaceholder: 'VD: Ít đá, 50% đường...',
                showCancelButton: true,
                confirmButtonText: 'Lưu ghi chú',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#4e73df'
            }).then((result) => {
                if (result.isConfirmed) {
                    const newNote = result.value;
                    
                    // Gửi API cập nhật
                    const formData = new FormData();
                    formData.append('detail_id', detailId);
                    formData.append('order_id', currentOrderId);
                    formData.append('base_price', basePrice);
                    formData.append('extra_price', 0); // Không có phí thêm
                    formData.append('size', 'M');      // Mặc định Size M
                    formData.append('toppings', '');   // Không có topping
                    formData.append('custom_note', newNote);

                    fetch(`${URLROOT}/pos/updateOrderItem`, { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            loadOrderDetails(currentTableId);
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã cập nhật!',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1000
                            });
                        } else {
                            notify('Lỗi cập nhật!');
                        }
                    })
                    .catch(err => console.error(err));
                }
            });
        }
    });
}

function initCheckoutActions() {
    const btnPay = document.getElementById('btn-pay');
    if (btnPay) {
        btnPay.addEventListener('click', function() {
            if (!currentTableId) return;

            Swal.fire({
                title: 'Thanh toán đơn hàng',
                html: 'Vui lòng chọn phương thức thanh toán:',
                icon: 'info',
                input: 'radio',
                inputOptions: {
                    'cash': '💵 Tiền mặt',
                    'transfer': '🏦 Chuyển khoản (QR)'
                },
                inputValue: 'cash',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                confirmButtonText: 'Xác nhận & In hóa đơn',
                cancelButtonText: 'Hủy bỏ',
                inputValidator: (value) => {
                    if (!value) return 'Bạn cần chọn một phương thức!';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const selectedMethod = result.value;
                    const formData = new FormData();
                    formData.append('table_id', currentTableId);
                    formData.append('payment_method', selectedMethod);
                    
                    fetch(`${URLROOT}/pos/checkout`, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            let msg = (selectedMethod === 'transfer') 
                                      ? 'Đã xác nhận chuyển khoản thành công!' 
                                      : 'Đã thu tiền mặt thành công!';

                            Swal.fire({
                                title: 'Thành công!',
                                text: msg,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            notify('Lỗi: ' + data.message, 'error');
                        }
                    });
                }
            });
        });
    }
}

function initDiscountActions() {
    const btnApply = document.getElementById('btn-apply-discount');
    const selectCode = document.getElementById('discount-code');

    if (selectCode && btnApply) {
        selectCode.addEventListener('change', function() {
            btnApply.innerHTML = 'Áp dụng';
            btnApply.className = 'btn btn-outline-primary';
            selectCode.classList.remove('bg-light', 'text-success', 'fw-bold');
        });
    }

    if (btnApply) {
        btnApply.addEventListener('click', function() {
            if (!currentTableId) return;
            
            if (this.classList.contains('btn-danger')) {
                Swal.fire({
                    title: 'Gỡ bỏ ưu đãi?',
                    text: "Đơn hàng sẽ tính lại theo giá gốc.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#e74a3b',
                    cancelButtonColor: '#858796',
                    confirmButtonText: 'Gỡ ngay',
                    cancelButtonText: 'Quay lại'
                }).then((result) => {
                    if (result.isConfirmed) {
                        callDiscountApi(''); 
                    }
                });
            } else {
                const code = selectCode.value.trim();
                if (!code) { callDiscountApi(''); return; }
                callDiscountApi(code); 
            }
        });
    }
}

function callDiscountApi(code) {
    const input = document.getElementById('discount-code');
    const formData = new FormData();
    formData.append('table_id', currentTableId);
    formData.append('code', code);

    fetch(`${URLROOT}/pos/applyDiscount`, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: code ? 'Áp dụng thành công!' : 'Đã gỡ ưu đãi!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
            loadOrderDetails(currentTableId); 
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Không thể áp dụng',
                text: data.message,
                confirmButtonColor: '#4e73df',
                confirmButtonText: 'Đã hiểu'
            });
            if(code && input) input.value = ''; 
        }
    })
    .catch(err => console.error('Lỗi discount:', err));
}

function initSidebarAndSearch() {
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    if(sidebarCollapse) sidebarCollapse.addEventListener('click', () => document.getElementById('sidebar').classList.toggle('active'));
    const searchInput = document.getElementById("searchProduct");
    if (searchInput) searchInput.addEventListener("keyup", () => filterCategory(null, null));
}

function filterCategory(catId, element) {
    if (element) {
        document.querySelectorAll('.category-link').forEach(el => {
            el.classList.remove('active', 'bg-dark', 'text-white');
            el.classList.add('bg-light', 'text-dark');
        });
        element.classList.remove('bg-light', 'text-dark');
        element.classList.add('active', 'bg-dark', 'text-white');
    }
    if (!catId && !element) catId = 'all'; 
    const keyword = document.getElementById("searchProduct").value.toLowerCase().trim();
    document.querySelectorAll(".product-item").forEach(product => {
        const productCat = product.getAttribute("data-cat");
        const name = product.querySelector(".product-name-text").innerText.toLowerCase();
        const matchCategory = (catId && catId !== 'all') ? (productCat == catId) : true;
        const matchSearch = name.includes(keyword);
        product.style.display = (matchCategory && matchSearch) ? "block" : "none";
    });
}

function loadOrderDetails(tableId) {
    fetch(`${URLROOT}/pos/getTableOrder/${tableId}`)
    .then(response => response.json())
    .then(data => {
        const billBody = document.getElementById('bill-body');
        const totalAmount = document.getElementById('total-amount');
        const btnPay = document.getElementById('btn-pay');
        const inputCode = document.getElementById('discount-code');
        const btnDiscount = document.getElementById('btn-apply-discount');
        const discountInfo = document.getElementById('discount-info');
        const discountVal = document.getElementById('discount-value');

        if (data.status === 'success' && data.items.length > 0) {
            currentOrderId = data.order_id;
            if (btnPay) btnPay.disabled = false;

            const itemsHtml = data.items.map(item => {
                const imgUrl = item.image ? `${URLROOT}/public/uploads/${item.image}` : '';
                const imgTag = imgUrl ? `<img src="${imgUrl}" width="50" height="50" class="rounded me-2" style="object-fit: cover;">` 
                                      : `<div class="rounded me-2 bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px; font-size: 10px;">NoImg</div>`;
                const noteText = item.note ? `(${item.note})` : '';
                const subTotal = item.unit_price * item.quantity;

                return `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div class="d-flex align-items-center" style="width: 65%">
                            ${imgTag}
                            <div style="flex: 1; min-width: 0;">
                                <div class="fw-bold text-truncate mb-1" style="font-size: 0.95rem;">${item.product_name}</div>
                                
                                <div class="d-flex align-items-center mb-1">
                                    <button class="btn btn-xs btn-outline-secondary btn-qty px-1 py-0" 
                                            data-action="dec" data-id="${item.order_detail_id}" 
                                            style="width:22px; height:22px; line-height:1;">-</button>
                                            
                                    <span class="mx-2 fw-bold small">${item.quantity}</span>
                                    
                                    <button class="btn btn-xs btn-outline-primary btn-qty px-1 py-0" 
                                            data-action="inc" data-id="${item.order_detail_id}" 
                                            style="width:22px; height:22px; line-height:1;">+</button>
                                            
                                    <small class="text-muted ms-2" style="font-size: 11px;">x ${formatMoney(item.unit_price)}</small>
                                </div>

                                <small class="text-success fst-italic d-block text-truncate" style="font-size: 11px;">${noteText}</small>
                            </div>
                        </div>
                        <div class="text-end ps-1">
                            <div class="fw-bold mb-2 text-danger">${formatMoney(subTotal)}</div>
                            
                            <div class="btn-group">
                                <button class="btn btn-sm btn-light border btn-edit-item text-primary" 
                                        data-id="${item.order_detail_id}" 
                                        data-name="${item.product_name}"
                                        data-baseprice="${item.price}" 
                                        data-note="${item.note || ''}"
                                        title="Sửa ghi chú">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-light border btn-delete text-danger" data-id="${item.order_detail_id}" title="Xóa món">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>`;
            }).join('');
            
            billBody.innerHTML = itemsHtml;

            if (inputCode && btnDiscount) {
                if (data.discount_code) {
                    inputCode.value = data.discount_code;
                    inputCode.readOnly = true;
                    inputCode.classList.add('bg-light', 'text-success', 'fw-bold');
                    btnDiscount.innerHTML = '<i class="fas fa-times"></i> Hủy';
                    btnDiscount.className = 'btn btn-danger';
                } else {
                    inputCode.value = '';
                    inputCode.readOnly = false;
                    inputCode.classList.remove('bg-light', 'text-success', 'fw-bold');
                    btnDiscount.innerText = 'Áp dụng';
                    btnDiscount.className = 'btn btn-outline-primary';
                }
            }

            const discountAmt = parseInt(data.discount_amount || 0);
            const finalAmt = parseInt(data.final_amount || data.total);

            if (discountInfo && discountVal) {
                if (discountAmt > 0) {
                    discountInfo.style.display = 'block';
                    discountVal.innerText = formatMoney(discountAmt);
                    totalAmount.innerText = formatMoney(finalAmt);
                } else {
                    discountInfo.style.display = 'none';
                    totalAmount.innerText = formatMoney(data.total);
                }
            }

        } else {
            if(data.status === 'success' && data.items.length === 0) { location.reload(); return; }
            currentOrderId = null;
            billBody.innerHTML = `<div class="text-center text-muted mt-5"><i class="fas fa-shopping-basket fa-3x mb-3 text-black-50"></i><p>Vui lòng chọn bàn để gọi món</p></div>`;
            totalAmount.innerText = '0 đ';
            if (btnPay) btnPay.disabled = true;
            if (inputCode) { inputCode.value = ''; inputCode.readOnly = false; }
            if (discountInfo) discountInfo.style.display = 'none';
            if (btnDiscount) { btnDiscount.innerText = 'Áp dụng'; btnDiscount.className = 'btn btn-outline-primary'; }
        }
    })
    .catch(error => console.error('Lỗi tải hóa đơn:', error));
}