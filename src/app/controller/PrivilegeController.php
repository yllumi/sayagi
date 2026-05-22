<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\attributes\RequirePrivilege;
use support\Request;
use Symfony\Component\Yaml\Yaml;

class PrivilegeController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module'     => 'development',
        'submodule'  => 'privilege',
    ];

    // -------------------------------------------------------------------------
    // YAML helpers
    // -------------------------------------------------------------------------

    private function privilegesPath(): string
    {
        return base_path('config/plugin/panel/privileges.yml');
    }

    /**
     * Baca YAML → flat array [{feature, privilege, description}]
     */
    private function loadPrivileges(): array
    {
        $path = $this->privilegesPath();
        if (!file_exists($path)) return [];

        $raw  = Yaml::parseFile($path) ?? [];
        $flat = [];

        foreach ($raw as $feature => $items) {
            if (!is_array($items)) continue;
            foreach ($items as $entry) {
                if (!is_array($entry)) continue;
                foreach ($entry as $privilege => $description) {
                    $flat[] = [
                        'feature'     => (string) $feature,
                        'privilege'   => (string) $privilege,
                        'description' => (string) ($description ?? ''),
                    ];
                }
            }
        }

        return $flat;
    }

    /**
     * Simpan flat array kembali ke YAML (grupkan per feature, urut alfabet).
     */
    private function savePrivileges(array $flat): void
    {
        $grouped = [];
        foreach ($flat as $item) {
            $grouped[$item['feature']][] = [$item['privilege'] => $item['description']];
        }
        ksort($grouped);
        file_put_contents($this->privilegesPath(), Yaml::dump($grouped, 2, 2));
    }

    private function makeKey(string $feature, string $privilege): string
    {
        return $feature . '.' . $privilege;
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    #[RequirePrivilege('privilege.read')]
    public function index(Request $request)
    {
        $this->data['page_title'] = 'Manajemen Privilege';
        return render('privilege/index', $this->data);
    }

    #[RequirePrivilege('privilege.read')]
    public function data(Request $request)
    {
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 10));
        $search  = strtolower(trim($request->input('search', '')));
        $feature = trim($request->input('feature', ''));

        $all   = $this->loadPrivileges();
        $total = count($all);

        // Filter feature
        if ($feature) {
            $all = array_values(array_filter($all, fn($r) => $r['feature'] === $feature));
        }
        // Filter search
        if ($search) {
            $all = array_values(array_filter($all, function ($r) use ($search) {
                return str_contains(strtolower($r['feature'] . '.' . $r['privilege']), $search)
                    || str_contains(strtolower($r['description']), $search);
            }));
        }

        $filtered = count($all);

        // Sort & paginate
        usort($all, fn($a, $b) => ($a['feature'] . $a['privilege']) <=> ($b['feature'] . $b['privilege']));
        $rows = array_slice($all, ($page - 1) * $perPage, $perPage);

        // Tambahkan key unik
        foreach ($rows as &$row) {
            $row['key'] = $this->makeKey($row['feature'], $row['privilege']);
        }

        return json([
            'rows'     => array_values($rows),
            'total'    => $total,
            'filtered' => $filtered,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($filtered / $perPage),
            'privileges' => [
                'privilege_read'   => isAllow('privilege.read'),
                'privilege_write'  => isAllow('privilege.write'),
                'privilege_delete' => isAllow('privilege.delete'),
            ],
        ]);
    }

    #[RequirePrivilege('privilege.read')]
    public function features(Request $request)
    {
        $all      = $this->loadPrivileges();
        $features = array_values(array_unique(array_column($all, 'feature')));
        sort($features);
        return json(['features' => $features]);
    }

    #[RequirePrivilege('privilege.write')]
    public function create(Request $request)
    {
        $all = $this->loadPrivileges();
        $this->data['page_title'] = 'Tambah Privilege';
        $this->data['privilege']  = null;
        $this->data['features']   = array_values(array_unique(array_column($all, 'feature')));
        return render('privilege/form', $this->data);
    }

    #[RequirePrivilege('privilege.write')]
    public function store(Request $request)
    {
        $feature     = trim($request->input('feature', ''));
        $privilege   = trim($request->input('privilege', ''));
        $description = trim($request->input('description', ''));

        if (!$feature)   return json(['success' => 0, 'message' => 'Feature wajib diisi.']);
        if (!$privilege) return json(['success' => 0, 'message' => 'Privilege wajib diisi.']);

        $all = $this->loadPrivileges();

        foreach ($all as $row) {
            if ($row['feature'] === $feature && $row['privilege'] === $privilege) {
                return json(['success' => 0, 'message' => 'Privilege sudah terdaftar.']);
            }
        }

        $all[] = ['feature' => $feature, 'privilege' => $privilege, 'description' => $description];
        $this->savePrivileges($all);

        return json(['success' => 1, 'message' => 'Privilege berhasil ditambahkan.', 'redirect' => site_url('/panel/privilege')]);
    }

    #[RequirePrivilege('privilege.write')]
    public function edit(Request $request)
    {
        $key = trim($request->input('key', ''));
        $all = $this->loadPrivileges();

        $item = null;
        foreach ($all as $row) {
            if ($this->makeKey($row['feature'], $row['privilege']) === $key) {
                $item = $row;
                break;
            }
        }

        if (!$item) return redirect(site_url('/panel/privilege'));

        $this->data['page_title'] = 'Edit Privilege';
        $this->data['privilege']  = (object) array_merge($item, ['key' => $key]);
        $this->data['features']   = array_values(array_unique(array_column($all, 'feature')));
        return render('privilege/form', $this->data);
    }

    #[RequirePrivilege('privilege.write')]
    public function update(Request $request)
    {
        $oldKey      = trim($request->input('key', ''));
        $feature     = trim($request->input('feature', ''));
        $privilege   = trim($request->input('privilege', ''));
        $description = trim($request->input('description', ''));

        if (!$oldKey)    return json(['success' => 0, 'message' => 'Key tidak valid.']);
        if (!$feature)   return json(['success' => 0, 'message' => 'Feature wajib diisi.']);
        if (!$privilege) return json(['success' => 0, 'message' => 'Privilege wajib diisi.']);

        $all    = $this->loadPrivileges();
        $newKey = $this->makeKey($feature, $privilege);
        $found  = false;

        foreach ($all as &$row) {
            if ($this->makeKey($row['feature'], $row['privilege']) === $oldKey) {
                // Cek duplikat dengan entri lain
                if ($newKey !== $oldKey) {
                    foreach ($all as $other) {
                        if ($this->makeKey($other['feature'], $other['privilege']) === $newKey) {
                            return json(['success' => 0, 'message' => 'Privilege sudah digunakan oleh entri lain.']);
                        }
                    }
                }
                $row['feature']     = $feature;
                $row['privilege']   = $privilege;
                $row['description'] = $description;
                $found = true;
                break;
            }
        }

        if (!$found) return json(['success' => 0, 'message' => 'Privilege tidak ditemukan.']);

        $this->savePrivileges($all);
        return json(['success' => 1, 'message' => 'Privilege berhasil diperbarui.', 'redirect' => site_url('/panel/privilege')]);
    }

    #[RequirePrivilege('privilege.delete')]
    public function delete(Request $request)
    {
        $key = trim($request->input('key', ''));
        if (!$key) return json(['success' => 0, 'message' => 'Key tidak valid.']);

        $all   = $this->loadPrivileges();
        $found = false;
        $new   = [];

        foreach ($all as $row) {
            if ($this->makeKey($row['feature'], $row['privilege']) === $key) {
                $found = true;
                continue;
            }
            $new[] = $row;
        }

        if (!$found) return json(['success' => 0, 'message' => 'Privilege tidak ditemukan.']);

        $this->savePrivileges($new);
        return json(['success' => 1, 'message' => 'Privilege berhasil dihapus.', 'redirect' => site_url('/panel/privilege')]);
    }
}
