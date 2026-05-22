<?php

namespace Yllumi\Sayagi\app\controller;

use support\Request;
use support\Db;
use Symfony\Component\Yaml\Yaml;

class RoleController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module' => 'user',
        'submodule' => 'role',
    ];

    // Load privileges from config/plugin/panel/privileges.yml grouped by feature: [feature => [privilege => description]]
    public static function loadPrivilegesFromYml(): array
    {
        $file = base_path('config/plugin/panel/privileges.yml');
        if (!file_exists($file)) return [];

        $yaml = Yaml::parseFile($file);

        // Use "feature.privilege" composite string as the checkbox key so each
        // privilege is globally unique even if action names repeat across features.
        $grouped = [];
        foreach ($yaml as $feature => $actions) {
            foreach ($actions as $actionMap) {
                foreach ($actionMap as $privilege => $description) {
                    $key = $feature . '.' . $privilege;
                    $grouped[$feature][$key] = $description;
                }
            }
        }
        return $grouped;
    }

    private function db()
    {
        return Db::table('mein_roles');
    }

    private function dbPriv()
    {
        return Db::table('mein_role_privileges');
    }

    // ── GET /panel/role/index ──────────────────────────────
    public function index(Request $request)
    {
        $this->data['page_title'] = 'Manajemen Role';
        return render('role/index', $this->data);
    }

    // ── GET /panel/role/data ───────────────────────────────
    public function data(Request $request)
    {
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 10));
        $search  = trim($request->input('search', ''));
        $offset  = ($page - 1) * $perPage;

        $query = $this->db()->select('id', 'role_name', 'role_slug', 'created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('role_name', 'like', "%{$search}%")
                  ->orWhere('role_slug', 'like', "%{$search}%");
            });
        }

        $total    = $this->db()->count();
        $filtered = (clone $query)->count();
        $rows     = (clone $query)->orderBy('id')->skip($offset)->take($perPage)->get();

        // Attach privilege count per role
        $ids = collect($rows)->pluck('id')->toArray();
        $privCounts = [];
        if ($ids) {
            $counts = $this->dbPriv()
                ->selectRaw('role_id, COUNT(*) as total')
                ->whereIn('role_id', $ids)
                ->groupBy('role_id')
                ->get();
            foreach ($counts as $c) {
                $privCounts[$c->role_id] = $c->total;
            }
        }

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'id'          => $r->id,
                'name'        => $r->role_name,
                'role_slug'   => $r->role_slug ?? '-',
                'priv_count'  => $privCounts[$r->id] ?? 0,
                'created_at'  => $r->created_at,
            ];
        }

        return json([
            'rows'     => $data,
            'total'    => $total,
            'filtered' => $filtered,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($filtered / $perPage),
        ]);
    }

    // ── GET /panel/role/create ─────────────────────────────
    public function create(Request $request)
    {
        $this->data['page_title']     = 'Tambah Role';
        $this->data['role']           = null;
        $this->data['role_privs']     = [];
        $this->data['all_privileges'] = self::loadPrivilegesFromYml();
        return render('role/form', $this->data);
    }

    // ── POST /panel/role/store ─────────────────────────────
    public function store(Request $request)
    {
        $name     = trim($request->input('name', ''));
        $roleSlug = trim($request->input('role_slug', ''));
        $privs    = $request->input('privileges', []);

        if (!$name) {
            return json(['success' => 0, 'message' => 'Nama role wajib diisi.']);
        }
        if ($this->db()->where('role_name', $name)->exists()) {
            return json(['success' => 0, 'message' => 'Nama role sudah digunakan.']);
        }

        // Auto-generate slug if not provided
        if (!$roleSlug) {
            $roleSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        }

        $id = $this->db()->insertGetId([
            'role_name'  => $name,
            'role_slug'  => $roleSlug,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->syncPrivileges($id, (array) $privs);

        return json(['success' => 1, 'message' => 'Role berhasil ditambahkan.', 'redirect' => site_url('/panel/role/index')]);
    }

    // ── GET /panel/role/edit?id=X ─────────────────────────
    public function edit(Request $request)
    {
        $id   = (int) $request->input('id', 0);
        $role = $this->db()->where('id', $id)->first();

        if (!$role) {
            return redirect(site_url('/panel/role/index'));
        }

        // Build composite "feature.privilege" keys so Alpine can uniquely match them
        $rows  = $this->dbPriv()->where('role_id', $id)->get(['feature', 'privilege']);
        // return ddb($rows);
        if($rows->isNotEmpty()) {
            $privs = array_map(fn($r) => $r->feature . '.' . $r->privilege, $rows->toArray());
        } else {
            $privs = [];
        }

        $this->data['page_title']     = 'Edit Role';
        $this->data['role']           = $role;
        $this->data['role_privs']     = $privs;
        $this->data['all_privileges'] = self::loadPrivilegesFromYml();
        return render('role/form', $this->data);
    }

    // ── POST /panel/role/update ────────────────────────────
    public function update(Request $request)
    {
        $id       = (int) $request->input('id', 0);
        $name     = trim($request->input('name', ''));
        $roleSlug = trim($request->input('role_slug', ''));
        $privs    = $request->input('privileges', []);

        if (!$id || !$name) {
            return json(['success' => 0, 'message' => 'Nama role wajib diisi.']);
        }
        if ($this->db()->where('role_name', $name)->where('id', '!=', $id)->exists()) {
            return json(['success' => 0, 'message' => 'Nama role sudah digunakan role lain.']);
        }

        if (!$roleSlug) {
            $roleSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        }

        $this->db()->where('id', $id)->update([
            'role_name'  => $name,
            'role_slug'  => $roleSlug,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->syncPrivileges($id, (array) $privs);

        // Delete cache
        \support\Cache::delete('role_privileges:' . $id);

        return json(['success' => 1, 'message' => 'Role berhasil diperbarui.', 'redirect' => site_url('/panel/role/index')]);
    }

    // ── POST /panel/role/delete ────────────────────────────
    public function delete(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if (!$id) {
            return json(['success' => 0, 'message' => 'ID tidak valid.']);
        }

        // Check if any user is assigned this role
        $inUse = Db::table('mein_users')->where('role_id', $id)->exists();
        if ($inUse) {
            return json(['success' => 0, 'message' => 'Role masih digunakan oleh user. Pindahkan user terlebih dahulu.']);
        }

        $this->dbPriv()->where('role_id', $id)->delete();
        $deleted = $this->db()->where('id', $id)->delete();

        if (!$deleted) {
            return json(['success' => 0, 'message' => 'Role tidak ditemukan.']);
        }

        \support\Cache::delete('role_privileges:' . $id);

        return json(['success' => 1, 'message' => 'Role berhasil dihapus.', 'redirect' => site_url('/panel/role/index')]);
    }

    // ── Helper: sync privileges ────────────────────────────────
    private function syncPrivileges(int $roleId, array $privs): void
    {
        $this->dbPriv()->where('role_id', $roleId)->delete();

        if (!$privs) return;

        // Each $priv is a composite "feature.privilege" string
        $insert = [];
        foreach ($privs as $composite) {
            $dotPos = strpos($composite, '.');
            if ($dotPos === false) continue;
            $feature  = substr($composite, 0, $dotPos);
            $privilege = substr($composite, $dotPos + 1);
            $insert[] = [
                'role_id'    => $roleId,
                'feature'    => $feature,
                'privilege'  => $privilege,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        if ($insert) {
            $this->dbPriv()->insert($insert);
        }
    }
}
