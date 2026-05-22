<?php

namespace Yllumi\Sayagi\app\controller;

use support\Request;
use support\Db;
use Yllumi\Sayagi\libraries\FormBuilder\FormBuilder;
use Symfony\Component\Yaml\Yaml;

class SettingController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module'     => 'configuration',
        'submodule'  => 'setting',
    ];

    // ── GET /panel/setting/index ──────────────────────────────
    public function index(Request $request)
    {
        $this->data['page_title'] = 'Pengaturan';
        $this->data['groups']     = $this->buildGroups();

        return render('setting/index', $this->data);
    }

    // ── GET /panel/setting/data  (Alpine AJAX) ─────────────
    public function data(Request $request)
    {
        $group = trim($request->input('group', ''));

        if ($group === '') {
            return json(['success' => 0, 'message' => 'Group tidak boleh kosong.']);
        }

        $rows = self::getGroupFromCache($group);

        return json(['success' => 1, 'fields' => (object) $rows]);
    }

    // ── POST /panel/setting/save ─────────────────────────────
    public function save(Request $request)
    {
        // Support both JSON body (axios default) and form POST
        $raw = json_decode($request->rawBody(), true);
        if (! is_array($raw) || empty($raw)) {
            $raw = $request->post() ?? [];
        }

        $group = trim($raw['group'] ?? '');
        unset($raw['group']);

        if ($group === '') {
            return json(['success' => 0, 'message' => 'Group tidak boleh kosong.']);
        }

        foreach ($raw as $name => $value) {
            $name = trim((string) $name);
            if ($name === '') continue;

            Db::table('mein_options')->updateOrInsert(
                ['option_group' => $group, 'option_name' => $name],
                ['option_value' => (string) $value]
            );
        }

        // Bust the cache for this group
        \support\Cache::delete('panel_setting:' . $group);

        return json(['success' => 1, 'message' => 'Pengaturan berhasil disimpan.']);
    }

    // ── Static cache helper ──────────────────────────────────────

    /**
     * Return all option_name => option_value pairs for a group,
     * reading from cache when available.
     */
    public static function getGroupFromCache(string $group): array
    {
        $cacheKey = 'panel_setting:' . $group;
        $cached   = \support\Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rows = Db::table('mein_options')
            ->where('option_group', $group)
            ->pluck('option_value', 'option_name')
            ->toArray();

        \support\Cache::set($cacheKey, $rows, 86400); // 24 jam

        return $rows;
    }

    // ── Private helpers ───────────────────────────────────────────

    /**
     * Load all YAML files, sort by menu_position, return array of:
     * [ 'name' => label, 'slug' => group key, 'html' => rendered FormBuilder HTML ]
     */
    private function buildGroups(): array
    {
        $settingsDir = base_path('config/plugin/panel/settings');
        $yamlFiles   = glob($settingsDir . '/*.yml') ?: [];

        $groups = [];
        foreach ($yamlFiles as $file) {
            $cfg = Yaml::parseFile($file);
            if (empty($cfg['slug'])) continue;

            $groups[] = $cfg;
        }

        // Sort by menu_position ascending
        usort($groups, fn($a, $b) => ($a['menu_position'] ?? 99) <=> ($b['menu_position'] ?? 99));

        $built = [];
        foreach ($groups as $cfg) {
            $slug = $cfg['slug'];

            // Build FormBuilder schema from YAML setting definitions
            $schema = [];
            foreach ($cfg['setting'] ?? [] as $fieldKey => $fieldDef) {
                $schema[] = $this->yamlFieldToSchema($fieldKey, $fieldDef);
            }

            $fb   = new FormBuilder();
            $html = $fb->schemaArray($schema)->render(); // no values: Alpine fetches them

            $built[] = [
                'name' => $cfg['name'] ?? ucfirst($slug),
                'slug' => $slug,
                'html' => $html,
            ];
        }

        return $built;
    }

    /**
     * Convert a single YAML field definition into a FormBuilder schemaArray entry.
     */
    private function yamlFieldToSchema(string $fieldKey, array $def): array
    {
        $type = $def['form'] ?? 'text';

        $entry = [
            'name'  => $fieldKey,
            'type'  => $type,
            'label' => $def['label'] ?? ucwords(str_replace('_', ' ', $fieldKey)),
        ];

        // Normalise description (YAML uses both 'desc' and 'description')
        $desc = $def['description'] ?? $def['desc'] ?? '';
        if ($desc !== '') {
            $entry['description'] = $desc;
        }

        // Default value
        if (isset($def['default'])) {
            $entry['default'] = $def['default'];
        }

        // Options (for switch / dropdown / select)
        if (isset($def['options']) && is_array($def['options'])) {
            $entry['options'] = $def['options'];
        }

        // Code editor mode / height
        if (isset($def['mode']))   $entry['mode']   = $def['mode'];
        if (isset($def['height'])) $entry['height'] = (int) $def['height'];

        return $entry;
    }
}
