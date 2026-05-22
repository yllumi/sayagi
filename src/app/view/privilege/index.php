<div class="content" x-data="privilegeTable()" x-init="load()">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
            <a href="<?= site_url('panel/privilege/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Privilege
            </a>
        </div>
    </div>

    <div class="page-content">
        <div class="card">
            <div class="card-body">

                <!-- Toolbar -->
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted ">Tampilkan</span>
                        <select class="form-select" style="width:80px" x-model.number="perPage" @change="goPage(1)">
                            <option>10</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                        <span class="text-muted ">data</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select" style="width:160px" x-model="filterFeature" @change="goPage(1)">
                            <option value="">Semua Fitur</option>
                            <template x-for="m in features" :key="m">
                                <option :value="m" x-text="m"></option>
                            </template>
                        </select>
                        <div class="input-group" style="max-width:260px">
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Cari privilege..."
                                x-model="search"
                                @input.debounce.350ms="goPage(1)">
                            <button class="btn btn-outline-secondary btn-sm" x-show="search" @click="search=''; goPage(1)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th style="width:160px">Privilege</th>
                                <th>Deskripsi</th>
                                <th style="width:120px" x-show="privileges.privilege_write || privileges.privilege_delete">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr>
                                    <td colspan="6">
                                        <!-- Spinner -->
                                        <div class="d-flex flex-column align-items-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <span class="text-muted mt-2">Memuat data...</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-key fs-4 d-block mb-1"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(row, i) in rows" :key="row.key">
                                <tr>
                                    <td x-text="(page - 1) * perPage + i + 1" class="text-muted "></td>
                                    <td class="text-nowrap">
                                        <code class="fw-bold text-primary" x-text="row.feature + `.`"></code><code class="fw-bold text-success" x-text="row.privilege"></code>
                                    </td>
                                    <td class="text-muted " x-text="row.description || '-'"></td>
                                    <td class="text-end" x-show="privileges.privilege_write || privileges.privilege_delete">
                                        <template x-if="privileges.privilege_write">
                                            <a :href="'<?= site_url('panel/privilege/edit') ?>?key=' + row.key" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </template>
                                        <template x-if="privileges.privilege_delete">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger ms-1"
                                                @click="deleteRow(row)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2" x-show="!loading && rows.length > 0">
                    <div class="text-muted ">
                        Menampilkan
                        <strong x-text="(page - 1) * perPage + 1"></strong>–<strong x-text="Math.min(page * perPage, filtered)"></strong>
                        dari <strong x-text="filtered"></strong> data
                        <template x-if="search || filterFeature"><span> (difilter dari <strong x-text="total"></strong> total)</span></template>
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="page <= 1 ? 'disabled' : ''">
                                <button class="page-link" @click="goPage(page - 1)">&lsaquo;</button>
                            </li>
                            <template x-for="p in pageRange" :key="p">
                                <li class="page-item" :class="p === page ? 'active' : ''">
                                    <button class="page-link" x-text="p" @click="goPage(p)"></button>
                                </li>
                            </template>
                            <li class="page-item" :class="page >= pages ? 'disabled' : ''">
                                <button class="page-link" @click="goPage(page + 1)">&rsaquo;</button>
                            </li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div>

</div>

<script>
function privilegeTable() {
    return {
        rows:        [],
        features:     [],
        total:       0,
        filtered:    0,
        pages:       1,
        page:        1,
        perPage:     25,
        search:      '',
        filterFeature:'',
        privileges: {},
        loading:     false,

        get pageRange() {
            const range = [];
            for (let i = Math.max(1, this.page - 2); i <= Math.min(this.pages, this.page + 2); i++) {
                range.push(i);
            }
            return range;
        },

        async load() {
            this.loading = true;
            try {
                const [dataRes, modRes] = await Promise.all([
                    fetch(`<?= site_url('/panel/privilege/data') ?>` + '?' + new URLSearchParams({
                        page: this.page, per_page: this.perPage,
                        search: this.search, feature: this.filterFeature,
                    })),
                    this.features.length ? Promise.resolve(null) : fetch(`<?= site_url('/panel/privilege/features') ?>`),
                ]);

                const data = await dataRes.json();
                this.rows     = data.rows;
                this.total    = data.total;
                this.filtered = data.filtered;
                this.pages    = data.pages;
                this.privileges = data.privileges;

                if (modRes) {
                    const modData = await modRes.json();
                    this.features = modData.features;
                }
            } catch (e) {
                this.showToast('Gagal memuat data.', 'danger');
            } finally {
                this.loading = false;
            }
        },

        goPage(p) {
            if (p < 1 || (this.pages > 0 && p > this.pages)) return;
            this.page = p;
            this.load();
        },

        async deleteRow(row) {
            const confirmed = await Prompts.confirm(`Yakin ingin menghapus privilege "${row.privilege}"?`);
            if (!confirmed) return;
            try {
                const res  = await fetch(`<?= site_url('/panel/privilege/delete') ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ key: row.key }),
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    if (this.rows.length === 1 && this.page > 1) this.page--;
                    this.features = []; // reset so features reload
                    this.load();
                } else {
                    this.showToast(data.message, 'danger');
                }
            } catch (e) {
                this.showToast('Terjadi kesalahan.', 'danger');
            }
        },

        showToast(msg, type = 'success') {
            const id   = 'toast_' + Date.now();
            const html = `
                <div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" role="alert" style="min-width:260px">
                    <div class="d-flex">
                        <div class="toast-body">${msg}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;
            document.getElementById('toastContainer').insertAdjacentHTML('beforeend', html);
            setTimeout(() => document.getElementById(id)?.remove(), 4000);
        },
    };
}
</script>
