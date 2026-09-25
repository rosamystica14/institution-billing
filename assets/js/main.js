/**
 * main.js - Global helper functions shared across all pages.
 * Institution Billing & Fee Management System
 */

const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || window.APP_BASE_URL || '';

// ---------------------------------------------------------
// Sidebar toggle (mobile)
// ---------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
    }
});

// ---------------------------------------------------------
// Loading Spinner
// ---------------------------------------------------------
function showLoader() {
    document.getElementById('globalLoader')?.classList.remove('d-none');
}
function hideLoader() {
    document.getElementById('globalLoader')?.classList.add('d-none');
}

// ---------------------------------------------------------
// Toast Notifications
// ---------------------------------------------------------
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) { alert(message); return; }

    const icons = {
        success: 'bi-check-circle-fill text-success',
        error: 'bi-x-circle-fill text-danger',
        warning: 'bi-exclamation-triangle-fill text-warning',
        info: 'bi-info-circle-fill text-primary',
    };
    const icon = icons[type] || icons.info;

    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center border-0 shadow';
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body"><i class="bi ${icon} me-2"></i>${message}</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// ---------------------------------------------------------
// Confirm Dialog (returns a Promise<boolean>)
// ---------------------------------------------------------
function confirmAction(message) {
    return new Promise((resolve) => {
        const modalEl = document.getElementById('confirmModal');
        const bodyEl = document.getElementById('confirmModalBody');
        const okBtn = document.getElementById('confirmModalOkBtn');
        bodyEl.textContent = message;

        const modal = new bootstrap.Modal(modalEl);

        const cleanup = (result) => {
            okBtn.removeEventListener('click', onOk);
            modalEl.removeEventListener('hidden.bs.modal', onCancel);
            resolve(result);
        };
        const onOk = () => { modal.hide(); cleanup(true); };
        const onCancel = () => cleanup(false);

        okBtn.addEventListener('click', onOk);
        modalEl.addEventListener('hidden.bs.modal', onCancel, { once: true });

        modal.show();
    });
}

// ---------------------------------------------------------
// Generic AJAX wrapper (fetch-based)
// ---------------------------------------------------------
async function apiRequest(url, options = {}) {
    showLoader();
    try {
        const response = await fetch(url, options);
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            throw new Error('Unexpected server response. Please check server logs.');
        }
        const result = await response.json();
        return result;
    } catch (err) {
        showToast(err.message || 'Something went wrong. Please try again.', 'error');
        return { success: false, message: err.message, data: [] };
    } finally {
        hideLoader();
    }
}

async function apiPost(url, formData) {
    return apiRequest(url, { method: 'POST', body: formData });
}

async function apiGet(url) {
    return apiRequest(url, { method: 'GET' });
}

// ---------------------------------------------------------
// Debounce helper (used for live search)
// ---------------------------------------------------------
function debounce(fn, delay = 350) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ---------------------------------------------------------
// Shared formatting helpers
// ---------------------------------------------------------
function formatMoney(value) {
    const n = parseFloat(value || 0);
    return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}
