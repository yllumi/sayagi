<?php
// Build column definitions for Alpine from schema
$columns = [];
foreach ($schema['fields'] as $fieldDef) {
    $col = [
        'field' => $fieldDef['field'],
        'label' => $fieldDef['label'] ?? $fieldDef['field'],
        'type'  => $fieldDef['form'] ?? 'text',
    ];
    if (isset($fieldDef['options'])) {
        $col['options'] = $fieldDef['options'];
    }
    if (!empty($fieldDef['relation']['table'])) {
        $col['displayField'] = $fieldDef['field'] . '_display';
    }
    $columns[] = $col;
}
$columnsJson = json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$entrySlug   = $slug;
$entryName   = $schema['name'] ?? ucfirst($slug);
?>
<div class="content" x-data="entryApp()">

    <div class="page-heading mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
            <a href="/app/entry/<?= $entrySlug ?>/create" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah <?= $entryName ?>
            </a>
        </div>
    </div>

    <div class="page-content">
        <div class="card">
            <div class="card-body">

                <!-- Toolbar -->
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">Tampilkan</span>
                        <select class="form-select form-select-sm" style="width:75px"
                            x-model.number="perPage" @change="goPage(1)">
                            <option>10</option>
                            <option>25</option>
                            <option>50</option>
                        </select>
                        <span class="text-muted">data</span>
                    </div>
                    <div class="input-group" style="max-width:280px">
                        <input type="text" class="form-control form-control-sm"
                            placeholder="Cari…" x-model="search"
                            @input.debounce.350ms="goPage(1)">
                        <button class="btn btn-outline-secondary btn-sm"
                            x-show="search" @click="search=''; goPage(1)">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <template x-for="col in columns" :key="col.field">
                                    <th x-text="col.label"></th>
                                </template>
                                <th style="width:110px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>

                            <template x-if="loading">
                                <tr>
                                    <td :colspan="columns.length + 2">
                                        <div class="d-flex flex-column align-items-center py-4">
                                            <div class="spinner-border text-primary" role="status"></div>
                                            <span class="text-muted mt-2">Memuat data…</span>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && rows.length === 0">
                                <tr>
                                    <td :colspan="columns.length + 2" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        Tidak ada data
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(row, i) in rows" :key="row.id">
                                <tr>
                                    <td class="text-muted" x-text="(page - 1) * perPage + i + 1"></td>
                                    <template x-for="col in columns" :key="col.field">
                                        <td style="max-width:200px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
                                            <template x-if="col.options">
                                                <span x-text="col.options[row[col.field]] ?? row[col.field]"></span>
                                            </template>
                                            <template x-if="col.displayField">
                                                <span x-text="row[col.displayField] ?? '—'"></span>
                                            </template>
                                            <template x-if="!col.options && !col.displayField">
                                                <span x-text="row[col.field] ?? '—'"></span>
                                            </template>
                                        </td>
                                    </template>
                                    <td>
                                        <a :href="baseUrl + '/edit?id=' + row.id"
                                            class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-sm btn-danger ms-1"
                                            @click="hapus(row)" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2"
                    x-show="!loading && filtered > 0">
                    <div class="text-muted" style="font-size:13px">
                        Menampilkan
                        <strong x-text="(page - 1) * perPage + 1"></strong>–<strong
                            x-text="Math.min(page * perPage, filtered)"></strong>
                        dari <strong x-text="filtered"></strong> data
                        <template x-if="search">
                            <span> (dari <strong x-text="total"></strong> total)</span>
                        </template>
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
    <div id="entryToast" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999"></div>

</div>

<script>
function entryApp() {
    return {
        columns:  <?= $columnsJson ?>,
        baseUrl:  '/app/entry/<?= $entrySlug ?>',
        rows:     [],
        total:    0,
        filtered: 0,
        pages:    1,
        page:     1,
        perPage:  10,
        search:   '',
        loading:  false,

        get pageRange() {
            const delta = 2, range = [];
            for (let i = Math.max(1, this.page - delta); i <= Math.min(this.pages, this.page + delta); i++) {
                range.push(i);
            }
            return range;
        },

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.page, per_page: this.perPage, search: this.search,
            });
            try {
                const res = await axios.get(this.baseUrl + '/data?' + params);
                Object.assign(this, {
                    rows:     res.data.rows,
                    total:    res.data.total,
                    filtered: res.data.filtered,
                    pages:    res.data.pages,
                });
            } catch {
                this.toast('Gagal memuat data.', 'danger');
            } finally {
                this.loading = false;
            }
        },

        goPage(p) {
            if (p < 1 || (this.pages > 0 && p > this.pages)) return;
            this.page = p;
            this.load();
        },

        async hapus(row) {
            if (!confirm('Hapus data ini?\nTindakan ini tidak dapat dibatalkan.')) return;
            try {
                const res = await axios.post(this.baseUrl + '/delete', { id: row.id });
                if (res.data.success) {
                    this.toast(res.data.message, 'success');
                    if (this.rows.length === 1 && this.page > 1) this.page--;
                    await this.load();
                } else {
                    this.toast(res.data.message, 'danger');
                }
            } catch {
                this.toast('Gagal menghapus data.', 'danger');
            }
        },

        toast(msg, type = 'success') {
            const id   = 'toast_' + Date.now();
            const html = `
            <div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" role="alert" style="min-width:260px">
                <div class="d-flex">
                    <div class="toast-body">${msg}</div>
                    <button class="btn-close btn-close-white me-2 m-auto"
                        onclick="document.getElementById('${id}')?.remove()"></button>
                </div>
            </div>`;
            document.getElementById('entryToast').insertAdjacentHTML('beforeend', html);
            setTimeout(() => document.getElementById(id)?.remove(), 4000);
        },
    };
}
</script>
