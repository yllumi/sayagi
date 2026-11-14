/**
 * Reusable avatar uploader for panel forms (profile + user module).
 *
 * Usage:
 *   x-data="avatarUploader({ url, remove_url, field: 'avatar', extra: {}, name, url_current })"
 */
(() => {
    'use strict';

    const MAX_BYTES = 3 * 1024 * 1024;

    const notify = (message, type = 'success') => {
        if (window.toastr) {
            const fn = typeof toastr[type] === 'function' ? toastr[type] : toastr.info;
            fn.call(toastr, message);
            return;
        }
        if (type === 'error') {
            window.alert(message);
        }
    };

    document.addEventListener('alpine:init', () => {
        Alpine.data('avatarUploader', (config = {}) => ({
            preview: config.url_current || null,
            name: config.name || '',
            uploading: false,
            removing: false,
            dragging: false,

            pick() {
                this.$refs.avatarFile.click();
            },

            onPick(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (file) this.upload(file);
            },

            onDrop(event) {
                this.dragging = false;
                const file = event.dataTransfer && event.dataTransfer.files
                    ? event.dataTransfer.files[0]
                    : null;
                if (file) this.upload(file);
            },

            initials() {
                const parts = String(this.name || '').trim().split(/\s+/).filter(Boolean);
                if (!parts.length) return '?';
                const first = parts[0].charAt(0);
                const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';

                return (first + last).toUpperCase();
            },

            upload(file) {
                if (!/^image\//.test(file.type)) {
                    notify('Berkas harus berupa gambar.', 'error');
                    return;
                }
                if (file.size > MAX_BYTES) {
                    notify('Ukuran avatar maksimal 3 MB.', 'error');
                    return;
                }

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
