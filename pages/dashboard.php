<?php // Dashboard Page - stats are loaded via AJAX (dashboard.js) ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="section-title mb-0"><i class="bi bi-speedometer2"></i> Dashboard</h4>
    <button class="btn btn-sm btn-outline-primary" id="refreshDashboardBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-blue">
            <i class="bi bi-people-fill stat-icon"></i>
            <div class="label">Total Students</div>
            <h3 id="statTotalStudents">--</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-green">
            <i class="bi bi-cash-stack stat-icon"></i>
            <div class="label">Total Collection</div>
            <h3 id="statTotalCollection">--</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-orange">
            <i class="bi bi-calendar-check-fill stat-icon"></i>
            <div class="label">Today's Collection</div>
            <h3 id="statTodayCollection">--</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card bg-grad-red">
            <i class="bi bi-exclamation-diamond-fill stat-icon"></i>
            <div class="label">Pending Fees</div>
            <h3 id="statPendingFees">--</h3>
        </div>
    </div>
</div>

<!-- Month trend + admissions -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">This Month's Collection</div>
                    <h4 class="mb-0" id="statMonthCollection">--</h4>
                </div>
                <div id="statMonthTrend" class="text-end"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">New Admissions This Month</div>
                    <h4 class="mb-0" id="statNewAdmissions">--</h4>
                </div>
                <i class="bi bi-person-plus-fill fs-2 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-graph-up"></i> Collection Trend (Last 14 Days)</div>
            <div class="card-body">
                <canvas id="collectionTrendChart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart-fill"></i> Collection by Course</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="courseBreakdownChart"></canvas>
                <div id="courseBreakdownEmpty" class="text-muted text-center py-4 d-none">No collection data yet.</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Course-wise Overview -->
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-journal-bookmark-fill"></i> Course-wise Overview</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Course</th>
                            <th>Students</th>
                            <th>Collected</th>
                            <th>Pending</th>
                            <th style="width:120px;">Collected %</th>
                        </tr>
                        </thead>
                        <tbody id="courseOverviewBody">
                        <tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/dashboard.js?v=<?= filemtime(__DIR__ . '/../assets/js/dashboard.js') ?>"></script>