<div class="content" x-data="roleTable()" x-init="load()">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
            <a href="<?= site_url('panel/role/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Role
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
                    <div class="input-group" style="max-width:260px">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Cari nama atau deskripsi..."
                            x-model="search"
                            @input.debounce.350ms="goPage(1)">
                        <button class="btn btn-outline-secondary" x-show="search" @click="search=''; goPage(1)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">ID</th>
                                <th>Nama Role</th>
                                <th>Slug</th>
                                <th style="width:130px">Jumlah Privilege</th>
                                <th>Dibuat</th>
                                <th style="width:140px">Aksi</th>
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
                                        <i class="bi bi-shield-x fs-4 d-block mb-1"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(row, i) in rows" :key="row.id">
                                <tr>
                                    <td x-text="row.id" class="text-muted "></td>
                                    <td>
                                        <span class="fw-semibold" x-text="row.name"></span>
                                    </td>
                                    <td class="text-muted" x-text="row.role_slug"></td>
                                    <td>
                                        <span x-text="row.priv_count"></span>
                                    </td>
                                    <td class="text-muted " x-text="row.created_at"></td>
                                    <td>
                                        <a :href="'<?= site_url('panel/role/edit') ?>?id=' + row.id" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger ms-1"
                                            @click="deleteRow(row)">
                                            <i class="bi bi-trash"></i>
                                        </button>
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
                        <template x-if="search"><span> (difilter dari <strong x-text="total"></strong> total)</span></template>
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
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
function roleTable() {
    return {
        rows:    [],
        total:   0,
        filtered:0,
        pages:   1,
        page:    1,
        perPage: 10,
        search:  '',
        loading: false,

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
                const res  = await fetch(`<?= site_url('/panel/role/data') ?>?` + new URLSearchParams({
                    page: this.page, per_page: this.perPage, search: this.search,
                }));
                const data = await res.json();
                this.rows     = data.rows;
                this.total    = data.total;
                this.filtered = data.filtered;
                this.pages    = data.pages;
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
            const confirmed = await Prompts.confirm(`Yakin ingin menghapus role "${row.name}"?\n\nSemua privilege role ini akan ikut dihapus.`);
            if (!confirmed) return;
            try {
                const res  = await fetch(`<?= site_url('/panel/role/delete') ?>`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ id: row.id }),
                });
                const data = await res.json();
                if (data.success) {
                    this.showToast(data.message, 'success');
                    if (this.rows.length === 1 && this.page > 1) this.page--;
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
