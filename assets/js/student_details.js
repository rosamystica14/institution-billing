/**
 * student_details.js - Renders a single student's profile + full payment history.
 */
function relationPrefix(gender) {
    const g = (gender || '').trim().toLowerCase();
    if (g === 'female') return 'D/O';
    if (g === 'male') return 'S/O';
    return 'C/O'; // fallback for "Other" / unspecified — gender-neutral "Care of"
}
async function loadStudentDetails() {
    const id = document.getElementById('detailsStudentId').value;
    const result = await apiGet(`${BASE_URL}/ajax/students_get.php?id=${id}`);
    const container = document.getElementById('studentDetailsContainer');

    if (!result.success) {
        container.innerHTML = `<div class="alert alert-danger">${escapeHtml(result.message)}</div>`;
        return;
    }

    const s = result.data.student;
    const payments = result.data.payments;
    const photoSrc = s.photo ? `${BASE_URL}/assets/uploads/photos/${s.photo}` : `${BASE_URL}/assets/img/default-avatar.svg`;
    const balanceBadge = s.balance > 0
        ? `<span class="badge badge-balance-pending fs-6">${formatMoney(s.balance)} Pending</span>`
        : `<span class="badge badge-balance-zero fs-6">Fully Paid</span>`;

    container.innerHTML = `
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card text-center">
                <div class="card-body">
                    <img src="${photoSrc}" class="student-avatar-lg mb-3" onerror="this.src='${BASE_URL}/assets/img/default-avatar.svg'" alt="">
                    <h5 class="mb-0">${escapeHtml(s.name)}</h5>
                    <div class="text-muted small mb-2">${relationPrefix(s.gender)} ${escapeHtml(s.father_name || '-')}</div>
                    ${balanceBadge}
                    <hr>
                    <table class="table table-borderless table-sm text-start mb-0">
                        <tr><th>Mobile</th><td>${escapeHtml(s.mobile)}</td></tr>
                        <tr><th>WhatsApp</th><td>${escapeHtml(s.whatsapp_number || s.mobile)}</td></tr>
                        <tr><th>Course</th><td>${escapeHtml(s.course_name)}</td></tr>
                        <tr><th>Duration</th><td>${escapeHtml(s.course_duration || '-')}</td></tr>
                        <tr><th>Admission</th><td>${formatDate(s.admission_date)}</td></tr>
                        <tr><th>Address</th><td>${escapeHtml(s.address || '-')}</td></tr>
                    </table>
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-4"><div class="stat-card bg-grad-blue p-2"><div class="label small">Total Fee</div><div class="fw-bold">${formatMoney(s.course_fee)}</div></div></div>
                <div class="col-4"><div class="stat-card bg-grad-green p-2"><div class="label small">Paid</div><div class="fw-bold">${formatMoney(s.total_paid)}</div></div></div>
                <div class="col-4"><div class="stat-card bg-grad-red p-2"><div class="label small">Balance</div><div class="fw-bold">${formatMoney(s.balance)}</div></div></div>
            </div>

            <a href="${BASE_URL}/index.php?page=payments&student_id=${s.id}" class="btn btn-primary w-100 mt-3">
                <i class="bi bi-cash-coin"></i> Receive Fee Payment
            </a>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-receipt"></i> Payment History</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Mode</th>
                                <th>Remarks</th>
                                <th class="text-end">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            ${payments.length ? payments.map(p => `
                                <tr>
                                    <td><span class="badge bg-primary">${escapeHtml(p.receipt_no)}</span></td>
                                    <td>${formatDate(p.payment_date)}</td>
                                    <td class="fw-semibold text-success">${formatMoney(p.amount)}</td>
                                    <td>${escapeHtml(p.payment_mode)}</td>
                                    <td>${escapeHtml(p.remarks || '-')}</td>
                                    <td class="text-end">
                                        <a href="${BASE_URL}/index.php?page=receipt&payment_id=${p.id}" class="btn btn-sm btn-outline-primary" title="View Receipt"><i class="bi bi-file-earmark-text"></i></a>
                                    </td>
                                </tr>
                            `).join('') : `<tr><td colspan="6" class="text-center text-muted py-4">No payments recorded yet.</td></tr>`}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
}

document.addEventListener('DOMContentLoaded', loadStudentDetails);
