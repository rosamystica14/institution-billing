</div><!-- /.app-wrapper -->

<!-- Global Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1080;"></div>

<!-- Global Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmModalBody">Are you sure?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmModalOkBtn">Yes, Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Global Loading Spinner Overlay -->
<div id="globalLoader" class="global-loader d-none">
    <div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
