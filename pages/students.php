<?php // Students Management Page ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="section-title mb-0"><i class="bi bi-people-fill"></i> Students</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#studentModal" id="addStudentBtn">
        <i class="bi bi-plus-lg"></i> Add Student
    </button>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="studentSearchInput" placeholder="Search by name, mobile, or course...">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Course</th>
                    <th>Admission Date</th>
                    <th>Total Fee</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody id="studentsTableBody">
                <tr><td colspan="9" class="text-center text-muted py-4">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="studentForm" enctype="multipart/form-data" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalTitle"><i class="bi bi-person-plus-fill"></i> Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="studentId">

                    <h6 class="text-primary fw-bold mb-2"><i class="bi bi-person-lines-fill"></i> Applicant Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Full Name</label>
                            <input type="text" class="form-control" name="name" id="studentName"
                                   pattern="[A-Za-z\s]+" maxlength="100"
                                   title="Only letters and spaces are allowed" required>
                            <div class="invalid-feedback">Full name can only contain letters and spaces.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father's / Guardian's Name</label>
                            <input type="text" class="form-control" name="father_name" id="studentFatherName"
                                   pattern="[A-Za-z\s]*" maxlength="100"
                                   title="Only letters and spaces are allowed">
                            <div class="invalid-feedback">Father's/Guardian's name can only contain letters and spaces.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" id="studentGender">
                                <option value="">-- Select --</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" id="studentDob">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Admission Date</label>
                            <input type="date" class="form-control" name="admission_date" id="studentAdmissionDate" required>
                            <div class="form-text" id="admissionDateMonthlyNote"></div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Present Address</label>
                            <textarea class="form-control" name="address" id="studentAddress" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Photo (optional)</label>
                            <input type="file" class="form-control" name="photo" id="studentPhoto" accept="image/*">
                            <img id="studentPhotoPreview" src="" class="student-avatar mt-2 d-none" alt="Preview">
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mb-2 mt-4"><i class="bi bi-telephone-fill"></i> Contact Details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Mobile Number</label>
                            <input type="tel" class="form-control" name="mobile" id="studentMobile"
                                   pattern="[0-9]{10}" maxlength="10" inputmode="numeric"
                                   title="Mobile number must be exactly 10 digits" required>
                            <div class="invalid-feedback">Mobile number must be exactly 10 digits.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">WhatsApp Number</label>
                            <input type="tel" class="form-control" name="whatsapp_number" id="studentWhatsapp"
                                   pattern="[0-9]{10}" maxlength="10" inputmode="numeric"
                                   title="WhatsApp number must be exactly 10 digits" required>
                            <div class="invalid-feedback">WhatsApp number must be exactly 10 digits.</div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="studentWhatsappSameAsMobile">
                                <label class="form-check-label small" for="studentWhatsappSameAsMobile">Same as Mobile Number</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Parent Contact No</label>
                            <input type="tel" class="form-control" name="parent_contact" id="studentParentContact"
                                   pattern="[0-9]{10}" maxlength="10" inputmode="numeric"
                                   title="Parent contact number must be exactly 10 digits">
                            <div class="invalid-feedback">Parent contact number must be exactly 10 digits.</div>
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mb-2 mt-4"><i class="bi bi-mortarboard-fill"></i> Qualification</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Qualification</label>
                            <input type="text" class="form-control" name="qualification" id="studentQualification"
                                   pattern="[A-Za-z\s.]*" maxlength="100"
                                   title="Only letters and spaces are allowed">
                            <div class="invalid-feedback">Qualification can only contain letters and spaces.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">School / College Name</label>
                            <input type="text" class="form-control" name="school_college" id="studentSchoolCollege">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <input type="text" class="form-control" name="qualification_year" id="studentQualificationYear"
                                   pattern="[0-9]{4}" maxlength="4" inputmode="numeric"
                                   title="Enter a valid 4-digit year">
                            <div class="invalid-feedback">Year must be exactly 4 digits (e.g. 2023).</div>
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mb-2 mt-4"><i class="bi bi-journal-bookmark-fill"></i> Course & Fees</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Course</label>
                            <select class="form-select" name="course_id" id="studentCourse" required>
                                <option value="">-- Select Course --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Timing</label>
                            <select class="form-select" id="studentTimingSelect">
                                <option value="">-- Select Slot --</option>
                                <option value="Morning (7 AM - 10 AM)">Morning (8 AM - 10 AM)</option>
                                <option value="Morning (10 AM - 1 PM)">Morning (10 AM - 12 PM)</option>
                                <option value="Afternoon (1 PM - 4 PM)">Afternoon (12 PM - 2 PM)</option>
                                <option value="Evening (4 PM - 7 PM)">Afternoon (2 PM - 4 PM)</option>
                                <option value="Evening (7 PM - 9 PM)">Evening (4 PM - 6 PM)</option>
                                <option value="Evening (7 PM - 9 PM)">Evening (6 PM - 8 PM)</option>
                                <option value="__custom__">Other (specify)</option>
                            </select>
                            <input type="text" class="form-control mt-2 d-none" name="timing" id="studentTiming"
                                   placeholder="Enter custom timing" maxlength="50">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference</label>
                            <select class="form-select" name="reference_source" id="studentReferenceSource">
                                <option value="">-- Select --</option>
                                <option value="Friend">Friend</option>
                                <option value="Notice">Notice</option>
                                <option value="Online">Online</option>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- FIXED-FEE course fields (discount/custom amount override) -->
                    <div class="row g-3 mt-1" id="fixedFeeSection">
                        <div class="col-md-4">
                            <label class="form-label">Default Course Fee</label>
                            <input type="text" class="form-control bg-light" id="studentCourseDefaultFee" readonly>
                        </div>
                        <div class="col-md-4 d-flex align-items-center gap-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="discount_enabled" value="1" id="studentDiscountEnabled">
                                <label class="form-check-label" for="studentDiscountEnabled">Apply Discount</label>
                            </div>
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="custom_amount_enabled" value="1" id="studentCustomAmountEnabled">
                                <label class="form-check-label" for="studentCustomAmountEnabled">Custom Amount</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-none" id="studentDiscountPercentWrap">
                            <label class="form-label">Discount %</label>
                            <input type="number" class="form-control" name="discount_percent" id="studentDiscountPercent" min="0" max="100" step="0.01" value="0">
                        </div>
                        <div class="col-md-4 d-none" id="studentCustomAmountWrap">
                            <label class="form-label">Custom Amount (₹)</label>
                            <input type="number" class="form-control" name="custom_amount" id="studentCustomAmount" min="0" step="0.01" placeholder="Enter custom fee amount">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Final Course Fee</label>
                            <input type="text" class="form-control fw-bold text-primary bg-light" id="studentFinalFee" readonly>
                        </div>
                    </div>

                    <!-- MONTHLY-FEE course fields (Typewriting etc.) -->
                    <div class="row g-3 mt-1 d-none" id="monthlyFeeSection">
                        <div class="col-md-4">
                            <label class="form-label">Billing Type</label>
                            <input type="text" class="form-control bg-light" value="Monthly" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Monthly Fee</label>
                            <input type="text" class="form-control fw-bold text-primary bg-light" id="studentMonthlyFee" readonly>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-text mb-2">Billing starts from the Admission Date above. This course is billed monthly with no fixed end date.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="studentSaveBtn"><i class="bi bi-check-lg"></i> Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/students.js?v=<?= time() ?>"></script>