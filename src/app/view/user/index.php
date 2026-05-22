<style>
    tr.deleted {
        opacity: 0.6;
    }
</style>

<div class="content" x-data="userTable()" x-init="load()">

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
            <a href="<?= site_url('panel/user/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah User
            </a>
        </div>
    </div>

    <div class="page-content">
        <div class="card">
            <div class="card-body">

                <!-- Toolbar -->
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Status filter -->
                        <div class="btn-group btn-group-sm" role="group">
                            <template x-for="opt in statusOptions" :key="opt.value">
                                <button
                                    type="button"
                                    class="btn"
                                    :class="statusFilter === opt.value ? 'btn-primary' : 'btn-outline-secondary'"
                                    x-text="opt.label"
                                    @click="statusFilter = opt.value; goPage(1)">
                                </button>
                            </template>
                        </div>
                        <!-- Per-page -->
                        <span class="text-muted  ms-2">Tampilkan</span>
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
                            placeholder="Cari nama, username, email..."
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
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>No. HP</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th style="width:140px" x-show="privileges.user_write || privileges.user_delete">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Loading skeleton -->
                            <template x-if="loading">
                                <tr>
                                    <td colspan="9">
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

                            <!-- Empty -->
                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                            </template>

                            <!-- Rows -->
                            <template x-for="(row, i) in rows" :key="row.id">
                                <tr :class="row.status">
                                    <td x-text="row.id" class="text-muted "></td>
                                    <td x-text="row.name"></td>
                                    <td class="text-muted" x-text="row.username ? '@' + row.username : '-'"></td>
                                    <td x-text="row.email"></td>
                                    <td x-text="row.phone"></td>
                                    <td x-text="row.role_name" class="text-muted"></td>
                                    <td>
                                        <span
                                            class="badge"
                                            :class="{
                                                'bg-success': row.status === 'active',
                                                'bg-warning': row.status === 'inactive',
                                                'bg-danger': row.status === 'deleted',
                                            }"
                                            x-text="row.status">
                                        </span>
                                    </td>
                                    <td class="text-muted " x-text="row.created_at"></td>
                                    <td class="text-end">
                                        <template x-if="privileges.user_write">
                                            <a :href="'<?= site_url('panel/user/edit') ?>?id=' + row.id" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </template>
                                        <template x-if="privileges.user_delete">
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

                <!-- Footer: info + pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2" x-show="!loading && rows.length > 0">
                    <div class="text-muted ">
                        Menampilkan
                        <strong x-text="(page - 1) * perPage + 1"></strong>–<strong x-text="Math.min(page * perPage, filtered)"></strong>
                        dari <strong x-text="filtered"></strong> data
                        <template x-if="search"><span> (difilter dari <strong x-text="total"></strong> total)</span></template>
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

    <!-- Toast container -->
    <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div>

</div>

<script>
function userTable() {
    return {
        rows:         [],
        total:        0,
        filtered:     0,
        pages:        1,
        page:         1,
        perPage:      10,
        search:        '',
        statusFilter:  'active',
        privileges: {},
        statusOptions: [
            { value: 'active',   label: 'Aktif' },
            { value: 'inactive', label: 'Nonaktif' },
            { value: 'deleted',  label: 'Dihapus' },
            { value: 'all',      label: 'Semua' },
        ],
        loading:  false,

        get pageRange() {
            const delta = 2;
            const range = [];
            for (let i = Math.max(1, this.page - delta); i <= Math.min(this.pages, this.page + delta); i++) {
                range.push(i);
            }
            return range;
        },

        async load() {
            this.loading = true;
            const params = new URLSearchParams({
                page:     this.page,
                per_page: this.perPage,
                search:   this.search,
                status:   this.statusFilter,
            });
            try {
                const res  = await fetch(`<?= site_url('/panel/user/data') ?>?` + params);
                const data = await res.json();
                this.rows     = data.rows;
                this.total    = data.total;
                this.filtered = data.filtered;
                this.pages    = data.pages;
                this.privileges = data.privileges || {};
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
            const confirmed = await Prompts.confirm(`Yakin ingin menghapus user "${row.name}"? Tindakan ini tidak dapat dibatalkan.`);
            if (!confirmed) return;
            try {
                const res  = await fetch(`<?= site_url('/panel/user/delete') ?>`, {
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
