<style>
#redis-browser { height: calc(100vh - 160px); min-height: 500px; }
#redis-sidebar {
    width: 260px; min-width: 260px;
    border-right: 1px solid #dee2e6;
    display: flex; flex-direction: column; overflow: hidden;
    transition: width .2s ease, min-width .2s ease;
}
#redis-sidebar.collapsed { width: 0; min-width: 0; border-right: none; }
#redis-sidebar .key-tree { flex: 1; overflow-y: auto; font-size: 13px; }
#redis-main { flex: 1; overflow-y: auto; min-width: 0; }
.tree-node { cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-radius: 4px; line-height: 1.7; user-select: none; }
.tree-node:hover { background: #f0f4ff; }
.tree-node.active { background: #e0e9ff; color: #2563eb; font-weight: 500; }
.tree-node.is-group { color: #555; font-weight: 600; }
.redis-ace { height: 320px; border: 1px solid #dee2e6; border-radius: .375rem; font-size: 13px; }
.type-badge-string  { background: #2563eb; }
.type-badge-hash    { background: #16a34a; }
.type-badge-list    { background: #d97706; }
.type-badge-set     { background: #0891b2; }
.type-badge-zset    { background: #7c3aed; }
.type-badge-unknown { background: #6b7280; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.spin { display: inline-block; animation: spin .8s linear infinite; }

/* Mobile: sidebar becomes a fixed drawer overlay */
@media (max-width: 767.98px) {
    #redis-browser { height: auto; min-height: 0; flex-direction: column; }
    #redis-sidebar {
        position: fixed; top: 0; left: 0; bottom: 0; z-index: 1055;
        width: 280px !important; min-width: 280px !important;
        background: #fff; box-shadow: 4px 0 16px rgba(0,0,0,.18);
        border-right: 1px solid #dee2e6 !important;
        transform: translateX(-100%);
        transition: transform .25s ease;
    }
    #redis-sidebar.mobile-open { transform: translateX(0); }
    #redis-sidebar.collapsed { transform: translateX(-100%); }
    #redis-sidebar-backdrop {
        display: none; position: fixed; inset: 0; z-index: 1054;
        background: rgba(0,0,0,.35);
    }
    #redis-sidebar-backdrop.show { display: block; }
    #redis-main { padding: 1rem !important; }
}
</style>

<div class="content mb-5" x-data="redisBrowser()" x-init="init()">

    <div class="page-heading mb-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h3 class="mb-0"><?= $page_title ?></h3>
        </div>
        <div class="d-flex justify-content-between gap-2 flex-wrap">
            <button class="btn btn-sm btn-outline-secondary" @click="toggleSidebar()" :title="sidebarOpen ? 'Tutup sidebar' : 'Buka sidebar'">
                <i class="bi" :class="sidebarOpen ? 'bi-layout-sidebar-reverse' : 'bi-layout-sidebar'"></i>
            </button>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <div class="d-flex align-items-center gap-1">
                    <label class="text-muted mb-0" style="font-size:12px;white-space:nowrap"><i class="bi bi-database me-1"></i>DB</label>
                    <select class="form-select form-select-sm" style="width:80px" x-model.number="selectedDb" @change="onDbChange()">
                        <template x-for="n in 16" :key="n - 1">
                            <option :value="n - 1" x-text="n - 1"></option>
                        </template>
                    </select>
                </div>
                <button class="btn btn-sm btn-outline-primary" @click="loadKeys()" :disabled="loadingKeys">
                    <i class="bi bi-arrow-clockwise me-1" :class="{'spin': loadingKeys}"></i>Refresh
                </button>
                <button class="btn btn-sm btn-success" @click="showNewKey = !showNewKey">
                    <i class="bi me-1" :class="showNewKey ? 'bi-x-lg' : 'bi-plus-lg'"></i>
                    <span x-text="showNewKey ? 'Batal' : 'New Key'"></span>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="flushDb()">
                    <i class="bi bi-trash3 me-1"></i>Flush DB
                </button>
            </div>
        </div>
    </div>

    <!-- Global alert -->
    <div id="redisAlert" class="alert alert-dismissible fade d-none mb-2" role="alert">
        <span id="redisAlertMsg"></span>
        <button type="button" class="btn-close" onclick="hideRedisAlert()"></button>
    </div>

    <!-- Two-column layout -->
    <div id="redis-browser" class="d-flex border rounded bg-white">

        <!-- Mobile backdrop -->
        <div id="redis-sidebar-backdrop" :class="{ show: sidebarOpen && isMobile }" @click="closeSidebar()"></div>

        <!-- ── Sidebar ── -->
        <div id="redis-sidebar" class="p-0"
             :class="{ collapsed: !sidebarOpen && !isMobile, 'mobile-open': sidebarOpen && isMobile }">
            <!-- Search bar -->
            <div class="px-2 pt-2 pb-1 border-bottom d-flex flex-column gap-1">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-semibold" style="font-size:12px">Key Browser</span>
                    <button class="btn btn-sm p-0 px-1 text-muted" @click="closeSidebar()" title="Tutup">
                        <i class="bi bi-x-lg" style="font-size:12px"></i>
                    </button>
                </div>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm" placeholder="Pattern (e.g. user:*)"
                           x-model="pattern" @keydown.enter="loadKeys()">
                    <button class="btn btn-outline-secondary" @click="loadKeys()" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="mt-1 px-1 text-muted" style="font-size:11px">
                    <template x-if="loadingKeys">
                        <span><i class="bi bi-hourglass-split me-1"></i>Loading…</span>
                    </template>
                    <template x-if="!loadingKeys">
                        <span x-text="`${allKeys.length} key(s)`"></span>
                    </template>
                </div>
                <div class="mt-1">
                    <input type="text" class="form-control form-control-sm" placeholder="Filter keys…"
                           x-model="filterText">
                </div>
            </div>

            <!-- Key Tree -->
            <div class="key-tree px-1 py-1">
                <template x-if="allKeys.length === 0 && !loadingKeys">
                    <div class="text-center text-muted py-4" style="font-size:12px">
                        <i class="bi bi-inbox mb-1 fs-5"></i> <br>
                        No keys found
                    </div>
                </template>
                <template x-for="node in flatTree" :key="node.path">
                    <div class="tree-node py-0"
                         :style="`padding-left: ${node.level * 20 + 6}px`"
                         :class="{ active: node.isLeaf && selectedKey === node.fullKey, 'is-group': !node.isLeaf }"
                         @click="node.isLeaf ? selectKey(node.fullKey) : toggleNode(node.path)">
                        <!-- expand/collapse icon -->
                        <template x-if="!node.isLeaf">
                            <i class="bi me-1" style="font-size:10px"
                               :class="expandedNodes[node.path] ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                        </template>
                        <template x-if="node.isLeaf && !node.hasChildren">
                            <i class="bi bi-key me-1 text-secondary" style="font-size:10px"></i>
                        </template>
                        <template x-if="node.isLeaf && node.hasChildren">
                            <i class="bi bi-key-fill me-1" style="font-size:10px"
                               :class="expandedNodes[node.path] ? 'text-primary' : 'text-secondary'"></i>
                        </template>
                        <span x-text="node.label" :title="node.isLeaf ? node.fullKey : node.path"></span>
                        <template x-if="!node.isLeaf">
                            <span class="badge bg-secondary ms-1" style="font-size:9px" x-text="node.childCount"></span>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- ── Main content ── -->
        <div id="redis-main" class="p-4">

            <!-- Empty state -->
            <template x-if="!selectedKey && !loadingKey && !showNewKey">
                <div class="text-muted py-5">
                    <p class="text-center mb-0">
                        <i class="bi bi-database fs-1 mb-3 opacity-25"></i> <br>
                        Pilih key dari sidebar untuk melihat atau mengedit nilainya.
                    </p>
                </div>
            </template>

            <!-- Loading key -->
            <template x-if="loadingKey && !showNewKey">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm me-2"></div> Memuat data key…
                </div>
            </template>

            <!-- Key editor -->
            <template x-if="keyInfo && !loadingKey && !showNewKey">
                <div>
                    <!-- Header row: key name + type + ttl + actions -->
                    <div class="d-flex align-items-start justify-content-between mb-3 gap-3 flex-wrap">
                        <div class="flex-grow-1">
                            <label class="form-label text-muted mb-1" style="font-size:11px;text-transform:uppercase">Key Name</label>
                            <input type="text" class="form-control form-control-sm font-monospace" x-model="editKey" placeholder="key name">
                        </div>
                        <div style="min-width:100px">
                            <label class="form-label text-muted mb-1" style="font-size:11px;text-transform:uppercase">TTL (sec)</label>
                            <input type="number" class="form-control form-control-sm" x-model="editTtl" placeholder="no expiry" min="1">
                        </div>
                        <div class="d-flex align-items-end gap-2 pb-1">
                            <span class="badge text-white"
                                  :class="`type-badge-${keyInfo.type}`"
                                  x-text="keyInfo.type.toUpperCase()"></span>
                            <span class="badge" :class="keyInfo.ttl < 0 ? 'bg-secondary' : 'bg-warning text-dark'"
                                  x-text="keyInfo.ttl < 0 ? '∞ persist' : `TTL: ${keyInfo.ttl}s`"></span>
                        </div>
                    </div>

                    <!-- ─ String editor ─ -->
                    <div x-show="keyInfo.type === 'string'" x-effect="keyInfo.type === 'string' && $nextTick(() => syncRedisAce(editValue, v => editValue = v))">
                        <label class="form-label text-muted mb-1" style="font-size:11px;text-transform:uppercase">Value</label>
                        <div id="redis-ace-editor" class="redis-ace"></div>
                        <textarea x-model="editValue" class="d-none" id="redis-ace-ta"></textarea>
                    </div>

                    <!-- ─ Hash editor ─ -->
                    <div x-show="keyInfo.type === 'hash'">
                        <label class="form-label text-muted mb-1" style="font-size:11px;text-transform:uppercase">Fields</label>
                        <table class="table table-sm table-bordered mb-2" style="font-size:13px">
                            <thead class="table-light"><tr><th>Field</th><th>Value</th><th style="width:40px"></th></tr></thead>
                            <tbody>
                                <template x-for="(row, i) in editHash" :key="i">
                                    <tr>
                                        <td><input type="text" class="form-control form-control-sm font-monospace" x-model="row.field"></td>
                                        <td><input type="text" class="form-control form-control-sm font-monospace" x-model="row.val"></td>
                                        <td class="text-center"><button class="btn btn-sm btn-outline-danger py-0 px-1" @click="removeHashRow(i)"><i class="bi bi-x"></i></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <button class="btn btn-sm btn-outline-secondary" @click="addHashRow()">
                            <i class="bi bi-plus me-1"></i>Add Field
                        </button>
                    </div>

                    <!-- ─ List / Set editor ─ -->
                    <div x-show="keyInfo.type === 'list' || keyInfo.type === 'set'">
                        <label class="form-label text-muted mb-1" style="font-size:11px;text-transform:uppercase"
                               x-text="keyInfo.type === 'list' ? 'Items (ordered)' : 'Members'"></label>
                        <div class="d-flex flex-column gap-1 mb-2">
                            <template x-for="(row, i) in editList" :key="i">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text text-muted font-monospace" style="font-size:11px;min-width:32px" x-text="i + 1"></span>
                                    <input type="text" class="form-control font-monospace" x-model="row.val">
                                    <button class="btn btn-outline-danger" @click="removeListRow(i)"><i class="bi bi-x"></i></button>
                                </div>
                            </template>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" @click="addListRow()">
                            <i class="bi bi-plus me-1"></i>Add Item
                        </button>
                    </div>

                    <!-- ─ ZSet editor ─ -->
                    <div x-show="keyInfo.type === 'zset'">
                        <label class="form-label text-muted mb-1" style="font-size:11px;text-transform:uppercase">Members &amp; Scores</label>
                        <table class="table table-sm table-bordered mb-2" style="font-size:13px">
                            <thead class="table-light"><tr><th>Member</th><th style="width:120px">Score</th><th style="width:40px"></th></tr></thead>
                            <tbody>
                                <template x-for="(row, i) in editZset" :key="i">
                                    <tr>
                                        <td><input type="text" class="form-control form-control-sm font-monospace" x-model="row.member"></td>
                                        <td><input type="number" class="form-control form-control-sm" x-model="row.score" step="any"></td>
                                        <td class="text-center"><button class="btn btn-sm btn-outline-danger py-0 px-1" @click="removeZsetRow(i)"><i class="bi bi-x"></i></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <button class="btn btn-sm btn-outline-secondary" @click="addZsetRow()">
                            <i class="bi bi-plus me-1"></i>Add Member
                        </button>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button class="btn btn-primary btn-sm" @click="save()" :disabled="saving">
                            <template x-if="saving">
                                <span><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
                            </template>
                            <template x-if="!saving">
                                <span><i class="bi bi-floppy me-1"></i>Simpan</span>
                            </template>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" @click="deleteKey()">
                            <i class="bi bi-trash me-1"></i>Hapus Key
                        </button>
                    </div>
                </div>
            </template>
            <!-- ── New Key inline form ── -->
            <div x-show="showNewKey" x-cloak>
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Key Baru</h5>
                    <button class="btn btn-sm btn-outline-secondary" @click="showNewKey = false">
                        <i class="bi bi-x me-1"></i>Batal
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Key Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" x-model="newKey.key"
                               placeholder="e.g. app:config:site" @keydown.enter="createKey()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" x-model="newKey.type">
                            <option value="string">string</option>
                            <option value="hash">hash</option>
                            <option value="list">list</option>
                            <option value="set">set</option>
                            <option value="zset">zset</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">TTL (detik)</label>
                        <input type="number" class="form-control" x-model="newKey.ttl" min="1" placeholder="kosong = persist">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label">Initial Value</label>
                    <textarea class="form-control font-monospace" rows="5" x-model="newKey.value"
                        :placeholder="newKey.type === 'string' ? 'Nilai string atau JSON...' : 'JSON array, e.g. [\"a\",\"b\"] atau [{\"field\":\"f\",\"val\":\"v\"}]'"></textarea>
                    <div class="form-text" x-show="newKey.type !== 'string'">
                        hash: <code>[{"field":"f","val":"v"}]</code> &ensp;
                        list/set: <code>["a","b"]</code> &ensp;
                        zset: <code>[{"member":"m","score":1}]</code>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button class="btn btn-primary btn-sm" @click="createKey()">
                        <i class="bi bi-floppy me-1"></i>Simpan
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" @click="showNewKey = false; newKey = { key: '', type: 'string', value: '', ttl: '' }">
                        Batal
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
// ── Ace editor singleton for the Redis value editor ───────────────
var redisAce = null;

function syncRedisAce(value, onChange) {
    var el = document.getElementById('redis-ace-editor');
    if (!el) return;
    if (typeof ace === 'undefined') { setTimeout(() => syncRedisAce(value, onChange), 150); return; }

    if (!redisAce) {
        redisAce = ace.edit('redis-ace-editor');
        redisAce.setTheme('ace/theme/chrome');
        redisAce.setShowPrintMargin(false);
        redisAce.setFontSize(13);
        redisAce.session.setUseWrapMode(true);
        redisAce.setOption('wrap', true);
        redisAce.session.on('change', () => onChange(redisAce.getValue()));
    }

    // Detect JSON to set mode
    var mode = 'ace/mode/text';
    try { JSON.parse(value); mode = 'ace/mode/json'; } catch (e) {}
    redisAce.session.setMode(mode);

    // Only update if value differs to avoid cursor jump
    if (redisAce.getValue() !== value) {
        redisAce.setValue(value ?? '', -1);
    }
}

// ── Alert helpers ─────────────────────────────────────────────────
var redisAlertTimer;
function showRedisAlert(type, msg) {
    clearTimeout(redisAlertTimer);
    var el = document.getElementById('redisAlert');
    var msgEl = document.getElementById('redisAlertMsg');
    el.className = 'alert alert-' + type + ' alert-dismissible fade show mb-2';
    msgEl.textContent = msg;
    redisAlertTimer = setTimeout(hideRedisAlert, 5000);
}
function hideRedisAlert() {
    var el = document.getElementById('redisAlert');
    el.classList.remove('show');
    setTimeout(() => el.classList.add('d-none'), 300);
}

// ── Main Alpine component ─────────────────────────────────────────
function redisBrowser() {
    return {
        // Sidebar state
        sidebarOpen: true,
        isMobile: false,
        selectedDb:   0,
        pattern:      '*',
        allKeys:      [],
        filterText:   '',
        loadingKeys:  false,
        expandedNodes: {},

        // Key detail
        selectedKey: null,
        keyInfo:     null,
        loadingKey:  false,
        saving:      false,

        // Edit state
        editKey:   '',
        editTtl:   '',
        editValue: '',
        editHash:  [],
        editList:  [],
        editZset:  [],

        // New key modal
        showNewKey: false,
        newKey: { key: '', type: 'string', value: '', ttl: '' },

        // ── Computed ──────────────────────────────────────────────
        get filteredKeys() {
            if (!this.filterText) return this.allKeys;
            const q = this.filterText.toLowerCase();
            return this.allKeys.filter(k => k.toLowerCase().includes(q));
        },

        get flatTree() {
            const tree = this.buildTree(this.filteredKeys);
            return this.flattenTree(tree);
        },

        // ── Lifecycle ─────────────────────────────────────────────
        async init() {
            this.isMobile = window.innerWidth < 768;
            this.sidebarOpen = !this.isMobile;
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
                if (!this.isMobile) this.sidebarOpen = true;
            });
            await this.loadKeys();
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        closeSidebar() {
            if (this.isMobile) this.sidebarOpen = false;
        },

        // ── Sidebar ───────────────────────────────────────────────
        async onDbChange() {
            this.selectedKey = null;
            this.keyInfo     = null;
            this.allKeys     = [];
            await this.loadKeys();
        },

        async loadKeys() {
            this.loadingKeys = true;
            try {
                const res = await axios.get('/panel/redis/keys', { params: { pattern: this.pattern, db: this.selectedDb } });
                if (res.data.success) {
                    this.allKeys = res.data.keys;
                    // Auto-expand if few keys
                    if (this.allKeys.length <= 50) {
                        const tree = this.buildTree(this.allKeys);
                        this.autoExpand(tree, '', 1);
                    }
                }
            } catch (e) {
                showRedisAlert('danger', 'Gagal memuat daftar key.');
            } finally {
                this.loadingKeys = false;
            }
        },

        buildTree(keys) {
            const root = {};
            for (const key of keys) {
                const parts = key.split(':');
                let node = root;
                for (let i = 0; i < parts.length; i++) {
                    const part = parts[i];
                    if (!node[part]) {
                        node[part] = { _children: {}, _isLeaf: false, _fullKey: '' };
                    }
                    if (i === parts.length - 1) {
                        node[part]._isLeaf = true;
                        node[part]._fullKey = key;
                    }
                    node = node[part]._children;
                }
            }
            return root;
        },

        autoExpand(node, prefix, maxLevel) {
            for (const [part, data] of Object.entries(node)) {
                if (part.startsWith('_')) continue;
                const path = prefix ? `${prefix}:${part}` : part;
                const children = data._children || {};
                const childCount = Object.keys(children).filter(k => !k.startsWith('_')).length;
                if (childCount > 0 && maxLevel > 0) {
                    this.expandedNodes[path] = true;
                    this.autoExpand(children, path, maxLevel - 1);
                }
            }
        },

        flattenTree(node, prefix = '', level = 0) {
            const result = [];
            for (const [part, data] of Object.entries(node)) {
                if (part.startsWith('_')) continue;
                const path = prefix ? `${prefix}:${part}` : part;
                const children = data._children || {};
                const childKeys = Object.keys(children).filter(k => !k.startsWith('_'));
                const hasChildren = childKeys.length > 0;
                result.push({
                    label:      part,
                    path,
                    level,
                    isLeaf:     data._isLeaf,
                    fullKey:    data._fullKey,
                    hasChildren,
                    childCount: hasChildren ? childKeys.length : 0,
                });
                if (hasChildren && this.expandedNodes[path]) {
                    result.push(...this.flattenTree(children, path, level + 1));
                }
            }
            return result;
        },

        toggleNode(path) {
            this.expandedNodes[path] = !this.expandedNodes[path];
            this.expandedNodes = { ...this.expandedNodes }; // trigger reactivity
        },

        // ── Key selection ─────────────────────────────────────────
        async selectKey(key) {
            if (this.selectedKey === key && this.keyInfo) { this.closeSidebar(); return; }
            this.selectedKey = key;
            this.loadingKey  = true;
            this.keyInfo     = null;
            redisAce         = null; // force Ace re-init for new key
            this.closeSidebar(); // close drawer on mobile
            try {
                const res = await axios.get('/panel/redis/get', { params: { key, db: this.selectedDb } });
                if (res.data.success) {
                    this.keyInfo  = res.data;
                    this.editKey  = res.data.key;
                    this.editTtl  = res.data.ttl >= 0 ? String(res.data.ttl) : '';
                    this.initEditState(res.data.type, res.data.value);
                } else {
                    showRedisAlert('danger', res.data.message ?? 'Gagal memuat key.');
                }
            } catch (e) {
                showRedisAlert('danger', 'Gagal memuat key.');
            } finally {
                this.loadingKey = false;
            }
        },

        initEditState(type, value) {
            this.editValue = '';
            this.editHash  = [];
            this.editList  = [];
            this.editZset  = [];

            if (type === 'string') {
                try {
                    const parsed = JSON.parse(value);
                    this.editValue = JSON.stringify(parsed, null, 2);
                } catch {
                    this.editValue = value ?? '';
                }
            } else if (type === 'hash') {
                this.editHash = Object.entries(value ?? {}).map(([field, val]) => ({ field, val: String(val) }));
            } else if (type === 'list' || type === 'set') {
                this.editList = (value ?? []).map(v => ({ val: String(v) }));
            } else if (type === 'zset') {
                // phpredis WITHSCORES returns { member: score } object
                this.editZset = Object.entries(value ?? {}).map(([member, score]) => ({ member, score }));
            }
        },

        // ── Save ──────────────────────────────────────────────────
        async save() {
            if (!this.keyInfo) return;
            this.saving = true;

            const type = this.keyInfo.type;
            let value;
            if (type === 'string')             value = this.editValue;
            else if (type === 'hash')          value = this.editHash;
            else if (type === 'list' || type === 'set') value = this.editList.map(r => r.val);
            else if (type === 'zset')          value = this.editZset;

            const ttl = this.editTtl ? parseInt(this.editTtl) : null;

            try {
                // Rename first if key name changed
                if (this.editKey !== this.selectedKey) {
                    const rv = await axios.post('/panel/redis/rename', { old_key: this.selectedKey, new_key: this.editKey, db: this.selectedDb });
                    if (!rv.data.success) throw new Error(rv.data.message);
                    this.selectedKey = this.editKey;
                }

                const sv = await axios.post('/panel/redis/set', { key: this.editKey, type, value, ttl, db: this.selectedDb });
                if (!sv.data.success) throw new Error(sv.data.message);

                // Refresh TTL display
                this.keyInfo.ttl = ttl ?? -1;
                await this.loadKeys();
                showRedisAlert('success', 'Data berhasil disimpan.');
            } catch (e) {
                showRedisAlert('danger', e.message || 'Gagal menyimpan data.');
            } finally {
                this.saving = false;
            }
        },

        // ── Delete ────────────────────────────────────────────────
        async deleteKey() {
            if (!this.selectedKey) return;
            if (!confirm(`Hapus key "${this.selectedKey}"?\nTindakan ini tidak dapat dibatalkan.`)) return;
            try {
                await axios.post('/panel/redis/delete', { key: this.selectedKey, db: this.selectedDb });
                this.selectedKey = null;
                this.keyInfo     = null;
                await this.loadKeys();
                showRedisAlert('success', 'Key berhasil dihapus.');
            } catch (e) {
                showRedisAlert('danger', 'Gagal menghapus key.');
            }
        },

        // ── Flush DB ─────────────────────────────────────────────
        async flushDb() {
            if (!confirm('Hapus SEMUA key di database Redis ini?\nTindakan ini tidak dapat dibatalkan!')) return;
            try {
                await axios.post('/panel/redis/flush', { db: this.selectedDb });
                this.selectedKey = null;
                this.keyInfo     = null;
                this.allKeys     = [];
                showRedisAlert('success', 'Database Redis berhasil dikosongkan.');
            } catch (e) {
                showRedisAlert('danger', 'Gagal mengosongkan database.');
            }
        },

        // ── Create new key ────────────────────────────────────────
        async createKey() {
            const { key, type, value, ttl } = this.newKey;
            if (!key.trim()) { showRedisAlert('warning', 'Key name tidak boleh kosong.'); return; }

            let parsedValue = value;
            if (type !== 'string' && value.trim()) {
                try { parsedValue = JSON.parse(value); }
                catch { showRedisAlert('danger', 'Format JSON untuk value tidak valid.'); return; }
            }

            try {
                const res = await axios.post('/panel/redis/set', {
                    key: key.trim(), type, value: parsedValue,
                    ttl: ttl ? parseInt(ttl) : null,
                    db: this.selectedDb,
                });
                if (!res.data.success) throw new Error(res.data.message);
                this.showNewKey = false;
                this.newKey     = { key: '', type: 'string', value: '', ttl: '' };
                await this.loadKeys();
                await this.selectKey(key.trim());
                showRedisAlert('success', 'Key baru berhasil ditambahkan.');
            } catch (e) {
                showRedisAlert('danger', e.message || 'Gagal menambah key.');
            }
        },

        // ── Row helpers ───────────────────────────────────────────
        addHashRow()       { this.editHash.push({ field: '', val: '' }); },
        removeHashRow(i)   { this.editHash.splice(i, 1); },
        addListRow()       { this.editList.push({ val: '' }); },
        removeListRow(i)   { this.editList.splice(i, 1); },
        addZsetRow()       { this.editZset.push({ member: '', score: 0 }); },
        removeZsetRow(i)   { this.editZset.splice(i, 1); },
    };
}
</script>
