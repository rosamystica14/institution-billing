<?php $preselectStudentId = (int)($_GET['student_id'] ?? 0); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="section-title mb-0"><i class="bi bi-cash-coin"></i> Receive Fee Payment</h4>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil-square"></i> Payment Form</div>
            <div class="card-body">
                <form id="paymentForm">
                    <div class="mb-3">
                        <label class="form-label required">Select Student</label>
                        <select class="form-select" id="paymentStudentSelect" required>
                            <option value="">-- Select Student --</option>
                        </select>
                    </div>

                    <div id="studentBalanceInfo" class="alert alert-light border d-none mb-3"></div>
                    <div id="monthlyFeeStatusInfo" class="d-none mb-3">
    <div class="alert alert-light border mb-2">
        <div class="d-flex justify-content-between">
            <strong id="monthlyFeeStatusCourseName"></strong>
            <span>Monthly Fee: <span id="monthlyFeeStatusRate" class="fw-bold"></span></span>
        </div>
    </div>
    <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered mb-0">
            <thead><tr><th>Month</th><th>Status</th></tr></thead>
            <tbody id="monthlyFeeStatusTableBody"></tbody>
        </table>
    </div>
</div>

<div class="mb-3 d-none" id="billingMonthWrap">
    <label class="form-label required">Billing Month</label>
    <select class="form-select" id="paymentBillingMonth" required></select>
</div>

                    <div class="mb-3">
                        <label class="form-label required">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="paymentAmount" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Payment Date</label>
                            <input type="date" class="form-control" id="paymentDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Payment Mode</label>
                            <select class="form-select" id="paymentMode" required>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" id="paymentRemarks" rows="2" placeholder="Optional notes"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="paymentSubmitBtn">
                        <i class="bi bi-check-circle-fill"></i> Receive Payment & Generate Receipt
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-check"></i> Recent Payments</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr><th>Receipt</th><th>Student</th><th>Amount</th><th>Date</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody id="recentPaymentsListBody">
                        <tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="preselectStudentId" value="<?= $preselectStudentId ?>">

<!-- Edit Payment Modal -->
<div class="modal fade" id="paymentEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="paymentEditForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-fill"></i> Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editPaymentId">

                    <div class="mb-3">
                        <label class="form-label">Receipt No</label>
                        <input type="text" class="form-control bg-light" id="editPaymentReceiptNo" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <input type="text" class="form-control bg-light" id="editPaymentStudentName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="editPaymentAmount" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Payment Date</label>
                            <input type="date" class="form-control" id="editPaymentDate" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Payment Mode</label>
                            <select class="form-select" id="editPaymentMode" required>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" id="editPaymentRemarks" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="paymentEditSubmitBtn"><i class="bi bi-check-lg"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/payments.js?v=<?= time() ?>"></script>