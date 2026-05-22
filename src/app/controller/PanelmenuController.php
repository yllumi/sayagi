<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\attributes\RequirePrivilege;
use support\Request;
use Symfony\Component\Yaml\Yaml;

class PanelmenuController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module'     => 'development',
        'submodule'  => 'panelmenu',
    ];

    /**
     * Path ke file YAML menu
     */
    private function menuPath(): string
    {
        return base_path('config/plugin/panel/menu.yml');
    }

    /**
     * Baca tree menu dari menu.yml.
     * Tambahkan id unik ke setiap item yang belum memilikinya, lalu simpan.
     */
    private function loadMenus(): array
    {
        $path = $this->menuPath();

        if (!file_exists($path)) {
            return [];
        }

        $tree = Yaml::parseFile($path) ?? [];

        $changed = false;
        $tree    = $this->ensureIds($tree, $changed);

        if ($changed) {
            $this->saveMenus($tree);
        }

        return $tree;
    }

    /**
     * Pastikan setiap item (root & children) punya field 'id'.
     */
    private function ensureIds(array $tree, bool &$changed): array
    {
        foreach ($tree as &$item) {
            if (empty($item['id'])) {
                $item['id'] = uniqid('menu_');
                $changed    = true;
            }
            if (!empty($item['children'])) {
                $item['children'] = $this->ensureIds($item['children'], $changed);
            }
        }
        return $tree;
    }

    /**
     * Tulis tree ke file menu.yml.
     */
    private function saveMenus(array $tree): void
    {
        file_put_contents($this->menuPath(), Yaml::dump($tree, 4, 2));
    }

    /**
     * Rekursif: cari item berdasarkan id; kembalikan item atau null.
     */
    private function findById(array $tree, string $id): ?array
    {
        foreach ($tree as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
            if (!empty($item['children'])) {
                $found = $this->findById($item['children'], $id);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Rekursif: hapus item berdasarkan id.
     * Mengembalikan true jika item ditemukan dan dihapus.
     */
    private function findAndRemove(array &$tree, string $id): bool
    {
        foreach ($tree as $k => $item) {
            if (($item['id'] ?? '') === $id) {
                array_splice($tree, $k, 1);
                return true;
            }
            if (!empty($item['children'])) {
                if ($this->findAndRemove($tree[$k]['children'], $id)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Rekursif: update field item berdasarkan id.
     */
    private function findAndUpdate(array &$tree, string $id, array $fields): bool
    {
        foreach ($tree as &$item) {
            if (($item['id'] ?? '') === $id) {
                foreach ($fields as $key => $value) {
                    $item[$key] = $value;
                }
                return true;
            }
            if (!empty($item['children'])) {
                if ($this->findAndUpdate($item['children'], $id, $fields)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Rekursif: urutkan ulang children dari parent_id tertentu.
     */
    private function reorderChildren(array &$tree, string $parentId, array $ids): bool
    {
        foreach ($tree as &$item) {
            if (($item['id'] ?? '') === $parentId) {
                $indexed = [];
                foreach ($item['children'] as $child) {
                    $indexed[$child['id']] = $child;
                }
                $newChildren = [];
                foreach ($ids as $id) {
                    if (isset($indexed[$id])) {
                        $newChildren[] = $indexed[$id];
                    }
                }
                $item['children'] = $newChildren;
                return true;
            }
            if (!empty($item['children'])) {
                if ($this->reorderChildren($item['children'], $parentId, $ids)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Rekursif: tambahkan item sebagai child dari parent_id tertentu.
     */
    private function appendChild(array &$tree, string $parentId, array $newItem): bool
    {
        foreach ($tree as &$item) {
            if (($item['id'] ?? '') === $parentId) {
                if (!isset($item['children'])) {
                    $item['children'] = [];
                }
                $item['children'][] = $newItem;
                return true;
            }
            if (!empty($item['children'])) {
                if ($this->appendChild($item['children'], $parentId, $newItem)) {
                    return true;
                }
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    #[RequirePrivilege('development.read')]
    public function index(Request $request)
    {
        $this->data['page_title']  = 'Panel Menu';
        $this->data['menus']       = $this->loadMenus();

        // Baca privileges dari YAML
        $privPath = base_path('config/plugin/panel/privileges.yml');
        $privRaw  = file_exists($privPath) ? (Yaml::parseFile($privPath) ?? []) : [];
        $privFlat = [];
        foreach ($privRaw as $feature => $items) {
            foreach ((array) $items as $entry) {
                foreach ((array) $entry as $priv => $desc) {
                    $privFlat[] = (object) ['feature' => $feature, 'privilege' => $priv, 'description' => $desc];
                }
            }
        }
        usort($privFlat, fn($a, $b) => ($a->feature . $a->privilege) <=> ($b->feature . $b->privilege));
        $this->data['privileges'] = $privFlat;

        return render('panelmenu/index', $this->data);
    }

    #[RequirePrivilege('development.write')]
    public function store(Request $request)
    {
        $label      = trim($request->input('label', ''));
        $module     = trim($request->input('module', ''));
        $submodule  = trim($request->input('submodule', '')) ?: null;
        $icon       = trim($request->input('icon', ''));
        $url        = trim($request->input('url', '#'));
        $target     = trim($request->input('target', '')) ?: null;
        $privilege  = trim($request->input('privilege', '')) ?: null;
        $parent_id  = trim($request->input('parent_id', ''));

        if (!$label) {
            return json(['success' => 0, 'message' => 'Label wajib diisi.']);
        }

        $newItem = [
            'id'        => uniqid('menu_'),
            'label'     => $label,
            'module'    => $module,
            'submodule' => $submodule,
            'icon'      => $icon,
            'url'       => $url,
            'target'    => $target,
            'privilege' => $privilege,
            'children'  => [],
        ];

        $tree = $this->loadMenus();

        if ($parent_id) {
            $appended = $this->appendChild($tree, $parent_id, $newItem);
            if (!$appended) {
                return json(['success' => 0, 'message' => 'Parent menu tidak ditemukan.']);
            }
        } else {
            $tree[] = $newItem;
        }

        $this->saveMenus($tree);
        return json(['success' => 1, 'message' => 'Menu berhasil ditambahkan.']);
    }

    #[RequirePrivilege('development.read')]
    public function edit(Request $request)
    {
        $id   = trim($request->input('id', ''));
        $tree = $this->loadMenus();
        $item = $this->findById($tree, $id);

        if (!$item) {
            return json(['success' => 0, 'message' => 'Menu tidak ditemukan.']);
        }

        return json(['success' => 1, 'item' => $item]);
    }

    #[RequirePrivilege('development.write')]
    public function update(Request $request)
    {
        $id        = trim($request->input('id', ''));
        $label     = trim($request->input('label', ''));
        $module    = trim($request->input('module', ''));
        $submodule = trim($request->input('submodule', '')) ?: null;
        $icon      = trim($request->input('icon', ''));
        $url       = trim($request->input('url', '#'));
        $target    = trim($request->input('target', '')) ?: null;
        $privilege = trim($request->input('privilege', '')) ?: null;

        if (!$id || !$label) {
            return json(['success' => 0, 'message' => 'ID dan label wajib diisi.']);
        }

        $tree   = $this->loadMenus();
        $fields = compact('label', 'module', 'submodule', 'icon', 'url', 'target', 'privilege');
        $found  = $this->findAndUpdate($tree, $id, $fields);

        if (!$found) {
            return json(['success' => 0, 'message' => 'Menu tidak ditemukan.']);
        }

        $this->saveMenus($tree);
        return json(['success' => 1, 'message' => 'Menu berhasil diperbarui.']);
    }

    #[RequirePrivilege('development.write')]
    public function reorder(Request $request)
    {
        $raw      = json_decode($request->rawBody(), true) ?? [];
        $ids      = $raw['ids'] ?? [];
        $parentId = trim($raw['parent_id'] ?? '');

        if (!is_array($ids) || empty($ids)) {
            return json(['success' => 0, 'message' => 'Data urutan tidak valid.']);
        }

        $tree = $this->loadMenus();

        if (!$parentId) {
            // Urutkan root items
            $indexed = [];
            foreach ($tree as $item) {
                $indexed[$item['id']] = $item;
            }
            $newTree = [];
            foreach ($ids as $id) {
                if (isset($indexed[$id])) {
                    $newTree[] = $indexed[$id];
                }
            }
            $tree = $newTree;
        } else {
            // Urutkan children dari parent tertentu
            $found = $this->reorderChildren($tree, $parentId, $ids);
            if (!$found) {
                return json(['success' => 0, 'message' => 'Parent menu tidak ditemukan.']);
            }
        }

        $this->saveMenus($tree);
        return json(['success' => 1, 'message' => 'Urutan berhasil disimpan.']);
    }

    #[RequirePrivilege('development.delete')]
    public function delete(Request $request)
    {
        $id = trim($request->input('id', ''));

        if (!$id) {
            return json(['success' => 0, 'message' => 'ID tidak valid.']);
        }

        $tree  = $this->loadMenus();
        $found = $this->findAndRemove($tree, $id);

        if (!$found) {
            return json(['success' => 0, 'message' => 'Menu tidak ditemukan.']);
        }

        $this->saveMenus($tree);
        return json(['success' => 1, 'message' => 'Menu berhasil dihapus.']);
    }
}
