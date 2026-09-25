/**
 * payments.js - Receive Fee page logic: student selection, balance/monthly
 * status preview, payment submission, and recent payments list.
 */

let studentsCache = [];
let recentPaymentsCache = [];
let currentMonthlyStatus = [];

async function loadStudentsIntoSelect() {
    const result = await apiGet(`${BASE_URL}/ajax/students_list.php`);
    if (!result.success) return;
    studentsCache = result.data;

    // For fixed courses "pending" means balance > 0.
    // For monthly courses, always list them — balance/arrears may be 0
    // even though the current month is still payable.
    const relevantStudents = studentsCache.filter(s =>
        s.fee_type === 'monthly' || parseFloat(s.balance) > 0
    );

    const select = document.getElementById('paymentStudentSelect');
    select.innerHTML = '<option value="">-- Select Student --</option>' +
        relevantStudents.map(s => `<option value="${s.id}">${escapeHtml(s.name)} - ${escapeHtml(s.mobile)} (${escapeHtml(s.course_name)})</option>`).join('');

    const preselect = document.getElementById('preselectStudentId').value;
    if (preselect && preselect !== '0') {
        select.value = preselect;
        showStudentFeeInfo(preselect);
    }
}

function monthKey(y, m) { return `${y}-${m}`; }

function renderMonthlyBillingSelect(months) {
    const select = document.getElementById('paymentBillingMonth');
    const payable = months.filter(m => m.status !== 'paid');

    select.innerHTML = payable.map(m =>
        `<option value="${m.year}-${m.month}" data-fee="${m.monthly_fee}">${escapeHtml(m.label)}${m.status === 'upcoming' ? ' (Upcoming)' : ' (Due)'}</option>`
    ).join('');

    // Default to the earliest due month, or the upcoming one if all caught up.
    const dueFirst = payable.find(m => m.status === 'due');
    if (dueFirst) {
        select.value = monthKey(dueFirst.year, dueFirst.month);
    } else if (payable.length) {
        select.value = monthKey(payable[0].year, payable[0].month);
    }

    applyBillingMonthFeeToAmount();
}

function applyBillingMonthFeeToAmount() {
    const select = document.getElementById('paymentBillingMonth');
    const opt = select.selectedOptions[0];
    if (opt) {
        document.getElementById('paymentAmount').value = parseFloat(opt.dataset.fee).toFixed(2);
    }
}

async function showStudentFeeInfo(studentId) {
    const fixedBox = document.getElementById('studentBalanceInfo');
    const monthlyBox = document.getElementById('monthlyFeeStatusInfo');
    const billingWrap = document.getElementById('billingMonthWrap');
    const billingSelect = document.getElementById('paymentBillingMonth');
    const amountInput = document.getElementById('paymentAmount');

    const student = studentsCache.find(s => String(s.id) === String(studentId));
    if (!student) {
        fixedBox.classList.add('d-none');
        monthlyBox.classList.add('d-none');
        billingWrap.classList.add('d-none');
        billingSelect.required = false;
        return;
    }

    if (student.fee_type === 'monthly') {
        fixedBox.classList.add('d-none');

        const result = await apiGet(`${BASE_URL}/ajax/students_get.php?id=${studentId}`);
        currentMonthlyStatus = (result.success && result.data.monthly_fee_status) ? result.data.monthly_fee_status : [];

        document.getElementById('monthlyFeeStatusCourseName').textContent = student.course_name;
        document.getElementById('monthlyFeeStatusRate').textContent = formatMoney(student.course_fee);
        document.getElementById('monthlyFeeStatusTableBody').innerHTML = currentMonthlyStatus.map(m => {
            const badge = m.status === 'paid'
                ? '<span class="badge bg-success">Paid</span>'
                : m.status === 'upcoming'
                    ? '<span class="badge bg-secondary">Upcoming</span>'
                    : '<span class="badge bg-danger">Due</span>';
            return `<tr><td>${escapeHtml(m.label)}</td><td>${badge}</td></tr>`;
        }).join('');

        monthlyBox.classList.remove('d-none');
        billingWrap.classList.remove('d-none');
        billingSelect.required = true;
        renderMonthlyBillingSelect(currentMonthlyStatus);
    } else {
        monthlyBox.classList.add('d-none');
        billingWrap.classList.add('d-none');
        billingSelect.required = false;
        billingSelect.innerHTML = '';

        const balanceClass = student.balance > 0 ? 'text-danger' : 'text-success';
        fixedBox.innerHTML = `
            <div class="d-flex justify-content-between flex-wrap">
                <div><strong>Course Fee:</strong> ${formatMoney(student.course_fee)}</div>
                <div><strong>Total Paid:</strong> <span class="text-success">${formatMoney(student.total_paid)}</span></div>
                <div><strong>Balance:</strong> <span class="${balanceClass} fw-bold">${formatMoney(student.balance)}</span></div>
            </div>`;
        fixedBox.classList.remove('d-none');

        if (!amountInput.value) {
            amountInput.value = '1000.00';
        }
    }
}

async function loadRecentPayments() {
    const result = await apiGet(`${BASE_URL}/ajax/payments_list.php`);
    const tbody = document.getElementById('recentPaymentsListBody');

    if (!result.success || !result.data.length) {
        recentPaymentsCache = [];
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No payments yet.</td></tr>`;
        return;
    }

    recentPaymentsCache = result.data.slice(0, 10);

    tbody.innerHTML = recentPaymentsCache.map(p => `
        <tr>
            <td><span class="badge bg-primary">${escapeHtml(p.receipt_no)}</span></td>
            <td>${escapeHtml(p.student_name)}</td>
            <td class="text-success fw-semibold">${formatMoney(p.amount)}</td>
            <td>${formatDate(p.payment_date)}</td>
            <td class="text-end">
                <a href="${BASE_URL}/index.php?page=receipt&payment_id=${p.id}" class="btn btn-sm btn-outline-primary" title="View Receipt"><i class="bi bi-file-earmark-text"></i></a>
                <button class="btn btn-sm btn-outline-secondary edit-payment-btn" data-id="${p.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-payment-btn" data-id="${p.id}" data-receipt="${escapeHtml(p.receipt_no)}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `).join('');

    attachPaymentRowEvents();
}

function attachPaymentRowEvents() {
    document.querySelectorAll('.edit-payment-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditPaymentModal(btn.dataset.id));
    });
    document.querySelectorAll('.delete-payment-btn').forEach(btn => {
        btn.addEventListener('click', () => deletePayment(btn.dataset.id, btn.dataset.receipt));
    });
}

function openEditPaymentModal(id) {
    const payment = recentPaymentsCache.find(p => String(p.id) === String(id));
    if (!payment) {
        showToast('Payment not found in the current list.', 'error');
        return;
    }

    document.getElementById('editPaymentId').value = payment.id;
    document.getElementById('editPaymentReceiptNo').value = payment.receipt_no;
    document.getElementById('editPaymentStudentName').value = payment.student_name;
    document.getElementById('editPaymentAmount').value = payment.amount;
    document.getElementById('editPaymentDate').value = payment.payment_date;
    document.getElementById('editPaymentMode').value = payment.payment_mode;
    document.getElementById('editPaymentRemarks').value = payment.remarks || '';

    new bootstrap.Modal(document.getElementById('paymentEditModal')).show();
}

async function deletePayment(id, receiptNo) {
    const confirmed = await confirmAction(`Are you sure you want to delete payment "${receiptNo}"? This will reduce the student's total paid amount. This action cannot be undone.`);
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('id', id);

    const result = await apiPost(`${BASE_URL}/ajax/payments_delete.php`, formData);
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) {
        await loadStudentsIntoSelect();
        await loadRecentPayments();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('paymentDate').value = new Date().toISOString().split('T')[0];

    loadStudentsIntoSelect();
    loadRecentPayments();

    document.getElementById('paymentStudentSelect').addEventListener('change', (e) => {
        document.getElementById('paymentAmount').value = '';
        showStudentFeeInfo(e.target.value);
    });

    document.getElementById('paymentBillingMonth').addEventListener('change', applyBillingMonthFeeToAmount);

    document.getElementById('paymentForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const studentId = document.getElementById('paymentStudentSelect').value;
        if (!studentId) {
            showToast('Please select a student.', 'error');
            return;
        }

        const student = studentsCache.find(s => String(s.id) === String(studentId));

        const formData = new FormData();
        formData.append('student_id', studentId);
        formData.append('amount', document.getElementById('paymentAmount').value);
        formData.append('payment_date', document.getElementById('paymentDate').value);
        formData.append('payment_mode', document.getElementById('paymentMode').value);
        formData.append('remarks', document.getElementById('paymentRemarks').value);

        if (student && student.fee_type === 'monthly') {
            const [y, m] = document.getElementById('paymentBillingMonth').value.split('-');
            if (!y || !m) {
                showToast('Please select a billing month.', 'error');
                return;
            }
            formData.append('billing_year', y);
            formData.append('billing_month', m);
        }

        const submitBtn = document.getElementById('paymentSubmitBtn');
        submitBtn.disabled = true;

        const result = await apiPost(`${BASE_URL}/ajax/payments_add.php`, formData);
        submitBtn.disabled = false;

        if (!result.success) {
            showToast(result.message, 'error');
            return;
        }

        showToast(`Payment recorded! Receipt No: ${result.data.receipt_no}`, 'success');

        document.getElementById('paymentForm').reset();
        document.getElementById('paymentDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('studentBalanceInfo').classList.add('d-none');
        document.getElementById('monthlyFeeStatusInfo').classList.add('d-none');
        document.getElementById('billingMonthWrap').classList.add('d-none');
        document.getElementById('paymentBillingMonth').required = false;

        window.location.href = `${BASE_URL}/index.php?page=receipt&payment_id=${result.data.payment_id}`;
    });

    document.getElementById('paymentEditForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('id', document.getElementById('editPaymentId').value);
        formData.append('amount', document.getElementById('editPaymentAmount').value);
        formData.append('payment_date', document.getElementById('editPaymentDate').value);
        formData.append('payment_mode', document.getElementById('editPaymentMode').value);
        formData.append('remarks', document.getElementById('editPaymentRemarks').value);

        const submitBtn = document.getElementById('paymentEditSubmitBtn');
        submitBtn.disabled = true;

        const result = await apiPost(`${BASE_URL}/ajax/payments_edit.php`, formData);
        submitBtn.disabled = false;

        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('paymentEditModal')).hide();
            await loadStudentsIntoSelect();
            await loadRecentPayments();
        }
    });
});