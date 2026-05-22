<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\libraries\FormBuilder\FormBuilder;
use Symfony\Component\Yaml\Yaml;
use support\Request;
use support\Db;

class EntryController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module'     => 'entry',
        'submodule'  => 'entry',
    ];

    // ── Schema loader ────────────────────────────────────────────
    /**
     * Find and parse the YAML schema for a given slug.
     * Searches across all plugins: plugin/{*}/panel/entry/{slug}.yml
     */
    private function loadSchema(string $slug): array
    {
        $files = glob(base_path("plugin/*/panel/entry/{$slug}.yml"));

        if (empty($files)) {
            throw new \RuntimeException("Schema '{$slug}.yml' tidak ditemukan.");
        }

        return Yaml::parseFile($files[0]);
    }

    private function db(string $table)
    {
        return Db::table($table)->whereNull('deleted_at');
    }

    // ── GET /panel/entry/{slug} ────────────────────────────────────
    public function index(Request $request, string $slug)
    {
        $schema = $this->loadSchema($slug);

        $this->data['page_title'] = $schema['name'] ?? ucfirst($slug);
        $this->data['schema']     = $schema;
        $this->data['slug']       = $slug;

        return render('entry/index', $this->data, 'admin', 'panel');
    }

    // ── GET /panel/entry/{slug}/data ───────────────────────────────
    public function data(Request $request, string $slug)
    {
        $schema  = $this->loadSchema($slug);
        $table   = $schema['table'];

        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 10));
        $search  = trim($request->input('search', ''));
        $offset  = ($page - 1) * $perPage;

        $base = $this->db($table)->select("{$table}.*");

        // LEFT JOIN for every field that has a relation
        foreach ($this->relationFields($schema) as $fieldDef) {
            $rel = $fieldDef['relation'];
            $base->leftJoin(
                $rel['table'],
                "{$table}.{$fieldDef['field']}",
                '=',
                "{$rel['table']}.{$rel['value']}"
            );
            $base->addSelect("{$rel['table']}.{$rel['display']} AS {$fieldDef['field']}_display");
        }

        if ($search) {
            $searchable = $this->searchableFields($schema);
            if (!empty($searchable)) {
                $base->where(function ($q) use ($searchable, $search) {
                    foreach ($searchable as $i => $col) {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $q->{$method}($col, 'like', "%{$search}%");
                    }
                });
            }
        }

        $total    = $this->db($table)->count();
        $filtered = (clone $base)->count();
        $rows     = (clone $base)
            ->orderBy('id', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return json([
            'rows'     => $rows,
            'total'    => $total,
            'filtered' => $filtered,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($filtered / $perPage),
        ]);
    }

    // ── GET /panel/entry/{slug}/create ─────────────────────────────
    public function create(Request $request, string $slug)
    {
        $schema = $this->loadSchema($slug);

        $this->data['page_title'] = 'Tambah ' . ($schema['name'] ?? ucfirst($slug));
        $this->data               = array_merge($this->data, $this->buildForm($schema, $slug));

        return render('entry/form', $this->data, 'admin', 'panel');
    }

    // ── GET /panel/entry/{slug}/edit?id=X ──────────────────────────
    public function edit(Request $request, string $slug)
    {
        $schema = $this->loadSchema($slug);
        $table  = $schema['table'];
        $id     = (int) $request->input('id', 0);
        $row    = $this->db($table)->where('id', $id)->first();

        if (!$row) {
            return redirect(site_url("/panel/entry/{$slug}"));
        }

        $this->data['page_title'] = 'Edit ' . ($schema['name'] ?? ucfirst($slug));
        $this->data               = array_merge($this->data, $this->buildForm($schema, $slug, $row));

        return render('entry/form', $this->data, 'admin', 'panel');
    }

    // ── POST /panel/entry/{slug}/store ─────────────────────────────
    public function store(Request $request, string $slug)
    {
        $schema = $this->loadSchema($slug);
        $table  = $schema['table'];

        $input = $this->extractInput($request, $schema);
        if (isset($input['error'])) {
            return json(['success' => 0, 'message' => $input['error']]);
        }

        $input['created_at'] = date('Y-m-d H:i:s');
        Db::table($table)->insert($input);

        return json(['success' => 1, 'message' => ($schema['name'] ?? 'Data') . ' berhasil ditambahkan.']);
    }

    // ── POST /panel/entry/{slug}/update ────────────────────────────
    public function update(Request $request, string $slug)
    {
        $schema = $this->loadSchema($slug);
        $table  = $schema['table'];

        $raw = json_decode($request->rawBody(), true) ?: $request->post() ?? [];
        $id  = (int) ($raw['id'] ?? 0);

        if (!$id) {
            return json(['success' => 0, 'message' => 'ID tidak valid.']);
        }

        $input = $this->extractInput($request, $schema);
        if (isset($input['error'])) {
            return json(['success' => 0, 'message' => $input['error']]);
        }

        $input['updated_at'] = date('Y-m-d H:i:s');
        $affected = $this->db($table)->where('id', $id)->update($input);

        if (!$affected) {
            return json(['success' => 0, 'message' => 'Data tidak ditemukan atau tidak ada perubahan.']);
        }

        return json(['success' => 1, 'message' => ($schema['name'] ?? 'Data') . ' berhasil diperbarui.']);
    }

    // ── POST /panel/entry/{slug}/delete ────────────────────────────
    public function delete(Request $request, string $slug)
    {
        $schema = $this->loadSchema($slug);
        $table  = $schema['table'];

        $raw = json_decode($request->rawBody(), true) ?: $request->post() ?? [];
        $id  = (int) ($raw['id'] ?? 0);

        if (!$id) {
            return json(['success' => 0, 'message' => 'ID tidak valid.']);
        }

        $affected = $this->db($table)->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);

        if (!$affected) {
            return json(['success' => 0, 'message' => 'Data tidak ditemukan.']);
        }

        return json(['success' => 1, 'message' => ($schema['name'] ?? 'Data') . ' berhasil dihapus.']);
    }

    // ── Private: build FormBuilder data ─────────────────────────
    private function buildForm(array $schema, string $slug, $row = null): array
    {
        $isEdit      = $row !== null;
        $fieldSchema = $this->toFieldSchema($schema);
        $values      = $isEdit ? (array) $row : [];
        $fb          = (new FormBuilder())->schemaArray($fieldSchema);

        return [
            'fieldsHtml' => $fb->render($values),
            'alpineJson' => $fb->toAlpineJson(),
            'submitUrl'  => $isEdit ? site_url("/panel/entry/{$slug}/update") : site_url("/panel/entry/{$slug}/store"),
            'backUrl'    => site_url("/panel/entry/{$slug}"),
            'rowId'      => $isEdit ? (int) $row->id : 0,
            'schema'     => $schema,
        ];
    }

    // ── Private: convert YAML fields → FormBuilder schemaArray ──
    private function toFieldSchema(array $schema): array
    {
        $result = [];
        foreach ($schema['fields'] as $fieldDef) {
            $entry = [
                'name'  => $fieldDef['field'],
                'type'  => $fieldDef['form'],
                'label' => $fieldDef['label'] ?? '',
            ];
            if (!empty($fieldDef['required'])) {
                $entry['rules'] = 'required';
            }
            if (isset($fieldDef['options'])) {
                $entry['options'] = $fieldDef['options'];
            }
            if (isset($fieldDef['placeholder'])) {
                $entry['placeholder'] = $fieldDef['placeholder'];
            }
            if (isset($fieldDef['showif'])) {
                $entry['showif'] = $fieldDef['showif'];
            }
            if (isset($fieldDef['relation'])) {
                $entry['relation'] = $fieldDef['relation'];
            }
            $result[] = $entry;
        }

        return $result;
    }

    // ── Private: extract + validate request input from schema ────
    private function extractInput(Request $request, array $schema): array
    {
        $raw = json_decode($request->rawBody(), true);
        if (!is_array($raw) || empty($raw)) {
            $raw = $request->post() ?? [];
        }

        $data = [];
        foreach ($schema['fields'] as $fieldDef) {
            $field    = $fieldDef['field'];
            $required = !empty($fieldDef['required']);

            // If this field has a showif condition, check it server-side.
            // When the condition is NOT met the field is hidden, so skip it entirely.
            if (isset($fieldDef['showif'])) {
                $cond = $this->parseShowif($fieldDef['showif']);
                if ($cond && !$this->evaluateShowif($cond, $raw)) {
                    $data[$field] = null;
                    continue;
                }
            }

            $value = trim($raw[$field] ?? '');

            // For textarea / optional text: allow empty → null
            if ($value === '') {
                if ($required) {
                    return ['error' => ($fieldDef['label'] ?? $field) . ' wajib diisi.'];
                }
                $data[$field] = null;
                continue;
            }

            // Cast options (radio/select) to ensure value is in allowed list
            if (isset($fieldDef['options']) && !array_key_exists($value, $fieldDef['options'])) {
                return ['error' => 'Nilai ' . ($fieldDef['label'] ?? $field) . ' tidak valid.'];
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * Parse a showif definition into a normalised array with keys:
     *   field, operator, allowed (array of permitted values).
     * Returns null if the definition is unrecognised.
     *
     * Supported forms:
     *   Dict-style:     {profesi: [mahasiswa, dosen]}
     *   String-style:   "profesi == mahasiswa"
     *   Explicit-style: {field: profesi, value: mahasiswa}
     *                   {field: profesi, operator: '!=', value: mahasiswa}
     */
    private function parseShowif(mixed $showif): ?array
    {
        if (is_array($showif)) {
            if (isset($showif['field'])) {
                // Explicit-style
                return [
                    'field'    => $showif['field']    ?? '',
                    'operator' => $showif['operator'] ?? '==',
                    'allowed'  => [(string) ($showif['value'] ?? '')],
                ];
            }
            // Dict-style: {profesi: [mahasiswa, dosen]}
            $fieldName = (string) array_key_first($showif);
            return [
                'field'    => $fieldName,
                'operator' => 'in',
                'allowed'  => array_values((array) $showif[$fieldName]),
            ];
        }

        if (is_string($showif)) {
            $m = [];
            if (preg_match('/^(\w+)\s*(==|!=|>=|<=|>|<)\s*(.+)$/', trim($showif), $m)) {
                return ['field' => $m[1], 'operator' => $m[2], 'allowed' => [trim($m[3])]];
            }
        }

        return null;
    }

    /**
     * Evaluate a parsed showif condition against the submitted $raw values.
     */
    private function evaluateShowif(array $cond, array $raw): bool
    {
        $actual  = (string) ($raw[$cond['field']] ?? '');
        $allowed = $cond['allowed'];

        return match ($cond['operator']) {
            'in'    => in_array($actual, $allowed, true),
            '=='    => $actual == $allowed[0],
            '!='    => $actual != $allowed[0],
            '>'     => $actual >  $allowed[0],
            '<'     => $actual <  $allowed[0],
            '>='    => $actual >= $allowed[0],
            '<='    => $actual <= $allowed[0],
            default => in_array($actual, $allowed, true),
        };
    }

    // ── Private: fields that have a relation prop ───────────────
    private function relationFields(array $schema): array
    {
        return array_values(array_filter(
            $schema['fields'],
            static fn (array $f) => !empty($f['relation']['table'])
        ));
    }

    // ── Private: fields suitable for keyword search ──────────────
    private function searchableFields(array $schema): array
    {
        $searchTypes = ['text', 'email', 'mask', 'number'];
        $cols = [];
        foreach ($schema['fields'] as $fieldDef) {
            if (in_array($fieldDef['form'] ?? '', $searchTypes, true)) {
                $cols[] = $fieldDef['field'];
            }
        }

        return $cols;
    }
}
