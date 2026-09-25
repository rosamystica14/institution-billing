/**
 * settings.js - Institution details save, with a live circular logo
 * preview and a "remove logo" option that reverts to the default icon.
 */

function setLogoPreviewToIcon() {
    const wrap = document.getElementById('logoPreviewWrap');
    wrap.innerHTML = '<i class="bi bi-mortarboard-fill" id="logoPreviewIcon" style="font-size:2.1rem;color:#6c757d;"></i>';
}

function setLogoPreviewToImage(src) {
    const wrap = document.getElementById('logoPreviewWrap');
    wrap.innerHTML = `<img src="${src}" alt="Logo" id="logoPreviewImg" style="width:100%;height:100%;object-fit:cover;">`;
}

function validateSettingsForm(form) {
    const valid = form.checkValidity();
    form.classList.add('was-validated');
    return valid;
}

document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('logoFileInput');
    const removeCheckbox = document.getElementById('removeLogoCheckbox');

    // Live preview of a newly chosen logo file, and choosing a new file
    // cancels any pending "remove logo" request.
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;

        removeCheckbox.checked = false;

        const reader = new FileReader();
        reader.onload = (e) => setLogoPreviewToImage(e.target.result);
        reader.readAsDataURL(file);
    });

    // Checking "remove logo" previews the default icon immediately and
    // clears any file that was selected (can't remove + upload at once).
    removeCheckbox.addEventListener('change', () => {
        if (removeCheckbox.checked) {
            fileInput.value = '';
            setLogoPreviewToIcon();
        } else {
            // Re-show the current saved logo (if any) since the removal was undone
            const originalSrc = document.getElementById('logoPreviewWrap').dataset.originalSrc;
            if (originalSrc) {
                setLogoPreviewToImage(originalSrc);
            } else {
                setLogoPreviewToIcon();
            }
        }
    });

    // Remember the originally-saved logo src (if any) so unchecking "remove" can restore it.
    const existingImg = document.getElementById('logoPreviewImg');
    if (existingImg) {
        document.getElementById('logoPreviewWrap').dataset.originalSrc = existingImg.src;
    }

    // Digits-only enforcement on phone, matching the pattern used elsewhere in the app.
    document.getElementById('institutionPhone').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
    });

    document.getElementById('settingsForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;

        if (!validateSettingsForm(form)) {
            showToast('Please fix the highlighted fields before saving.', 'error');
            return;
        }

        const formData = new FormData(form);
        const result = await apiPost(`${BASE_URL}/ajax/settings_save.php`, formData);
        showToast(result.message, result.success ? 'success' : 'error');

        if (result.success) {
            setTimeout(() => window.location.reload(), 1000);
        }
    });
});