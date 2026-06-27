(() => {
    'use strict';

    // =========================================
    // Ace Editor — CodeField
    // =========================================
    function initAceEditor(editorEl) {
        if (editorEl._ace) return; // sudah diinit

        const id    = editorEl.id;
        const ta    = document.getElementById(id + '_ta');
        if (!ta) return;

        if (typeof ace === 'undefined') {
            setTimeout(() => initAceEditor(editorEl), 200);
            return;
        }

        const mode   = editorEl.dataset.aceMode || 'text';
        const editor = ace.edit(id);
        editorEl._ace = editor;
        editor.setTheme('ace/theme/chrome');
        editor.session.setMode('ace/mode/' + mode);
        editor.setShowPrintMargin(false);
        editor.setFontSize(13);
        editor.setValue(ta.value, -1);

        // Ace → Alpine: dispatch 'input' so x-model picks up
        editor.session.on('change', () => {
            ta.value = editor.getValue();
            ta.dispatchEvent(new Event('input'));
        });
    }

    function initAllAceEditors(container) {
        container = container || document;
        container.querySelectorAll('[data-ace-field]').forEach(initAceEditor);
    }

    // =========================================
    // Date Field — DateField
    // =========================================
    function initDateField(inputEl) {
        if (inputEl._dateReady) return;
        inputEl._dateReady = true;

        const format    = inputEl.dataset.dateFormat || 'DD/MM/YYYY';
        const dbFormat  = inputEl.dataset.dbFormat || 'YYYY-MM-DD';
        const fieldName = inputEl.dataset.fieldName;

        // Init jQuery datepicker
        $(inputEl).datepicker({ format, autoclose: true });

        // Baca nilai dari Alpine & tampilkan di input visible
        function syncDisplay() {
            const alpineEl = inputEl.closest('[x-data]');
            if (!alpineEl || !window.Alpine) return;
            try {
                const data = Alpine.$data(alpineEl);
                const dbVal = data.fields && data.fields[fieldName];
                if (dbVal) {
                    const m = moment(dbVal, dbFormat);
                    if (m.isValid()) {
                        inputEl.value = m.format(format);
                    }
                }
            } catch (e) { /* belum siap */ }
        }

        // Pilih tanggal → konversi display → DB → update Alpine
        $(inputEl).on('change.dateField', function () {
            const displayVal = this.value;
            const m = moment(displayVal, format);
            if (m.isValid()) {
                const alpineEl = this.closest('[x-data]');
                if (alpineEl && window.Alpine) {
                    try {
                        const data = Alpine.$data(alpineEl);
                        data.fields[fieldName] = m.format(dbFormat);
                    } catch (e) { /* belum siap */ }
                }
            }
        });

        syncDisplay();
    }

    function initAllDateFields(container) {
        container = container || document;
        container.querySelectorAll('[data-date-field]').forEach(initDateField);
    }

    function syncDateFields(container) {
        if (!container) return;
        container.querySelectorAll('[data-date-field]').forEach((el) => {
            const format    = el.dataset.dateFormat || 'DD/MM/YYYY';
            const dbFormat  = el.dataset.dbFormat || 'YYYY-MM-DD';
            const fieldName = el.dataset.fieldName;
            const alpineEl  = el.closest('[x-data]');
            if (!alpineEl || !window.Alpine) return;
            try {
                const data  = Alpine.$data(alpineEl);
                const dbVal = data.fields && data.fields[fieldName];
                if (dbVal) {
                    const m = moment(dbVal, dbFormat);
                    if (m.isValid()) {
                        el.value = m.format(format);
                    }
                }
            } catch (e) { /* ignore */ }
        });
    }

    // =========================================
    // Public API
    // =========================================
    window.FormBuilder = {
        init:           (container) => { initAllAceEditors(container); initAllDateFields(container); },
        initAceEditors: (container) => initAllAceEditors(container),
        initDateFields: (container) => initAllDateFields(container),
        syncDateFields: (container) => syncDateFields(container),
    };

    // Auto-init begitu DOM siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => FormBuilder.init());
    } else {
        FormBuilder.init();
    }

})();