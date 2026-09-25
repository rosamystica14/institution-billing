<?php // Courses Management Page ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="section-title mb-0"><i class="bi bi-journal-bookmark-fill"></i> Courses</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#courseModal" id="addCourseBtn">
        <i class="bi bi-plus-lg"></i> Add Course
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Fee Type</th>
                    <th>Fee</th>
                    <th>Duration</th>
                    <th>Enrolled Students</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody id="coursesTableBody">
                <tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Course Modal -->
<div class="modal fade" id="courseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="courseForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="courseModalTitle"><i class="bi bi-plus-circle-fill"></i> Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="courseId">

                    <div class="mb-3">
                        <label class="form-label required">Course Name</label>
                        <input type="text" class="form-control" name="name" id="courseName" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Fee Type</label>
                        <select class="form-select" name="fee_type" id="courseFeeType" required>
                            <option value="fixed">Fixed</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" id="courseFeeLabel">Course Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" class="form-control" name="fee" id="courseFee" required>
                        </div>
                        <div class="form-text" id="courseFeeHelp"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Duration</label>
                        <input type="text" class="form-control" name="duration" id="courseDuration" placeholder="e.g. 3 Months">
                        <div class="form-text" id="courseDurationHelp"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="courseSaveBtn"><i class="bi bi-check-lg"></i> Save Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/courses.js"></script>