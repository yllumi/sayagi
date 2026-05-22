<div class="content mb-5" x-data="settingPage()" x-init="init()">

    <div class="page-heading mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h3><?= $page_title ?></h3>
        </div>
    </div>

    <div class="page-content">

        <!-- Alert -->
        <div id="settingAlert" class="alert alert-dismissible fade d-none" role="alert">
            <span id="settingAlertMsg"></span>
            <button type="button" class="btn-close" onclick="hideAlert()"></button>
        </div>

        <?php if (empty($groups)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-gear fs-2 d-block mb-2"></i>
                Tidak ada file konfigurasi setting ditemukan di <code>config/plugin/panel/settings/</code>.
            </div>
        <?php else: ?>

            <div class="d-flex gap-0 align-items-start">

                <!-- Side tab navigation -->
                <ul class="nav flex-column nav-pills me-0" id="settingTabs" style="min-width:180px;border-right:1px solid #dee2e6">
                    <?php foreach ($groups as $i => $group): ?>
                        <li class="nav-item">
                            <button
                                type="button"
                                class="nav-link rounded-0 w-100 text-start<?= $i === 0 ? ' active' : '' ?>"
                                data-tab="<?= htmlspecialchars($group['slug']) ?>"
                                @click="setTab('<?= htmlspecialchars($group['slug']) ?>')">
                                <?= htmlspecialchars($group['name']) ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab panels -->
                <div class="tab-content flex-grow-1 bg-white border border-start-0 p-4" style="min-width:0">
                    <?php foreach ($groups as $i => $group): ?>
                        <div
                            id="tab-panel-<?= htmlspecialchars($group['slug']) ?>"
                            class="setting-panel<?= $i === 0 ? '' : ' d-none' ?>"
                            x-data="panelForm('<?= htmlspecialchars($group['slug']) ?>')">

                            <form @submit.prevent="save()" autocomplete="off">

                                <!-- Loading skeleton -->
                                <div x-show="loading" class="py-4 text-center text-muted">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Memuat pengaturan...
                                </div>

                                <!-- FormBuilder-rendered fields -->
                                <div x-show="!loading">
                                    <?= $group['html'] ?>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex align-items-center gap-3">
                                    <button type="submit" class="btn btn-primary" :disabled="saving">
                                        <span class="spinner-border spinner-border-sm me-1" x-show="saving" role="status"></span>
                                        <i class="bi bi-save me-1" x-show="!saving"></i>
                                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Pengaturan'">Simpan Pengaturan</span>
                                    </button>
                                </div>

                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

        <?php endif; ?>

    </div><!-- /.page-content -->
</div>

<script>
function settingPage() {
    return {
        activeTab: '<?= htmlspecialchars($groups[0]['slug'] ?? '') ?>',

        init() {
            this.highlightTab(this.activeTab);
        },

        setTab(slug) {
            document.querySelectorAll('.setting-panel').forEach(el => el.classList.add('d-none'));
            const panel = document.getElementById('tab-panel-' + slug);
            if (panel) {
                panel.classList.remove('d-none');
                // Ace editors in hidden panels render at zero size; resize after reveal
                if (typeof ace !== 'undefined') {
                    panel.querySelectorAll('[data-ace-field]').forEach(div => {
                        if (div._ace) div._ace.resize();
                    });
                }
            }
            this.activeTab = slug;
            this.highlightTab(slug);
        },

        highlightTab(slug) {
            document.querySelectorAll('#settingTabs .nav-link').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === slug);
            });
        },
    };
}

function panelForm(group) {
    return {
        fields:  {},
        saving:  false,
        loading: true,

        async init() {
            toastr.options = {
            "positionClass": "toast-bottom-right",
            };

            try {
                const res = await axios.get(`<?= site_url('/panel/setting/data') ?>`, { params: { group } });
                if (res.data && res.data.success) {
                    this.fields = res.data.fields ?? {};
                    // Re-sync Ace editors whose content was empty before the fetch
                    await this.$nextTick();
                    this.$el.querySelectorAll('[data-ace-field]').forEach(div => {
                        if (div._ace) {
                            div._ace.setValue(this.fields[div.dataset.aceField] ?? '', -1);
                        }
                    });
                }
            } catch (e) {
                toastr.danger('Gagal memuat data pengaturan.', );
            } finally {
                this.loading = false;
            }
        },

        async save() {
            this.saving = true;
            try {
                const res = await axios.post(`<?= site_url('/panel/setting/save') ?>`, { group, ...this.fields });
                if (res.data && res.data.success) {
                    toastr.success(res.data.message || 'Pengaturan berhasil disimpan.', );
                } else {
                    toastr.danger((res.data && res.data.message) || 'Gagal menyimpan pengaturan.', );
                }
            } catch (e) {
                toastr.danger('Terjadi kesalahan saat menyimpan.', );
            } finally {
                this.saving = false;
            }
        },
    };
}

function hideAlert() {
    const el = document.getElementById('settingAlert');
    if (! el) return;
    el.classList.remove('show');
    setTimeout(() => el.classList.add('d-none'), 300);
}
</script>