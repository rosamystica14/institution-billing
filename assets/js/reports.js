/**
 * reports.js - Handles Daily, Monthly, Pending, Total Paid, and Student-wise reports.
 */

let pendingFixedCache = [];
let pendingMonthlyCache = [];
let paidFixedCache = [];
let paidMonthlyCache = [];
let studentReportPaymentsCache = [];

function switchReportTab(tab) {
    document.querySelectorAll('#reportTabs .nav-link').forEach(el => el.classList.toggle('active', el.dataset.tab === tab));
    document.querySelectorAll('.report-panel').forEach(el => el.classList.add('d-none'));
    document.getElementById(`panel-${tab}`).classList.remove('d-none');
}

async function loadDailyReport() {
    const date = document.getElementById('dailyDateInput').value;
    const result = await apiGet(`${BASE_URL}/ajax/reports_daily.php?date=${date}`);
    const tbody = document.getElementById('dailyReportBody');

    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(result.message)}</td></tr>`;
        return;
    }

    document.getElementById('dailyTotal').textContent = 'Total: ' + formatMoney(result.data.total);

    if (!result.data.payments.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No payments found for this date.</td></tr>`;
        return;
    }

    tbody.innerHTML = result.data.payments.map(p => `
        <tr>
            <td><span class="badge bg-primary">${escapeHtml(p.receipt_no)}</span></td>
            <td>${escapeHtml(p.student_name)}</td>
            <td>${escapeHtml(p.course_name)}</td>
            <td>${p.billing_month_label ? escapeHtml(p.billing_month_label) : '<span class="text-muted">-</span>'}</td>
            <td class="text-success fw-semibold">${formatMoney(p.amount)}</td>
            <td>${escapeHtml(p.payment_mode)}</td>
        </tr>
    `).join('');
}

async function loadMonthlyReport() {
    const month = document.getElementById('monthlyMonthInput').value;
    const result = await apiGet(`${BASE_URL}/ajax/reports_monthly.php?month=${month}`);
    const tbody = document.getElementById('monthlyReportBody');
    const breakdownBody = document.getElementById('monthlyCourseBreakdownBody');

    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(result.message)}</td></tr>`;
        breakdownBody.innerHTML = `<tr><td class="text-center text-muted py-3">-</td></tr>`;
        return;
    }

    document.getElementById('monthlyTotal').textContent = 'Total: ' + formatMoney(result.data.total);

    if (!result.data.payments.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No payments found for this month.</td></tr>`;
    } else {
        tbody.innerHTML = result.data.payments.map(p => `
            <tr>
                <td><span class="badge bg-primary">${escapeHtml(p.receipt_no)}</span></td>
                <td>${escapeHtml(p.student_name)}</td>
                <td>${escapeHtml(p.course_name)}</td>
                <td>${p.billing_month_label ? escapeHtml(p.billing_month_label) : '<span class="text-muted">-</span>'}</td>
                <td class="text-success fw-semibold">${formatMoney(p.amount)}</td>
                <td>${formatDate(p.payment_date)}</td>
                <td>${escapeHtml(p.payment_mode)}</td>
            </tr>
        `).join('');
    }

    const courseWise = result.data.course_wise || [];
    breakdownBody.innerHTML = courseWise.length
        ? courseWise.map(c => `
            <tr>
                <td>${escapeHtml(c.course_name)}</td>
                <td class="text-end fw-semibold text-success">${formatMoney(c.total)}</td>
            </tr>
        `).join('')
        : `<tr><td class="text-center text-muted py-3">No collections this month.</td></tr>`;
}

function renderPendingTables() {
    const query = (document.getElementById('pendingSearchInput').value || '').trim().toLowerCase();

    const fixedFiltered = query ? pendingFixedCache.filter(s => s.name.toLowerCase().includes(query)) : pendingFixedCache;
    const monthlyFiltered = query ? pendingMonthlyCache.filter(s => s.name.toLowerCase().includes(query)) : pendingMonthlyCache;

    const fixedBody = document.getElementById('pendingFixedBody');
    fixedBody.innerHTML = fixedFiltered.length ? fixedFiltered.map(s => `
        <tr>
            <td>${escapeHtml(s.name)}</td>
            <td>${escapeHtml(s.mobile)}</td>
            <td>${escapeHtml(s.course_name)}</td>
            <td>${formatMoney(s.course_fee)}</td>
            <td class="text-success">${formatMoney(s.total_paid)}</td>
            <td><span class="badge badge-balance-pending">${formatMoney(s.balance)}</span></td>
        </tr>
    `).join('') : `<tr><td colspan="6" class="text-center text-muted py-4">No matching fixed-course balances.</td></tr>`;
    document.getElementById('pendingFixedTotal').textContent = 'Total: ' + formatMoney(fixedFiltered.reduce((sum, s) => sum + parseFloat(s.balance), 0));

    const monthlyBody = document.getElementById('pendingMonthlyBody');
    monthlyBody.innerHTML = monthlyFiltered.length ? monthlyFiltered.map(s => `
        <tr>
            <td>${escapeHtml(s.name)}</td>
            <td>${escapeHtml(s.mobile)}</td>
            <td>${escapeHtml(s.course_name)}</td>
            <td>${escapeHtml(s.month_label)}</td>
            <td><span class="badge badge-balance-pending">${formatMoney(s.due_amount)}</span></td>
        </tr>
    `).join('') : `<tr><td colspan="5" class="text-center text-muted py-4">No matching monthly dues.</td></tr>`;
    document.getElementById('pendingMonthlyTotal').textContent = 'Total: ' + formatMoney(monthlyFiltered.reduce((sum, s) => sum + parseFloat(s.due_amount), 0));
}

async function loadPendingReport() {
    const result = await apiGet(`${BASE_URL}/ajax/reports_pending.php`);

    if (!result.success) {
        pendingFixedCache = [];
        pendingMonthlyCache = [];
        document.getElementById('pendingFixedBody').innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(result.message)}</td></tr>`;
        document.getElementById('pendingMonthlyBody').innerHTML = '';
        return;
    }

    pendingFixedCache = result.data.fixed;
    pendingMonthlyCache = result.data.monthly;
    renderPendingTables();
}

function filterPendingReport() {
    renderPendingTables();
}

function renderPaidTables() {
    const query = (document.getElementById('paidSearchInput').value || '').trim().toLowerCase();

    const fixedFiltered = query ? paidFixedCache.filter(s => s.name.toLowerCase().includes(query)) : paidFixedCache;
    const monthlyFiltered = query ? paidMonthlyCache.filter(s => s.name.toLowerCase().includes(query)) : paidMonthlyCache;

    const fixedBody = document.getElementById('paidFixedBody');
    fixedBody.innerHTML = fixedFiltered.length ? fixedFiltered.map(s => `
        <tr>
            <td>${escapeHtml(s.name)}</td>
            <td>${escapeHtml(s.mobile)}</td>
            <td>${escapeHtml(s.course_name)}</td>
            <td>${formatMoney(s.course_fee)}</td>
            <td class="text-success fw-semibold">${formatMoney(s.total_paid)}</td>
        </tr>
    `).join('') : `<tr><td colspan="5" class="text-center text-muted py-4">No matching fully-paid students.</td></tr>`;
    document.getElementById('paidFixedTotal').textContent = 'Total: ' + formatMoney(fixedFiltered.reduce((sum, s) => sum + parseFloat(s.total_paid), 0));

    const monthlyBody = document.getElementById('paidMonthlyBody');
    monthlyBody.innerHTML = monthlyFiltered.length ? monthlyFiltered.map(s => `
        <tr>
            <td>${escapeHtml(s.name)}</td>
            <td>${escapeHtml(s.mobile)}</td>
            <td>${escapeHtml(s.course_name)}</td>
            <td>${formatMoney(s.monthly_fee)}/month</td>
            <td class="text-success fw-semibold">${formatMoney(s.total_paid)}</td>
        </tr>
    `).join('') : `<tr><td colspan="5" class="text-center text-muted py-4">No matching up-to-date monthly students.</td></tr>`;
    document.getElementById('paidMonthlyTotal').textContent = 'Total: ' + formatMoney(monthlyFiltered.reduce((sum, s) => sum + parseFloat(s.total_paid), 0));
}

async function loadPaidReport() {
    const result = await apiGet(`${BASE_URL}/ajax/reports_paid.php`);

    if (!result.success) {
        paidFixedCache = [];
        paidMonthlyCache = [];
        document.getElementById('paidFixedBody').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${escapeHtml(result.message)}</td></tr>`;
        document.getElementById('paidMonthlyBody').innerHTML = '';
        return;
    }

    paidFixedCache = result.data.fixed;
    paidMonthlyCache = result.data.monthly;
    renderPaidTables();
}

function filterPaidReport() {
    renderPaidTables();
}

async function loadStudentSelectForReport() {
    const result = await apiGet(`${BASE_URL}/ajax/students_list.php`);
    if (!result.success) return;

    // A student enrolled in multiple courses has one row per course in
    // students_list.php. Collapse to one dropdown entry per person —
    // identified by name+mobile together, since mobile alone could
    // wrongly merge two different family members sharing one number.
    const seenKeys = new Set();
    const uniqueStudents = [];
    result.data.forEach(s => {
        const key = s.name.trim().toLowerCase() + '|' + s.mobile;
        if (!seenKeys.has(key)) {
            seenKeys.add(key);
            uniqueStudents.push(s);
        }
    });

    const select = document.getElementById('studentReportSelect');
    select.innerHTML = '<option value="">-- Select Student --</option>' +
        uniqueStudents.map(s => `<option value="${s.id}">${escapeHtml(s.name)} - ${escapeHtml(s.mobile)}</option>`).join('');
}

async function loadStudentReport() {
    const studentId = document.getElementById('studentReportSelect').value;
    const container = document.getElementById('studentReportContainer');
    if (!studentId) {
        showToast('Please select a student first.', 'warning');
        return;
    }

    const result = await apiGet(`${BASE_URL}/ajax/reports_student.php?student_id=${studentId}`);
    if (!result.success) {
        container.innerHTML = `<div class="alert alert-danger">${escapeHtml(result.message)}</div>`;
        return;
    }

    const person = result.data.person;
    const enrollments = result.data.enrollments;
    const payments = result.data.payments;
    studentReportPaymentsCache = payments;

    const hasMultiple = enrollments.length > 1;

    const enrollmentCardsHtml = enrollments.map(en => {
        if (en.fee_type === 'monthly') {
            return `
            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                    <div><strong>${escapeHtml(en.course_name)}</strong><div class="small text-muted">Monthly Course</div></div>
                    <div>Monthly Fee: <strong>${formatMoney(en.course_fee)}</strong></div>
                    <div>Total Paid: <strong class="text-success">${formatMoney(en.total_paid)}</strong></div>
                </div>
                <div class="card-body p-0 border-top">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Month</th><th>Status</th></tr></thead>
                        <tbody>
                            ${en.monthly_fee_status.map(m => {
                                const badge = m.status === 'paid'
                                    ? '<span class="badge bg-success">Paid</span>'
                                    : m.status === 'upcoming'
                                        ? '<span class="badge bg-secondary">Upcoming</span>'
                                        : '<span class="badge bg-danger">Due</span>';
                                return `<tr><td>${escapeHtml(m.label)}</td><td>${badge}</td></tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
        }
        return `
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap justify-content-between gap-3">
                <div><strong>${escapeHtml(en.course_name)}</strong><div class="small text-muted">Fixed Course</div></div>
                <div>Total Fee: <strong>${formatMoney(en.total_fee)}</strong></div>
                <div>Paid: <strong class="text-success">${formatMoney(en.total_paid)}</strong></div>
                <div>Balance: <strong class="text-danger">${formatMoney(en.balance)}</strong></div>
            </div>
        </div>`;
    }).join('');

    container.innerHTML = `
        <div class="mb-3">
            <h5 class="mb-1">${escapeHtml(person.name)}</h5>
            <div class="text-muted small">${escapeHtml(person.mobile)}${hasMultiple ? ' &middot; Enrolled in ' + enrollments.length + ' courses' : ''}</div>
        </div>
        ${enrollmentCardsHtml}
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-receipt"></i> All Payments</span>
                <button class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Receipt No</th><th>Course</th><th>Date</th><th>Billing Month</th><th>Amount</th><th>Mode</th><th>Remarks</th><th class="text-end no-print">Actions</th></tr></thead>
                    <tbody>
                    ${payments.length ? payments.map(p => `
                        <tr>
                            <td><span class="badge bg-primary">${escapeHtml(p.receipt_no)}</span></td>
                            <td>${escapeHtml(p.course_name)}</td>
                            <td>${formatDate(p.payment_date)}</td>
                            <td>${p.billing_month_label ? escapeHtml(p.billing_month_label) : '-'}</td>
                            <td class="text-success fw-semibold">${formatMoney(p.amount)}</td>
                            <td>${escapeHtml(p.payment_mode)}</td>
                            <td>${escapeHtml(p.remarks || '-')}</td>
                            <td class="text-end no-print">
                                <button class="btn btn-sm btn-outline-secondary report-edit-payment-btn" data-id="${p.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger report-delete-payment-btn" data-id="${p.id}" data-receipt="${escapeHtml(p.receipt_no)}" title="Delete"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    `).join('') : `<tr><td colspan="8" class="text-center text-muted py-4">No payments recorded.</td></tr>`}
                    </tbody>
                </table>
            </div>
        </div>
    `;

    attachStudentReportRowEvents(person.name);
}

function attachStudentReportRowEvents(studentName) {
    document.querySelectorAll('.report-edit-payment-btn').forEach(btn => {
        btn.addEventListener('click', () => openReportEditPaymentModal(btn.dataset.id, studentName));
    });
    document.querySelectorAll('.report-delete-payment-btn').forEach(btn => {
        btn.addEventListener('click', () => deleteReportPayment(btn.dataset.id, btn.dataset.receipt));
    });
}

function openReportEditPaymentModal(id, studentName) {
    const payment = studentReportPaymentsCache.find(p => String(p.id) === String(id));
    if (!payment) {
        showToast('Payment not found in the current report.', 'error');
        return;
    }

    document.getElementById('reportEditPaymentId').value = payment.id;
    document.getElementById('reportEditPaymentReceiptNo').value = payment.receipt_no;
    document.getElementById('reportEditPaymentStudentName').value = studentName;
    document.getElementById('reportEditPaymentAmount').value = payment.amount;
    document.getElementById('reportEditPaymentDate').value = payment.payment_date;
    document.getElementById('reportEditPaymentMode').value = payment.payment_mode;
    document.getElementById('reportEditPaymentRemarks').value = payment.remarks || '';

    new bootstrap.Modal(document.getElementById('reportPaymentEditModal')).show();
}

async function deleteReportPayment(id, receiptNo) {
    const confirmed = await confirmAction(`Are you sure you want to delete payment "${receiptNo}"? This will reduce the student's total paid amount. This action cannot be undone.`);
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('id', id);

    const result = await apiPost(`${BASE_URL}/ajax/payments_delete.php`, formData);
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) {
        loadStudentReport();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dailyDateInput').value = today;
    document.getElementById('monthlyMonthInput').value = today.slice(0, 7);

    document.querySelectorAll('#reportTabs .nav-link').forEach(btn => {
        btn.addEventListener('click', () => switchReportTab(btn.dataset.tab));
    });

    document.getElementById('dailyDateInput').addEventListener('change', loadDailyReport);
    document.getElementById('monthlyMonthInput').addEventListener('change', loadMonthlyReport);

    document.getElementById('studentReportSelect').addEventListener('change', loadStudentReport);
    document.getElementById('studentReportSelect').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); loadStudentReport(); }
    });

    document.getElementById('pendingSearchInput').addEventListener('input', filterPendingReport);
    document.getElementById('pendingSearchInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); filterPendingReport(); }
    });
    document.getElementById('paidSearchInput').addEventListener('input', filterPaidReport);
    document.getElementById('paidSearchInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); filterPaidReport(); }
    });

    document.getElementById('reportPaymentEditForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('id', document.getElementById('reportEditPaymentId').value);
        formData.append('amount', document.getElementById('reportEditPaymentAmount').value);
        formData.append('payment_date', document.getElementById('reportEditPaymentDate').value);
        formData.append('payment_mode', document.getElementById('reportEditPaymentMode').value);
        formData.append('remarks', document.getElementById('reportEditPaymentRemarks').value);

        const submitBtn = document.getElementById('reportPaymentEditSubmitBtn');
        submitBtn.disabled = true;

        const result = await apiPost(`${BASE_URL}/ajax/payments_edit.php`, formData);
        submitBtn.disabled = false;

        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('reportPaymentEditModal')).hide();
            loadStudentReport();
        }
    });

    loadDailyReport();
    loadMonthlyReport();
    loadPendingReport();
    loadPaidReport();
    loadStudentSelectForReport();
});