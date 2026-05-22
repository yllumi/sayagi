<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\attributes\RequirePrivilege;
use support\Request;
use support\Db;

class UserController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module' => 'user',
        'submodule' => 'user',
    ];

    #[RequirePrivilege('user.read')]
    public function index(Request $request)
    {
        $this->data['page_title'] = 'Manajemen User';
        return render('user/index', $this->data);
    }

    #[RequirePrivilege('user.read')]
    public function data(Request $request)
    {
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 10));
        $search  = trim($request->input('search', ''));
        $status  = $request->input('status', 'active');
        $offset  = ($page - 1) * $perPage;

        $query = Db::table('mein_users as u')
            ->leftJoin('mein_roles as r', 'u.role_id', '=', 'r.id')
            ->select('u.id', 'u.name', 'u.username', 'u.email', 'u.phone', 'u.status', 'u.role_id', 'r.role_name', 'u.created_at');

        if ($status && $status !== 'all') {
            $query->where('u.status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                  ->orWhere('u.username', 'like', "%{$search}%")
                  ->orWhere('u.email', 'like', "%{$search}%")
                  ->orWhere('u.phone', 'like', "%{$search}%");
            });
        }

        $total    = Db::table('mein_users')->count();
        $filtered = (clone $query)->count();
        $rows     = (clone $query)->orderByDesc('u.created_at')->skip($offset)->take($perPage)->get();

        $data = [];
        foreach ($rows as $u) {
            $data[] = [
                'id'         => $u->id,
                'name'       => $u->name,
                'username'   => $u->username ?? '',
                'email'      => $u->email ?? '',
                'phone'      => $u->phone ?? '-',
                'status'     => $u->status,
                'role_name'  => $u->role_name ?? '-',
                'created_at' => $u->created_at,
            ];
        }

        return json([
            'rows'     => $data,
            'total'    => $total,
            'filtered' => $filtered,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($filtered / $perPage),
            'privileges' => [
                'user_write' => isAllow('user.write'),
                'user_delete' => isAllow('user.delete'),
            ]
        ]);
    }

    #[RequirePrivilege('user.write')]
    public function create(Request $request)
    {
        $this->data['page_title'] = 'Tambah User';
        $this->data['user']       = null;
        $this->data['roles']      = Db::table('mein_roles')->orderBy('role_name')->get();
        return render('user/form', $this->data);
    }

    #[RequirePrivilege('user.write')]
    public function store(Request $request)
    {
        $name     = trim($request->input('name', ''));
        $username = strtolower(trim($request->input('username', '')));
        $email    = strtolower(trim($request->input('email', '')));
        $phone    = trim($request->input('phone', ''));
        $status   = $request->input('status', 'active');
        $role_id  = (int) $request->input('role_id', 0) ?: null;
        $password = $request->input('password', '');

        if (!$name || !$username || !$email || !$password) {
            return json(['success' => 0, 'message' => 'Nama, username, email, dan password wajib diisi.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => 0, 'message' => 'Format email tidak valid.']);
        }
        if (strlen($password) < 8) {
            return json(['success' => 0, 'message' => 'Password minimal 8 karakter.']);
        }
        if (Db::table('mein_users')->where('email', $email)->exists()) {
            return json(['success' => 0, 'message' => 'Email sudah digunakan.']);
        }
        if (Db::table('mein_users')->where('username', $username)->exists()) {
            return json(['success' => 0, 'message' => 'Username sudah digunakan.']);
        }

        $Phpass = new \Yllumi\Sayagi\libraries\Phpass();

        $userData = [
            'name'       => $name,
            'username'   => $username,
            'email'      => $email,
            'phone'      => $phone,
            'password'   => $Phpass->HashPassword($password),
            'status'     => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if(isAllow('user.set_role') && $role_id !== null) {
            $userData['role_id'] = $role_id;
        }
        Db::table('mein_users')->insert($userData);

        return json(['success' => 1, 'message' => 'User berhasil ditambahkan.', 'redirect' => site_url('/panel/user')]);
    }

    #[RequirePrivilege('user.write')]
    public function edit(Request $request)
    {
        $id   = (int) $request->input('id', 0);
        $user = Db::table('mein_users')->where('id', $id)->first();

        if (!$user) {
            return redirect(site_url('/panel/user'));
        }

        $this->data['page_title'] = 'Edit User';
        $this->data['user']       = $user;
        $this->data['roles']      = Db::table('mein_roles')->orderBy('role_name')->get();
        return render('user/form', $this->data);
    }

    #[RequirePrivilege('user.write')]
    public function update(Request $request)
    {
        $id       = (int) $request->input('id', 0);
        $name     = trim($request->input('name', ''));
        $username = strtolower(trim($request->input('username', '')));
        $email    = strtolower(trim($request->input('email', '')));
        $phone    = trim($request->input('phone', ''));
        $status   = $request->input('status', 'active');
        $role_id  = (int) $request->input('role_id', 0) ?: null;
        $password = $request->input('password', '');

        if (!$id || !$name || !$username || !$email) {
            return json(['success' => 0, 'message' => 'Nama, username, dan email wajib diisi.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => 0, 'message' => 'Format email tidak valid.']);
        }
        if (Db::table('mein_users')->where('email', $email)->where('id', '!=', $id)->exists()) {
            return json(['success' => 0, 'message' => 'Email sudah digunakan user lain.']);
        }
        if (Db::table('mein_users')->where('username', $username)->where('id', '!=', $id)->exists()) {
            return json(['success' => 0, 'message' => 'Username sudah digunakan user lain.']);
        }

        $payload = [
            'name'       => $name,
            'username'   => $username,
            'email'      => $email,
            'phone'      => $phone,
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if(isAllow('user.set_role') && $role_id !== null) {
            $payload['role_id'] = $role_id;
        }

        if ($password !== '') {
            if (strlen($password) < 8) {
                return json(['success' => 0, 'message' => 'Password minimal 8 karakter.']);
            }
            $Phpass = new \Yllumi\Sayagi\libraries\Phpass();
            $payload['password'] = $Phpass->HashPassword($password);
        }

        Db::table('mein_users')->where('id', $id)->update($payload);

        return json(['success' => 1, 'message' => 'User berhasil diperbarui.', 'redirect' => site_url('/panel/user/index')]);
    }

    #[RequirePrivilege('user.delete')]
    public function delete(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if (!$id) {
            return json(['success' => 0, 'message' => 'ID tidak valid.']);
        }

        $deleted = Db::table('mein_users')->where('id', $id)->update(['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')]);
        if (!$deleted) {
            return json(['success' => 0, 'message' => 'User tidak ditemukan.']);
        }

        return json(['success' => 1, 'message' => 'User berhasil dihapus.', 'redirect' => site_url('/panel/user/index')]);
    }
}
