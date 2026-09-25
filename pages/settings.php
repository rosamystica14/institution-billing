<?php $settings = getSettings(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="section-title mb-0"><i class="bi bi-gear-fill"></i> Settings</h4>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form id="settingsForm" enctype="multipart/form-data" novalidate>
            <div class="card">
                <div class="card-header"><i class="bi bi-building"></i> Institution Details</div>
                <div class="card-body">

                    <div class="mb-4">
                        <label class="form-label">Logo</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="settings-logo-circle" id="logoPreviewWrap"
                                 style="width:84px;height:84px;border-radius:50%;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#eef2f7;border:2px solid #dee2e6;">
                                <?php if (!empty($settings['logo'])): ?>
                                    <img src="<?= BASE_URL ?>/assets/uploads/logo/<?= e($settings['logo']) ?>" alt="Logo" id="logoPreviewImg"
                                         style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <i class="bi bi-mortarboard-fill" id="logoPreviewIcon" style="font-size:2.1rem;color:#6c757d;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" class="form-control" name="logo" id="logoFileInput" accept="image/png, image/jpeg, image/webp, image/gif">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogoCheckbox" <?= empty($settings['logo']) ? 'disabled' : '' ?>>
                                    <label class="form-check-label small" for="removeLogoCheckbox">Remove current logo (revert to default)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Institution Name</label>
                        <input type="text" class="form-control" name="institution_name" id="institutionName"
                               maxlength="150" value="<?= e($settings['institution_name'] ?? '') ?>" required>
                        <div class="invalid-feedback">Institution name is required.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" id="institutionAddress" rows="2" maxlength="300"><?= e($settings['address'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" id="institutionPhone"
                                   pattern="[0-9]{10}" maxlength="10" inputmode="numeric"
                                   title="Phone number must be exactly 10 digits"
                                   value="<?= e($settings['phone'] ?? '') ?>">
                            <div class="invalid-feedback">Phone number must be exactly 10 digits.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="institutionEmail"
                                   maxlength="150" value="<?= e($settings['email'] ?? '') ?>">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>

                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> Save Settings</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/settings.js?v=<?= time() ?>"></script>