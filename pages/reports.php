<?php // Reports Page ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="section-title mb-0"><i class="bi bi-bar-chart-line-fill"></i> Reports</h4>
</div>

<ul class="nav nav-tabs no-print mb-3" id="reportTabs">
    <li class="nav-item"><button class="nav-link active" data-tab="daily">Daily Collection</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="monthly">Monthly Collection</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="pending">Pending Fees</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="paid">Total Paid</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="student">Student-wise</button></li>
</ul>

<!-- Daily Report -->
<div class="report-panel" id="panel-daily">
    <div class="card mb-3 no-print">
        <div class="card-body d-flex gap-2 align-items-end flex-wrap">
            <div>
                <label class="form-label">Select Date</label>
                <input type="date" class="form-control" id="dailyDateInput">
            </div>
            <button class="btn btn-outline-secondary ms-auto" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <span><i class="bi bi-calendar-day"></i> Daily Collection</span>
            <span class="fw-bold" id="dailyTotal">Total: ₹0.00</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Receipt No</th><th>Student</th><th>Course</th><th>Billing</th><th>Amount</th><th>Mode</th></tr></thead>
                    <tbody id="dailyReportBody"><tr><td colspan="6" class="text-center text-muted py-4">Select a date to load its collection.</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Report -->
<div class="report-panel d-none" id="panel-monthly">
    <div class="card mb-3 no-print">
        <div class="card-body d-flex gap-2 align-items-end flex-wrap">
            <div>
                <label class="form-label">Select Month</label>
                <input type="month" class="form-control" id="monthlyMonthInput">
            </div>
            <button class="btn btn-outline-secondary ms-auto" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <span><i class="bi bi-calendar-month"></i> Monthly Collection</span>
                    <span class="fw-bold" id="monthlyTotal">Total: ₹0.00</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Receipt No</th><th>Student</th><th>Course</th><th>Billing</th><th>Amount</th><th>Date</th><th>Mode</th></tr></thead>
                            <tbody id="monthlyReportBody"><tr><td colspan="7" class="text-center text-muted py-4">Select a month to load its collection.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-journal-bookmark"></i> Course-wise Breakdown</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody id="monthlyCourseBreakdownBody"><tr><td class="text-center text-muted py-3">-</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Fee Report -->
<div class="report-panel d-none" id="panel-pending">
    <div class="card mb-3 no-print">
        <div class="card-body">
            <label class="form-label">Search by Name</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="pendingSearchInput" placeholder="Type a student name...">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between no-print-inline">
            <span><i class="bi bi-exclamation-diamond"></i> Fixed Course Balance</span>
            <span class="fw-bold" id="pendingFixedTotal">Total: ₹0.00</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Mobile</th><th>Course</th><th>Total Fee</th><th>Paid</th><th>Balance</th></tr></thead>
                    <tbody id="pendingFixedBody"><tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between no-print-inline">
            <span><i class="bi bi-calendar-x"></i> Monthly Course Due</span>
            <span class="fw-bold" id="pendingMonthlyTotal">Total: ₹0.00</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Mobile</th><th>Course</th><th>Month</th><th>Due</th></tr></thead>
                    <tbody id="pendingMonthlyBody"><tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
        <div class="card-footer no-print text-end">
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </div>
</div>

<!-- Total Paid Report -->
<div class="report-panel d-none" id="panel-paid">
    <div class="card mb-3 no-print">
        <div class="card-body">
            <label class="form-label">Search by Name</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="paidSearchInput" placeholder="Type a student name...">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between no-print-inline">
            <span><i class="bi bi-check-circle"></i> Fixed Courses — Fully Paid</span>
            <span class="fw-bold" id="paidFixedTotal">Total: ₹0.00</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Mobile</th><th>Course</th><th>Total Fee</th><th>Paid</th></tr></thead>
                    <tbody id="paidFixedBody"><tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between no-print-inline">
            <span><i class="bi bi-calendar-check"></i> Monthly Courses — Up to Date</span>
            <span class="fw-bold" id="paidMonthlyTotal">Total: ₹0.00</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Student</th><th>Mobile</th><th>Course</th><th>Monthly Fee</th><th>Total Paid</th></tr></thead>
                    <tbody id="paidMonthlyBody"><tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
        <div class="card-footer no-print text-end">
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        </div>
    </div>
</div>

<!-- Student-wise Report -->
<div class="report-panel d-none" id="panel-student">
    <div class="card mb-3 no-print">
        <div class="card-body">
            <label class="form-label">Select Student</label>
            <select class="form-select" id="studentReportSelect"><option value="">-- Select Student --</option></select>
        </div>
    </div>
    <div id="studentReportContainer"></div>
</div>

<!-- Edit Payment Modal (Student-wise Report) -->
<div class="modal fade" id="reportPaymentEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reportPaymentEditForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-fill"></i> Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reportEditPaymentId">
                    <div class="mb-3">
                        <label class="form-label">Receipt No</label>
                        <input type="text" class="form-control" id="reportEditPaymentReceiptNo" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" class="form-control" id="reportEditPaymentStudentName" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Amount</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="reportEditPaymentAmount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Payment Date</label>
                        <input type="date" class="form-control" id="reportEditPaymentDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Payment Mode</label>
                        <select class="form-select" id="reportEditPaymentMode" required>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <input type="text" class="form-control" id="reportEditPaymentRemarks">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="reportPaymentEditSubmitBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/reports.js?v=<?= time() ?>"></script>