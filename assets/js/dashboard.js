/**
 * dashboard.js - Loads and renders dashboard statistics via AJAX.
 */

let trendChartInstance = null;
let courseChartInstance = null;

async function loadDashboard() {
    const result = await apiGet(`${BASE_URL}/ajax/dashboard_stats.php`);
    if (!result.success) return;

    const d = result.data;

    document.getElementById('statTotalStudents').textContent = d.total_students;
    document.getElementById('statTotalCollection').textContent = formatMoney(d.total_collection);
    document.getElementById('statTodayCollection').textContent = formatMoney(d.today_collection);
    document.getElementById('statPendingFees').textContent = formatMoney(d.total_pending);

    // This month's collection + trend vs last month
    document.getElementById('statMonthCollection').textContent = formatMoney(d.month_collection);
    const trendEl = document.getElementById('statMonthTrend');
    if (trendEl) {
        if (d.month_trend_percent === null || d.month_trend_percent === undefined) {
            trendEl.innerHTML = `<span class="text-muted small">No data for last month</span>`;
        } else {
            const up = d.month_trend_percent >= 0;
            const icon = up ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
            const cls = up ? 'text-success' : 'text-danger';
            trendEl.innerHTML = `
                <div class="${cls} fw-semibold"><i class="bi ${icon}"></i> ${Math.abs(d.month_trend_percent)}%</div>
                <div class="small text-muted">vs last month</div>
            `;
        }
    }

    document.getElementById('statNewAdmissions').textContent = d.new_admissions_month;

    renderCourseOverview(d.course_overview);
    renderTrendChart(d.collection_trend);
    renderCourseChart(d.course_breakdown);
}

function renderCourseOverview(courses) {
    const body = document.getElementById('courseOverviewBody');
    if (!body) return;

    if (!courses.length) {
        body.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No courses yet.</td></tr>`;
        return;
    }

    body.innerHTML = courses.map(c => `
        <tr>
            <td>${escapeHtml(c.name)}</td>
            <td>${c.student_count}</td>
            <td class="text-success fw-semibold">${formatMoney(c.total_collected)}</td>
            <td class="${c.total_pending > 0 ? 'text-danger fw-semibold' : 'text-muted'}">${formatMoney(c.total_pending)}</td>
            <td>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: ${c.percent}%"></div>
                </div>
                <div class="small text-muted mt-1">${c.percent}%</div>
            </td>
        </tr>
    `).join('');
}

const PAYMENT_MODE_ICONS = {
    'Cash': 'bi-cash-stack',
    'UPI': 'bi-phone-fill',
    'Card': 'bi-credit-card-fill',
    'Bank Transfer': 'bi-bank',
    'Cheque': 'bi-postcard-fill',
};

function renderPaymentModes(modes) {
    const container = document.getElementById('paymentModeBody');
    if (!container) return;

    if (!modes.length) {
        container.innerHTML = `<div class="text-center text-muted py-4">No payments recorded this month.</div>`;
        return;
    }

    const grandTotal = modes.reduce((sum, m) => sum + parseFloat(m.total), 0);

    container.innerHTML = modes.map(m => {
        const pct = grandTotal > 0 ? Math.round((parseFloat(m.total) / grandTotal) * 100) : 0;
        const icon = PAYMENT_MODE_ICONS[m.payment_mode] || 'bi-cash';
        return `
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span><i class="bi ${icon} me-1 text-primary"></i>${escapeHtml(m.payment_mode)}
                        <span class="text-muted small">(${m.cnt})</span>
                    </span>
                    <span class="fw-semibold">${formatMoney(m.total)}</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: ${pct}%"></div>
                </div>
            </div>
        `;
    }).join('');
}
function renderTrendChart(trend) {
    const canvas = document.getElementById('collectionTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = trend.map(row => new Date(row.date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }));
    const values = trend.map(row => row.total);

    if (trendChartInstance) {
        trendChartInstance.data.labels = labels;
        trendChartInstance.data.datasets[0].data = values;
        trendChartInstance.update();
        return;
    }

    trendChartInstance = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Collection',
                data: values,
                borderColor: '#1565c0',
                backgroundColor: 'rgba(21,101,192,0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#1565c0',
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => formatMoney(ctx.parsed.y),
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (val) => formatMoney(val) },
                },
            },
        },
    });
}

function renderCourseChart(courseBreakdown) {
    const canvas = document.getElementById('courseBreakdownChart');
    const emptyEl = document.getElementById('courseBreakdownEmpty');
    if (!canvas || typeof Chart === 'undefined') return;

    if (!courseBreakdown.length) {
        canvas.classList.add('d-none');
        emptyEl?.classList.remove('d-none');
        return;
    }
    canvas.classList.remove('d-none');
    emptyEl?.classList.add('d-none');

    const palette = ['#1565c0', '#43a047', '#fb8c00', '#e53935', '#8e24aa', '#00897b'];
    const labels = courseBreakdown.map(c => c.name);
    const values = courseBreakdown.map(c => c.total);

    if (courseChartInstance) {
        courseChartInstance.data.labels = labels;
        courseChartInstance.data.datasets[0].data = values;
        courseChartInstance.update();
        return;
    }

    courseChartInstance = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: palette,
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.label}: ${formatMoney(ctx.parsed)}`,
                    },
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    document.getElementById('refreshDashboardBtn')?.addEventListener('click', loadDashboard);
});