<style>
    .hovered:hover {
        background-color: rgba(0, 71, 202, 0.1);
        /* add transition */
        transition: background-color 0.2s ease-in-out;
    }
</style>

<div class="content" x-data="panelmenuApp()" x-init="init()">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
            <button class="btn btn-primary" @click="openCreate(null)">
                <i class="bi bi-plus-lg"></i> Tambah Root Menu
            </button>
        </div>
    </div>

    <div class="page-content mb-5" x-ref="rootContainer">

        <!-- Alert -->
        <div x-show="alert.show" x-cloak
            :class="'alert alert-' + alert.type + ' alert-dismissible'"
            role="alert">
            <span x-text="alert.message"></span>
            <button type="button" class="btn-close" @click="alert.show = false"></button>
        </div>

        <!-- Menu Tree -->
        <template x-if="menus.length === 0">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-list-nested fs-1 d-block mb-2"></i>
                    Belum ada menu. Klik <strong>Tambah Root Menu</strong> untuk memulai.
                </div>
            </div>
        </template>

        <template x-for="(menu, idx) in menus" :key="menu.id">
            <div class="card mb-2" :data-id="menu.id">
                <div class="card-header d-flex align-items-top gap-3 py-2 hovered">
                    <div class="drag-handle" style="cursor:grab;color:#ccc;padding-top:3px" title="Geser untuk mengubah urutan">
                        <i class="bi bi-grip-vertical fs-5"></i>
                    </div>
                    <div>
                        <i :class="menu.icon + ' fs-5 text-primary'"></i>
                    </div>
                    <div class="flex-grow-1 mt-1">
                        <strong x-text="menu.label"></strong>
                        <span class="text-muted ms-2" x-text="menu.url !== '#' ? menu.url : ''"></span>
                        <div class="text-muted small mt-1">
                            <template x-if="menu.module">
                                <span class="me-4 text-primary">
                                    <span>module:</span>
                                    <span x-text="menu.module + (menu.submodule ? '.' + menu.submodule : '')"></span>
                                </span>
                            </template>
                            <template x-if="menu.privilege">
                                <span>
                                    <span>privilege:</span>
                                    <code x-text="menu.privilege"></code>
                                </span>
                            </template>
                        </div>
                    </div>
                    <div class="d-flex gap-1 align-items-center">
                        <button class="btn btn-sm btn-success" @click="openCreate(menu.id)" title="Tambah sub-menu">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" @click="openEdit(menu)" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" @click="confirmDelete(menu)" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Children -->
                <template x-if="menu.children && menu.children.length > 0">
                    <ul class="list-group list-group-flush ps-4 children-list" :data-parent-id="menu.id">
                        <template x-for="child in menu.children" :key="child.id">
                            <li class="list-group-item d-flex align-items-top gap-3 ps-4 hovered" :data-id="child.id">
                                <div class="drag-handle" style="cursor:grab;color:#ccc;padding-top:2px" title="Geser untuk mengubah urutan">
                                    <i class="bi bi-grip-vertical"></i>
                                </div>
                                <div>
                                    <i :class="child.icon + ' text-secondary'"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <span x-text="child.label"></span>
                                    <span class="text-muted ms-2 small" x-text="child.url !== '#' ? child.url : ''"></span>
                                    <div class="text-muted small mt-1">
                                        <template x-if="child.module">
                                            <span class="me-4 text-primary">
                                                <span>module:</span>
                                                <span class="ms-1" x-text="child.module + (child.submodule ? '.' + child.submodule : '')"></span>
                                            </span>
                                        </template>
                                        <template x-if="child.privilege">
                                            <span>
                                                <span>privilege:</span>
                                                <code class="ms-1" x-text="child.privilege"></code>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <div class="d-flex gap-1 align-items-center pe-2">
                                    <button class="btn btn-sm btn-outline-warning" @click="openEdit(child)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(child)" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>
        </template>

    </div><!-- /page-content -->

    <!-- ================================================================
    Modal: Create / Edit
    ================================================================ -->
    <div class="modal fade" id="menuModal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true" x-ref="menuModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="menuModalLabel" x-text="modalMode === 'edit' ? 'Edit Menu' : 'Tambah Menu'"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" x-model="form.id">
                    <input type="hidden" x-model="form.parent_id">

                    <template x-if="form.parent_id">
                        <div class="alert alert-info py-2 mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Menu ini akan menjadi <strong>sub-menu</strong> dari menu yang dipilih.
                        </div>
                    </template>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.label" placeholder="contoh: Manajemen User">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL</label>
                            <input type="text" class="form-control" x-model="form.url" placeholder="/panel/user/index atau #">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Module</label>
                            <input type="text" class="form-control" x-model="form.module" placeholder="contoh: user">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Submodule</label>
                            <input type="text" class="form-control" x-model="form.submodule" placeholder="contoh: user (kosongkan jika root)">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Icon <small class="text-muted">(Bootstrap Icons class)</small></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i :class="form.icon || 'bi bi-question-circle'"></i>
                                </span>
                                <input type="text" class="form-control" x-model="form.icon" placeholder="bi bi-person">
                            </div>
                            <div class="form-text">
                                Lihat daftar ikon di <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Target</label>
                            <select class="form-select" x-model="form.target">
                                <option value="">_self (sama tab)</option>
                                <option value="_blank">_blank (tab baru)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Privilege <small class="text-muted">(hak akses yang dibutuhkan untuk melihat menu ini)</small></label>
                            <select class="form-select" x-model="form.privilege">
                                <option value="">— Tidak dibatasi —</option>
                                <?php
                                $grouped = [];
                                foreach ($privileges as $p) {
                                    $grouped[$p->feature][] = $p;
                                }
                                foreach ($grouped as $feature => $items) : ?>
                                    <optgroup label="<?= htmlspecialchars($feature) ?>">
                                        <?php foreach ($items as $p) : ?>
                                            <option value="<?= htmlspecialchars($p->feature . '.' . $p->privilege) ?>">
                                                <?= htmlspecialchars($p->feature . '.' . $p->privilege) ?><?= $p->description ? ' — ' . htmlspecialchars($p->description) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" @click="submitForm()" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================
    Modal: Konfirmasi Hapus
    ================================================================ -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Hapus Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus menu <strong x-text="deleteTarget.label"></strong>?</p>
                    <p class="text-danger mb-0"><i class="bi bi-exclamation-triangle me-1"></i>
                        Seluruh sub-menu di dalamnya juga akan ikut terhapus.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" @click="doDelete()" :disabled="deleting">
                        <span x-show="deleting" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-text="deleting ? 'Menghapus...' : 'Hapus'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div><!-- /x-data -->

<script>
    function panelmenuApp() {
        return {
            menus: <?= json_encode($menus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,

            alert: {
                show: false,
                type: 'success',
                message: ''
            },

            modalMode: 'create', // 'create' | 'edit'
            form: {
                id: '',
                parent_id: '',
                label: '',
                module: '',
                submodule: '',
                icon: '',
                url: '#',
                target: '',
                privilege: '',
            },
            saving: false,

            deleteTarget: {},
            deleting: false,

            _menuModal: null,
            _deleteModal: null,

            init() {
                this._menuModal = new bootstrap.Modal(document.getElementById('menuModal'));
                this._deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                this.$nextTick(() => this.initSortable());
            },

            initSortable() {
                if (typeof Sortable === 'undefined') return;
                const rootEl = this.$refs.rootContainer;

                new Sortable(rootEl, {
                    handle: '.drag-handle',
                    draggable: '.card[data-id]',
                    animation: 150,
                    onEnd: () => {
                        const ids = [...rootEl.querySelectorAll(':scope > .card[data-id]')]
                            .map(el => el.dataset.id);
                        this.reorder('', ids);
                    },
                });

                rootEl.querySelectorAll('.children-list').forEach(ul => {
                    new Sortable(ul, {
                        handle: '.drag-handle',
                        animation: 150,
                        onEnd: () => {
                            const parentId = ul.dataset.parentId;
                            const ids = [...ul.querySelectorAll(':scope > [data-id]')]
                                .map(el => el.dataset.id);
                            this.reorder(parentId, ids);
                        },
                    });
                });
            },

            async reorder(parentId, ids) {
                try {
                    const res = await axios.post('/panel/panelmenu/reorder', { parent_id: parentId, ids });
                    if (!res.data.success) {
                        this.showAlert('danger', res.data.message ?? 'Gagal menyimpan urutan.');
                    }
                } catch (e) {
                    this.showAlert('danger', 'Permintaan gagal. Coba lagi.');
                }
            },

            showAlert(type, message) {
                this.alert = {
                    show: true,
                    type,
                    message
                };
                setTimeout(() => this.alert.show = false, 4000);
            },

            resetForm() {
                this.form = {
                    id: '',
                    parent_id: '',
                    label: '',
                    module: '',
                    submodule: '',
                    icon: '',
                    url: '#',
                    target: '',
                    privilege: ''
                };
            },

            openCreate(parentId) {
                this.modalMode = 'create';
                this.resetForm();
                this.form.parent_id = parentId ?? '';
                this._menuModal.show();
            },

            openEdit(item) {
                this.modalMode = 'edit';
                this.form.id = item.id;
                this.form.parent_id = '';
                this.form.label = item.label;
                this.form.module = item.module ?? '';
                this.form.submodule = item.submodule ?? '';
                this.form.icon = item.icon ?? '';
                this.form.url = item.url ?? '#';
                this.form.target = item.target ?? '';
                this.form.privilege = item.privilege ?? '';
                this._menuModal.show();
            },

            async submitForm() {
                if (!this.form.label.trim()) {
                    this.showAlert('warning', 'Label wajib diisi.');
                    return;
                }
                this.saving = true;
                const url = this.modalMode === 'edit' ?
                    '/panel/panelmenu/update' :
                    '/panel/panelmenu/store';

                try {
                    const params = new URLSearchParams();
                    Object.entries(this.form).forEach(([k, v]) => params.append(k, v ?? ''));
                    const res = await axios.post(url, params);
                    if (res.data.success) {
                        this._menuModal.hide();
                        this.showAlert('success', res.data.message);
                        setTimeout(() => location.reload(), 800);
                    } else {
                        this.showAlert('danger', res.data.message ?? 'Terjadi kesalahan.');
                    }
                } catch (e) {
                    this.showAlert('danger', 'Permintaan gagal. Coba lagi.');
                } finally {
                    this.saving = false;
                }
            },

            confirmDelete(item) {
                this.deleteTarget = item;
                this._deleteModal.show();
            },

            async doDelete() {
                this.deleting = true;
                try {
                    const params = new URLSearchParams({
                        id: this.deleteTarget.id
                    });
                    const res = await axios.post('/panel/panelmenu/delete', params);
                    if (res.data.success) {
                        this._deleteModal.hide();
                        this.showAlert('success', res.data.message);
                        setTimeout(() => location.reload(), 800);
                    } else {
                        this.showAlert('danger', res.data.message ?? 'Terjadi kesalahan.');
                    }
                } catch (e) {
                    this.showAlert('danger', 'Permintaan gagal. Coba lagi.');
                } finally {
                    this.deleting = false;
                }
            },
        };
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>