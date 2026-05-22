<div class="content" x-data="entryForm()" x-init="init()">

    <div class="page-heading mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="<?= $backUrl ?>"><?= $schema['name'] ?? 'Data' ?></a>
                </li>
                <li class="breadcrumb-item active"><?= $page_title ?></li>
            </ol>
        </nav>
        <h3><?= $page_title ?></h3>
    </div>

    <div class="page-content">
        <div class="row">
            <div class="col-md-7 col-lg-6">
                <div class="card">
                    <div class="card-body">

                        <!-- Alert -->
                        <div class="alert alert-danger" x-show="alertMsg" x-text="alertMsg"></div>

                        <!-- FormBuilder fields -->
                        <?= $fieldsHtml ?>

                        <!-- Actions -->
                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-primary" @click="submit()" :disabled="saving">
                                <template x-if="saving">
                                    <span><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan…</span>
                                </template>
                                <template x-if="!saving">
                                    <span><i class="bi bi-floppy me-1"></i>Simpan</span>
                                </template>
                            </button>
                            <a href="<?= $backUrl ?>" class="btn btn-outline-secondary">Batal</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function entryForm() {
    return {
        fields:    <?= $alpineJson ?>,
        id:        <?= $rowId ?>,
        submitUrl: <?= json_encode($submitUrl) ?>,
        backUrl:   <?= json_encode($backUrl) ?>,
        alertMsg:  '',
        saving:    false,

        init() {},

        async submit() {
            this.alertMsg = '';
            this.saving   = true;
            const body    = { ...this.fields };
            if (this.id) body.id = this.id;

            try {
                const res = await axios.post(this.submitUrl, body);
                if (res.data.success) {
                    window.location.href = this.backUrl;
                } else {
                    this.alertMsg = res.data.message;
                }
            } catch (e) {
                this.alertMsg = 'Terjadi kesalahan, silakan coba lagi.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
