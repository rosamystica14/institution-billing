/**
 * courses.js - Course CRUD via AJAX. Fixed + Monthly fee types.
 */

async function loadCourses() {
    const result = await apiGet(`${BASE_URL}/ajax/courses_list.php`);
    const tbody = document.getElementById('coursesTableBody');

    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(result.message)}</td></tr>`;
        return;
    }

    if (!result.data.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No courses added yet.</td></tr>`;
        return;
    }

    tbody.innerHTML = result.data.map(c => {
        const isMonthly = c.fee_type === 'monthly';
        const feeTypeBadge = isMonthly
            ? '<span class="badge bg-info-subtle text-info-emphasis">Monthly</span>'
            : '<span class="badge bg-secondary-subtle text-secondary-emphasis">Fixed</span>';
        const feeDisplay = isMonthly ? `${formatMoney(c.fee)}/month` : formatMoney(c.fee);
        const durationDisplay = isMonthly ? 'Ongoing' : (c.duration || '-');

        return `
        <tr>
            <td class="fw-semibold">${escapeHtml(c.name)}</td>
            <td>${feeTypeBadge}</td>
            <td>${feeDisplay}</td>
            <td>${escapeHtml(durationDisplay)}</td>
            <td><span class="badge bg-secondary">${c.student_count}</span></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary edit-course-btn"
                    data-id="${c.id}" data-name="${escapeHtml(c.name)}" data-fee="${c.fee}"
                    data-fee-type="${c.fee_type}" data-duration="${escapeHtml(c.duration || '')}"
                    title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-course-btn" data-id="${c.id}" data-name="${escapeHtml(c.name)}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
        `;
    }).join('');

    document.querySelectorAll('.edit-course-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditCourseModal(btn.dataset));
    });
    document.querySelectorAll('.delete-course-btn').forEach(btn => {
        btn.addEventListener('click', () => deleteCourse(btn.dataset.id, btn.dataset.name));
    });
}

/**
 * Toggle the fee/duration fields based on the selected Fee Type.
 * Monthly courses: duration is locked to "Ongoing", fee label becomes "Monthly Fee".
 */
function updateCourseFeeTypeUI() {
    const feeType = document.getElementById('courseFeeType').value;
    const feeLabel = document.getElementById('courseFeeLabel');
    const feeHelp = document.getElementById('courseFeeHelp');
    const durationInput = document.getElementById('courseDuration');
    const durationHelp = document.getElementById('courseDurationHelp');

    if (feeType === 'monthly') {
        feeLabel.textContent = 'Monthly Fee';
        feeHelp.textContent = 'Amount charged per month.';
        durationInput.value = 'Ongoing';
        durationInput.readOnly = true;
        durationHelp.textContent = 'Monthly courses run continuously — duration is fixed to "Ongoing".';
    } else {
        feeLabel.textContent = 'Course Fee';
        feeHelp.textContent = 'Total fee for the full course.';
        durationInput.readOnly = false;
        if (durationInput.value === 'Ongoing') durationInput.value = '';
        durationHelp.textContent = '';
    }
}

function openEditCourseModal(data) {
    document.getElementById('courseModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Edit Course';
    document.getElementById('courseId').value = data.id;
    document.getElementById('courseName').value = data.name;
    document.getElementById('courseFeeType').value = data.feeType || 'fixed';
    document.getElementById('courseFee').value = data.fee;
    document.getElementById('courseDuration').value = data.duration;
    updateCourseFeeTypeUI();
    new bootstrap.Modal(document.getElementById('courseModal')).show();
}

async function deleteCourse(id, name) {
    const confirmed = await confirmAction(`Are you sure you want to delete the course "${name}"?`);
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('id', id);
    const result = await apiPost(`${BASE_URL}/ajax/courses_delete.php`, formData);
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) loadCourses();
}

function resetCourseForm() {
    document.getElementById('courseForm').reset();
    document.getElementById('courseId').value = '';
    document.getElementById('courseFeeType').value = 'fixed';
    document.getElementById('courseModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> Add Course';
    updateCourseFeeTypeUI();
}

document.addEventListener('DOMContentLoaded', () => {
    loadCourses();

    document.getElementById('addCourseBtn').addEventListener('click', resetCourseForm);
    document.getElementById('courseModal').addEventListener('hidden.bs.modal', resetCourseForm);
    document.getElementById('courseFeeType').addEventListener('change', updateCourseFeeTypeUI);
    updateCourseFeeTypeUI();

    document.getElementById('courseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('courseId').value;
        const formData = new FormData(e.target);
        const url = id ? `${BASE_URL}/ajax/courses_edit.php` : `${BASE_URL}/ajax/courses_add.php`;

        const result = await apiPost(url, formData);
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('courseModal')).hide();
            loadCourses();
        }
    });
});