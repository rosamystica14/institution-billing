/**
 * students.js - Student CRUD, search, and photo upload handling.
 */

let allCourses = [];

async function loadCoursesIntoSelect() {
    const result = await apiGet(`${BASE_URL}/ajax/courses_list.php`);
    if (!result.success) return;
    allCourses = result.data;

    const select = document.getElementById('studentCourse');
    select.innerHTML = '<option value="">-- Select Course --</option>' +
        allCourses.map(c => {
            const feeLabel = c.fee_type === 'monthly' ? `${formatMoney(c.fee)}/month` : formatMoney(c.fee);
            return `<option value="${c.id}">${escapeHtml(c.name)} (${feeLabel})</option>`;
        }).join('');
}

/**
 * Shows/hides the Fixed-fee section (discount/custom override) vs the
 * Monthly-fee section (Typewriting etc.) based on the selected course's
 * fee_type. Also force-clears any discount/custom state when switching
 * to a monthly course, since those overrides don't apply there.
 */
function updateStudentFeeSectionUI() {
    const courseId = document.getElementById('studentCourse').value;
    const course = allCourses.find(c => String(c.id) === String(courseId));
    const isMonthly = course ? course.fee_type === 'monthly' : false;

    document.getElementById('fixedFeeSection').classList.toggle('d-none', isMonthly);
    document.getElementById('monthlyFeeSection').classList.toggle('d-none', !isMonthly);
    document.getElementById('admissionDateMonthlyNote').textContent =
        isMonthly ? 'Also used as the monthly billing start date.' : '';

    if (isMonthly) {
        // Discount/custom amount don't apply to monthly billing yet.
        document.getElementById('studentDiscountEnabled').checked = false;
        document.getElementById('studentCustomAmountEnabled').checked = false;
        document.getElementById('studentDiscountPercentWrap').classList.add('d-none');
        document.getElementById('studentCustomAmountWrap').classList.add('d-none');
        document.getElementById('studentMonthlyFee').value = course ? `${formatMoney(course.fee)}/month` : '';
    }

    recalcStudentFee();
}

/**
 * Reads the selected course's default fee + the discount/custom-amount
 * toggle and recalculates the Final Course Fee field live in the form.
 * Discount and Custom Amount are mutually exclusive.
 * No-op (besides clearing the fixed-fee display) for monthly courses —
 * see updateStudentFeeSectionUI() for the monthly fee display.
 */
function recalcStudentFee() {
    const courseId = document.getElementById('studentCourse').value;
    const course = allCourses.find(c => String(c.id) === String(courseId));
    const isMonthly = course ? course.fee_type === 'monthly' : false;
    const defaultFee = course ? parseFloat(course.fee) : 0;

    if (isMonthly) {
        document.getElementById('studentCourseDefaultFee').value = '';
        document.getElementById('studentFinalFee').value = '';
        return;
    }

    document.getElementById('studentCourseDefaultFee').value = course ? formatMoney(defaultFee) : '';

    const discountEnabled = document.getElementById('studentDiscountEnabled').checked;
    const customEnabled = document.getElementById('studentCustomAmountEnabled').checked;

    const percentWrap = document.getElementById('studentDiscountPercentWrap');
    const customWrap = document.getElementById('studentCustomAmountWrap');
    percentWrap.classList.toggle('d-none', !discountEnabled);
    customWrap.classList.toggle('d-none', !customEnabled);

    let finalFee = defaultFee;
    if (customEnabled) {
        finalFee = parseFloat(document.getElementById('studentCustomAmount').value) || 0;
    } else if (discountEnabled) {
        let percent = parseFloat(document.getElementById('studentDiscountPercent').value) || 0;
        percent = Math.max(0, Math.min(100, percent));
        finalFee = defaultFee - (defaultFee * (percent / 100));
    }

    document.getElementById('studentFinalFee').value = course ? formatMoney(finalFee) : '';
}

/**
 * Enforces that "Apply Discount" and "Custom Amount" can't both be checked.
 */
function handleDiscountToggle() {
    if (document.getElementById('studentDiscountEnabled').checked) {
        document.getElementById('studentCustomAmountEnabled').checked = false;
    }
    recalcStudentFee();
}
function handleCustomAmountToggle() {
    if (document.getElementById('studentCustomAmountEnabled').checked) {
        document.getElementById('studentDiscountEnabled').checked = false;
    }
    recalcStudentFee();
}

/**
 * WhatsApp "Same as Mobile Number" checkbox handling.
 */
function handleWhatsappSameAsMobile() {
    const same = document.getElementById('studentWhatsappSameAsMobile').checked;
    const whatsappInput = document.getElementById('studentWhatsapp');
    const mobileInput = document.getElementById('studentMobile');

    whatsappInput.readOnly = same;
    if (same) {
        whatsappInput.value = mobileInput.value;
    }
}

/**
 * Timing select -> hidden/visible text input handling.
 * The actual value submitted is always the text input named "timing".
 */
function handleTimingSelectChange() {
    const select = document.getElementById('studentTimingSelect');
    const input = document.getElementById('studentTiming');

    if (select.value === '__custom__') {
        input.value = '';
        input.classList.remove('d-none');
        input.focus();
    } else {
        input.classList.add('d-none');
        input.value = select.value;
    }
}

/**
 * Restores the Timing select/input pair from a stored timing string
 * (used when opening the Edit Student modal).
 */
function setTimingValue(value) {
    const select = document.getElementById('studentTimingSelect');
    const input = document.getElementById('studentTiming');
    const presetOption = Array.from(select.options).find(o => o.value === value);

    if (!value) {
        select.value = '';
        input.value = '';
        input.classList.add('d-none');
    } else if (presetOption) {
        select.value = value;
        input.value = value;
        input.classList.add('d-none');
    } else {
        select.value = '__custom__';
        input.value = value;
        input.classList.remove('d-none');
    }
}

/**
 * Applies Bootstrap-style client-side validation to the student form.
 * Returns true if valid, false otherwise (and marks invalid fields).
 */
function validateStudentForm(form) {
    let valid = form.checkValidity();

    // Custom Amount must be > 0 when that mode is enabled (fixed-fee courses only)
    const customEnabled = document.getElementById('studentCustomAmountEnabled').checked;
    const customAmountInput = document.getElementById('studentCustomAmount');
    if (customEnabled && (!customAmountInput.value || parseFloat(customAmountInput.value) <= 0)) {
        customAmountInput.setCustomValidity('Enter a custom amount greater than 0.');
        valid = false;
    } else {
        customAmountInput.setCustomValidity('');
    }

    form.classList.add('was-validated');
    return valid;
}

async function loadStudents(search = '') {
    const url = `${BASE_URL}/ajax/students_list.php` + (search ? `?search=${encodeURIComponent(search)}` : '');
    const result = await apiGet(url);
    const tbody = document.getElementById('studentsTableBody');

    if (!result.success) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${escapeHtml(result.message)}</td></tr>`;
        return;
    }

    if (!result.data.length) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No students found.</td></tr>`;
        return;
    }

    tbody.innerHTML = result.data.map(s => {
        const balanceBadge = s.balance > 0
            ? `<span class="badge badge-balance-pending">${formatMoney(s.balance)}</span>`
            : `<span class="badge badge-balance-zero">Paid Full</span>`;
        const photoSrc = s.photo ? `${BASE_URL}/assets/uploads/photos/${s.photo}` : `${BASE_URL}/assets/img/default-avatar.svg`;

        return `
        <tr>
            <td><img src="${photoSrc}" class="student-avatar" onerror="this.src='${BASE_URL}/assets/img/default-avatar.svg'" alt=""></td>
            <td><a href="${BASE_URL}/index.php?page=student_details&id=${s.id}" class="fw-semibold text-decoration-none">${escapeHtml(s.name)}</a></td>
            <td>${escapeHtml(s.mobile)}</td>
            <td>${escapeHtml(s.course_name)}</td>
            <td>${formatDate(s.admission_date)}</td>
            <td>${formatMoney(s.course_fee)}</td>
            <td class="text-success">${formatMoney(s.total_paid)}</td>
            <td>${balanceBadge}</td>
            <td class="text-end">
                <a href="${BASE_URL}/index.php?page=student_details&id=${s.id}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                <a href="${BASE_URL}/index.php?page=application&id=${s.id}" class="btn btn-sm btn-outline-success" title="View Application"><i class="bi bi-file-earmark-text"></i></a>
                <button class="btn btn-sm btn-outline-secondary edit-student-btn" data-id="${s.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-student-btn" data-id="${s.id}" data-name="${escapeHtml(s.name)}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
    }).join('');

    attachRowEvents();
}

function attachRowEvents() {
    document.querySelectorAll('.edit-student-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditStudentModal(btn.dataset.id));
    });
    document.querySelectorAll('.delete-student-btn').forEach(btn => {
        btn.addEventListener('click', () => deleteStudent(btn.dataset.id, btn.dataset.name));
    });
}

async function openEditStudentModal(id) {
    const result = await apiGet(`${BASE_URL}/ajax/students_get.php?id=${id}`);
    if (!result.success) {
        showToast(result.message, 'error');
        return;
    }
    const s = result.data.student;

    document.getElementById('studentModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> Edit Student';
    document.getElementById('studentId').value = s.id;
    document.getElementById('studentName').value = s.name;
    document.getElementById('studentFatherName').value = s.father_name || '';
    document.getElementById('studentGender').value = s.gender || '';
    document.getElementById('studentDob').value = s.dob || '';
    document.getElementById('studentMobile').value = s.mobile;
    document.getElementById('studentWhatsapp').value = s.whatsapp_number || '';
    document.getElementById('studentWhatsappSameAsMobile').checked = !!s.whatsapp_number && s.whatsapp_number === s.mobile;
    document.getElementById('studentWhatsapp').readOnly = document.getElementById('studentWhatsappSameAsMobile').checked;
    document.getElementById('studentParentContact').value = s.parent_contact || '';
    document.getElementById('studentQualification').value = s.qualification || '';
    document.getElementById('studentSchoolCollege').value = s.school_college || '';
    document.getElementById('studentQualificationYear').value = s.qualification_year || '';
    document.getElementById('studentCourse').value = s.course_id;
    setTimingValue(s.timing || '');
    document.getElementById('studentReferenceSource').value = s.reference_source || '';
    document.getElementById('studentAdmissionDate').value = s.admission_date;
    document.getElementById('studentAddress').value = s.address || '';

    const feeMode = s.fee_mode || 'default';
    const discountPercent = parseFloat(s.discount_percent) || 0;
    document.getElementById('studentDiscountEnabled').checked = feeMode === 'discount';
    document.getElementById('studentDiscountPercent').value = discountPercent;
    document.getElementById('studentCustomAmountEnabled').checked = feeMode === 'custom';
    document.getElementById('studentCustomAmount').value = feeMode === 'custom' ? (parseFloat(s.final_fee) || 0) : '';

    const preview = document.getElementById('studentPhotoPreview');
    if (s.photo) {
        preview.src = `${BASE_URL}/assets/uploads/photos/${s.photo}`;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }

    // Must run after course_id/discount fields are populated above,
    // since it reads them when deciding what to show/hide.
    updateStudentFeeSectionUI();
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}

async function deleteStudent(id, name) {
    const confirmed = await confirmAction(`Are you sure you want to delete student "${name}"? This will also delete all their payment records. This action cannot be undone.`);
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('id', id);

    const result = await apiPost(`${BASE_URL}/ajax/students_delete.php`, formData);
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) loadStudents(document.getElementById('studentSearchInput').value);
}

function resetStudentForm() {
    const form = document.getElementById('studentForm');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('studentId').value = '';
    document.getElementById('studentModalTitle').innerHTML = '<i class="bi bi-person-plus-fill"></i> Add Student';
    document.getElementById('studentPhotoPreview').classList.add('d-none');
    document.getElementById('studentDiscountPercentWrap').classList.add('d-none');
    document.getElementById('studentCustomAmountWrap').classList.add('d-none');
    document.getElementById('studentCourseDefaultFee').value = '';
    document.getElementById('studentFinalFee').value = '';
    document.getElementById('studentMonthlyFee').value = '';
    document.getElementById('admissionDateMonthlyNote').textContent = '';
    document.getElementById('fixedFeeSection').classList.remove('d-none');
    document.getElementById('monthlyFeeSection').classList.add('d-none');
    document.getElementById('studentWhatsapp').readOnly = false;
    document.getElementById('studentWhatsappSameAsMobile').checked = false;
    setTimingValue('');
}

document.addEventListener('DOMContentLoaded', () => {
    loadCoursesIntoSelect();
    loadStudents();

    document.getElementById('addStudentBtn').addEventListener('click', resetStudentForm);

    document.getElementById('studentModal').addEventListener('hidden.bs.modal', resetStudentForm);

    document.getElementById('studentSearchInput').addEventListener('input', debounce((e) => {
        loadStudents(e.target.value);
    }, 350));

    document.getElementById('studentCourse').addEventListener('change', updateStudentFeeSectionUI);
    document.getElementById('studentDiscountEnabled').addEventListener('change', handleDiscountToggle);
    document.getElementById('studentDiscountPercent').addEventListener('input', recalcStudentFee);
    document.getElementById('studentCustomAmountEnabled').addEventListener('change', handleCustomAmountToggle);
    document.getElementById('studentCustomAmount').addEventListener('input', recalcStudentFee);

    document.getElementById('studentTimingSelect').addEventListener('change', handleTimingSelectChange);

    document.getElementById('studentWhatsappSameAsMobile').addEventListener('change', handleWhatsappSameAsMobile);
    document.getElementById('studentMobile').addEventListener('input', (e) => {
        // digits only
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
        if (document.getElementById('studentWhatsappSameAsMobile').checked) {
            document.getElementById('studentWhatsapp').value = e.target.value;
        }
    });
    document.getElementById('studentWhatsapp').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
    });
    document.getElementById('studentParentContact').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
    });
    document.getElementById('studentQualificationYear').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
    });
    document.getElementById('studentName').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, '');
    });
    document.getElementById('studentFatherName').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, '');
    });
    document.getElementById('studentQualification').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^A-Za-z\s.]/g, '');
    });

    document.getElementById('studentPhoto').addEventListener('change', (e) => {
        const file = e.target.files[0];
        const preview = document.getElementById('studentPhotoPreview');
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
        }
    });

    document.getElementById('studentForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;

        if (!validateStudentForm(form)) {
            showToast('Please fix the highlighted fields before saving.', 'error');
            return;
        }

        const id = document.getElementById('studentId').value;
        const formData = new FormData(form);
        const url = id ? `${BASE_URL}/ajax/students_edit.php` : `${BASE_URL}/ajax/students_add.php`;

        const saveBtn = document.getElementById('studentSaveBtn');
        saveBtn.disabled = true;

        const result = await apiPost(url, formData);
        saveBtn.disabled = false;

        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('studentModal')).hide();

            if (!id) {
                window.location.href = `${BASE_URL}/index.php?page=application&id=${result.data.id}`;
                return;
            }

            loadStudents(document.getElementById('studentSearchInput').value);
        }
    });
});