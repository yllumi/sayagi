/**
 * Reusable avatar uploader for panel forms (profile + user module).
 *
 * Usage:
 *   x-data="avatarUploader({ url, remove_url, field: 'avatar', extra: {}, name, url_current })"
 *
 * Picked or dropped files pass through a square crop editor (Cropper.js) before
 * the upload request. The editor is loaded on demand; when it cannot be loaded
 * the original file is uploaded as-is and the server keeps its center crop.
 */
(() => {
    'use strict';

    const MAX_BYTES = 3 * 1024 * 1024;
    const CROP_SIZE = 512;
    const CROP_QUALITY = 0.92;
    const CROPPER_VERSION = '1.6.2';
    const CROPPER_CSS = `https://cdn.jsdelivr.net/npm/cropperjs@${CROPPER_VERSION}/dist/cropper.min.css`;
    const CROPPER_JS = `https://cdn.jsdelivr.net/npm/cropperjs@${CROPPER_VERSION}/dist/cropper.min.js`;

    const notify = (message, type = 'success') => {
        if (window.toastr) {
            const fn = typeof toastr[type] === 'function' ? toastr[type] : toastr.info;
            fn.call(toastr, message);
            return;
        }
        if (type === 'error' || type === 'warning') {
            window.alert(message);
        }
    };

    // ---- Crop editor -----------------------------------------------------

    let cropperLoader = null;

    const loadCropper = () => {
        if (window.Cropper) {
            return Promise.resolve(window.Cropper);
        }
        if (cropperLoader) {
            return cropperLoader;
        }

        cropperLoader = new Promise((resolve, reject) => {
            if (!document.querySelector('link[data-cropper]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = CROPPER_CSS;
                link.dataset.cropper = '';
                document.head.appendChild(link);
            }

            const script = document.createElement('script');
            script.src = CROPPER_JS;
            script.async = true;
            script.onload = () => {
                if (window.Cropper) {
                    resolve(window.Cropper);
                    return;
                }
                reject(new Error('Cropper is not available'));
            };
            script.onerror = () => reject(new Error('Cropper failed to load'));
            document.head.appendChild(script);
        }).catch((error) => {
            // Allow a retry on the next attempt instead of caching the failure.
            cropperLoader = null;
            throw error;
        });

        return cropperLoader;
    };

    const crop = {
        element: null,
        cropper: null,
        objectUrl: null,
        baseRatio: 1,
        imageReady: false,
        modalShown: false,
        started: false,
        start: null,
        resolve: null,
        reject: null,
    };

    const settleCrop = (file, error = null) => {
        const { resolve, reject } = crop;
        crop.resolve = null;
        crop.reject = null;

        if (error) {
            if (reject) reject(error);
            return;
        }
        if (resolve) resolve(file);
    };

    const destroyCropper = () => {
        if (crop.cropper) {
            crop.cropper.destroy();
            crop.cropper = null;
        }
        if (crop.objectUrl) {
            URL.revokeObjectURL(crop.objectUrl);
            crop.objectUrl = null;
        }

        const image = crop.element ? crop.element.querySelector('.avatar-crop-image') : null;
        if (image) {
            image.onload = null;
            image.onerror = null;
            image.removeAttribute('src');
        }

        crop.start = null;
        crop.imageReady = false;
        crop.modalShown = false;
        crop.started = false;
    };

    const hideCropModal = () => {
        if (window.bootstrap && window.bootstrap.Modal && crop.element) {
            window.bootstrap.Modal.getOrCreateInstance(crop.element).hide();
        }
    };

    /**
     * Re-encode the crop selection as a square 512px WebP file.
     * Browsers without a canvas WebP encoder fall back to lossless PNG.
     */
    const canvasToFile = (canvas) => new Promise((resolve) => {
        canvas.toBlob((blob) => {
            if (blob && blob.type === 'image/webp') {
                resolve(new File([blob], 'avatar.webp', { type: 'image/webp' }));
                return;
            }
            canvas.toBlob((png) => {
                resolve(png ? new File([png], 'avatar.png', { type: 'image/png' }) : null);
            }, 'image/png');
        }, 'image/webp', CROP_QUALITY);
    });

    const buildCropModal = () => {
        if (crop.element) {
            return crop.element;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
<div class="modal fade avatar-crop-modal" id="avatarCropModal" tabindex="-1" aria-labelledby="avatarCropTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarCropTitle">Sesuaikan avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="avatar-crop-stage">
                    <img class="avatar-crop-image" alt="Pratinjau avatar">
                </div>
                <div class="avatar-crop-toolbar">
                    <span class="avatar-crop-icon"><i class="bi bi-zoom-out"></i></span>
                    <input type="range" class="form-range avatar-crop-zoom" min="0" max="100" step="1" value="0" aria-label="Zoom avatar">
                    <span class="avatar-crop-icon"><i class="bi bi-zoom-in"></i></span>
                    <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Putar gambar">
                        <button type="button" class="btn btn-outline-secondary avatar-crop-rotate" data-degrees="-90" title="Putar ke kiri"><i class="bi bi-arrow-counterclockwise"></i></button>
                        <button type="button" class="btn btn-outline-secondary avatar-crop-rotate" data-degrees="90" title="Putar ke kanan"><i class="bi bi-arrow-clockwise"></i></button>
                        <button type="button" class="btn btn-outline-secondary avatar-crop-reset" title="Reset posisi"><i class="bi bi-arrow-repeat"></i></button>
                    </div>
                </div>
                <p class="form-text mb-0 mt-2">Geser gambar untuk memilih bagian yang terlihat, gunakan slider untuk zoom. Hasil disimpan 512×512 piksel.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary avatar-crop-confirm">
                    <span class="spinner-border spinner-border-sm me-1" hidden></span>Gunakan
                </button>
            </div>
        </div>
    </div>
</div>`;

        crop.element = wrapper.firstElementChild;
        document.body.appendChild(crop.element);

        const zoomInput = crop.element.querySelector('.avatar-crop-zoom');
        const confirmButton = crop.element.querySelector('.avatar-crop-confirm');
        const spinner = confirmButton.querySelector('.spinner-border');

        zoomInput.addEventListener('input', () => {
            if (!crop.cropper) return;
            crop.cropper.zoomTo(crop.baseRatio * (1 + Number(zoomInput.value) / 100));
        });

        crop.element.querySelectorAll('.avatar-crop-rotate').forEach((button) => {
            button.addEventListener('click', () => {
                if (crop.cropper) crop.cropper.rotate(Number(button.dataset.degrees || 0));
            });
        });

        crop.element.querySelector('.avatar-crop-reset').addEventListener('click', () => {
            if (!crop.cropper) return;
            crop.cropper.reset();
            zoomInput.value = '0';
        });

        confirmButton.addEventListener('click', async () => {
            if (!crop.cropper) return;

            confirmButton.disabled = true;
            spinner.hidden = false;
            try {
                const canvas = crop.cropper.getCroppedCanvas({
                    width: CROP_SIZE,
                    height: CROP_SIZE,
                    imageSmoothingQuality: 'high',
                });
                settleCrop(canvas ? await canvasToFile(canvas) : null);
            } catch (error) {
                settleCrop(null, error);
            } finally {
                spinner.hidden = true;
                confirmButton.disabled = false;
            }
            hideCropModal();
        });

        crop.element.addEventListener('shown.bs.modal', () => {
            crop.modalShown = true;
            if (crop.start) crop.start();
        });

        crop.element.addEventListener('hidden.bs.modal', () => {
            // Ignore a stale hide when the modal was reopened during the fade.
            if (crop.element.classList.contains('show')) return;

            destroyCropper();
            settleCrop(null);
        });

        return crop.element;
    };

    /**
     * Open the crop editor for a file.
     *
     * @returns {Promise<File|null>} cropped file, or null when cancelled.
     */
    const cropImage = async (file) => {
        await loadCropper();

        if (!window.bootstrap || !window.bootstrap.Modal) {
            throw new Error('Bootstrap modal is not available');
        }

        const element = buildCropModal();
        const image = element.querySelector('.avatar-crop-image');
        const zoomInput = element.querySelector('.avatar-crop-zoom');

        destroyCropper();
        zoomInput.value = '0';

        return new Promise((resolve, reject) => {
            crop.resolve = resolve;
            crop.reject = reject;
            crop.baseRatio = 1;
            crop.imageReady = false;
            crop.modalShown = false;
            crop.started = false;

            /**
             * Cropper must wait for the modal to be visible, otherwise the
             * stage has no size and the crop box is laid out at 0×0.
             */
            crop.start = () => {
                if (crop.started || !crop.imageReady || !crop.modalShown) return;
                crop.started = true;

                try {
                    crop.cropper = new window.Cropper(image, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        cropBoxMovable: false,
                        cropBoxResizable: false,
                        toggleDragModeOnDblclick: false,
                        background: false,
                        ready() {
                            const cropper = crop.cropper;
                            if (!cropper) return;

                            // Cover the square crop area instead of letterboxing
                            // it, then lock the box to the full 1:1 stage.
                            const container = cropper.getContainerData();
                            const canvas = cropper.getCanvasData();
                            const side = Math.min(container.width, container.height);
                            const natural = canvas.naturalWidth ? canvas.width / canvas.naturalWidth : 1;
                            const cover = Math.max(side / canvas.width, side / canvas.height);

                            cropper.zoomTo(natural * cover);

                            const zoomed = cropper.getCanvasData();
                            // Crop box offsets are container-relative, and the
                            // canvas stays centered, so the box is centered too.
                            cropper.setCropBoxData({
                                left: (container.width - side) / 2,
                                top: (container.height - side) / 2,
                                width: side,
                                height: side,
                            });

                            crop.baseRatio = zoomed.naturalWidth ? zoomed.width / zoomed.naturalWidth : 1;
                        },
                    });
                } catch (error) {
                    destroyCropper();
                    hideCropModal();
                    settleCrop(null, error);
                }
            };

            image.onload = () => {
                crop.imageReady = true;
                if (crop.start) crop.start();
            };
            image.onerror = () => {
                hideCropModal();
                settleCrop(null, new Error('Image failed to load'));
            };

            crop.objectUrl = URL.createObjectURL(file);
            image.src = crop.objectUrl;

            if (image.complete && image.naturalWidth > 0) {
                crop.imageReady = true;
            }

            window.bootstrap.Modal.getOrCreateInstance(element, { backdrop: 'static' }).show();
        });
    };

    document.addEventListener('alpine:init', () => {
        Alpine.data('avatarUploader', (config = {}) => ({
            preview: config.url_current || null,
            name: config.name || '',
            uploading: false,
            removing: false,
            dragging: false,

            init() {
                this.ensureAdjustButton();
            },

            /**
             * Older sayagi releases ship the partial without the re-crop
             * button, and `vendor/` comes from the composer package in
             * production, so add it only when the markup does not have it.
             */
            ensureAdjustButton() {
                if (this.$el.querySelector('[data-avatar-adjust]')) return;

                const row = this.$el.querySelector('.avatar-uploader-body .d-flex');
                if (!row) return;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-secondary';
                button.title = 'Sesuaikan area avatar';
                button.dataset.avatarAdjust = '';
                button.innerHTML = '<i class="bi bi-crop"></i> Sesuaikan';
                button.addEventListener('click', () => this.adjust());

                row.insertBefore(button, row.children[1] || null);

                const sync = () => {
                    button.style.display = this.preview ? '' : 'none';
                    button.disabled = this.uploading || this.removing;
                };

                this.$watch('preview', sync);
                this.$watch('uploading', sync);
                this.$watch('removing', sync);
                sync();
            },

            pick() {
                this.$refs.avatarFile.click();
            },

            onPick(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (file) this.prepare(file);
            },

            onDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer && event.dataTransfer.files
                    ? event.dataTransfer.files[0]
                    : null;
                if (file) this.prepare(file);
            },

            /**
             * Re-crop the stored avatar by feeding the current file back into
             * the editor. Only same-origin uploads are fetchable.
             */
            adjust() {
                if (!this.preview || this.uploading) return;

                fetch(this.preview, { credentials: 'same-origin' })
                    .then((response) => {
                        if (!response.ok) throw new Error('Avatar is not readable');
                        return response.blob();
                    })
                    .then((blob) => new File([blob], 'avatar-saat-ini', { type: blob.type || 'image/webp' }))
                    .then((file) => this.prepare(file, { skipTypeCheck: true }))
                    .catch(() => notify('Tidak dapat membuka editor crop.', 'error'));
            },

            /**
             * Validate, then crop before uploading. When the editor is not
             * available the original file is uploaded and the server applies
             * its own center crop.
             */
            async prepare(file, options = {}) {
                if (!options.skipTypeCheck && !/^image\//.test(file.type)) {
                    notify('Berkas harus berupa gambar.', 'error');
                    return;
                }
                if (file.size > MAX_BYTES) {
                    notify('Ukuran avatar maksimal 3 MB.', 'error');
                    return;
                }

                let ready = null;
                try {
                    ready = await cropImage(file);
                } catch (error) {
                    notify('Editor crop tidak tersedia, gambar diunggah apa adanya.', 'warning');
                    ready = file;
                }

                if (ready) this.upload(ready);
            },

            initials() {
                const parts = String(this.name || '').trim().split(/\s+/).filter(Boolean);
                if (!parts.length) return '?';
                const first = parts[0].charAt(0);
                const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';

                return (first + last).toUpperCase();
            },

            upload(file) {
                const body = new FormData();
                body.append(config.field || 'avatar', file);
                Object.entries(config.extra || {}).forEach(([key, value]) => {
                    body.append(key, value);
                });

                this.uploading = true;

                fetch(config.url, {
                    method: 'POST',
                    body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (!data.success) {
                            notify(data.message || 'Gagal mengunggah avatar.', 'error');
                            return;
                        }
                        this.preview = data.avatar_url || null;
                        if (data.avatar_url) {
                            this.name = this.name || '';
                        }
                        notify(data.message || 'Avatar diperbarui.', 'success');
                        this.$dispatch('avatar-updated', data);
                    })
                    .catch(() => notify('Gagal mengunggah avatar.', 'error'))
                    .finally(() => { this.uploading = false; });
            },

            remove() {
                if (!config.remove_url || this.removing) return;
                if (!window.confirm('Hapus avatar ini?')) return;

                const body = new FormData();
                Object.entries(config.extra || {}).forEach(([key, value]) => {
                    body.append(key, value);
                });

                this.removing = true;

                fetch(config.remove_url, {
                    method: 'POST',
                    body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (!data.success) {
                            notify(data.message || 'Gagal menghapus avatar.', 'error');
                            return;
                        }
                        this.preview = null;
                        notify(data.message || 'Avatar dihapus.', 'success');
                        this.$dispatch('avatar-updated', data);
                    })
                    .catch(() => notify('Gagal menghapus avatar.', 'error'))
                    .finally(() => { this.removing = false; });
            },
        }));
    });
})();
