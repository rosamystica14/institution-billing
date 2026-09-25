<?php
$studentId = (int)($_GET['id'] ?? 0);
if ($studentId <= 0) {
    echo '<div class="alert alert-danger">Invalid student.</div>';
    return;
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="section-title mb-0"><i class="bi bi-person-badge-fill"></i> Student Details</h4>
    <a href="<?= BASE_URL ?>/index.php?page=students" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Students</a>
</div>

<div id="studentDetailsContainer">
    <div class="text-center text-muted py-5"><div class="spinner-border text-primary"></div></div>
</div>

<input type="hidden" id="detailsStudentId" value="<?= (int)$studentId ?>">
<script src="<?= BASE_URL ?>/assets/js/student_details.js"></script>
