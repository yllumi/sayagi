<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\app\controller\Concerns\HandlesUserAvatar;
use Yllumi\Sayagi\libraries\AvatarUploader;
use Yllumi\Sayagi\libraries\Phpass;
use support\Db;
use support\Request;

/**
 * Self-service profile for the logged-in user (no privilege required).
 *
 * Identity fields, avatar and password are editable here; role and status
 * stay under `user.write` in the User module on purpose.
 */
class ProfileController extends AdminController
{
    use HandlesUserAvatar;

    protected $data = [
        'page_title' => '',
        'module'     => 'user',
        'submodule'  => 'user',
    ];

    public function index(Request $request)
    {
        $user = $this->currentUser($request);
        if ($user === null) {
            return redirect(site_url('/panel/auth/login'));
        }

        $this->data['page_title'] = 'Profil Saya';
        $this->data['user']       = $user;
        $this->data['avatar_url'] = AvatarUploader::url($user->avatar);
        $this->data['endpoints']  = [
            'update'        => site_url('/panel/profile/update'),
            'password'      => site_url('/panel/profile/password'),
            'avatar'        => site_url('/panel/profile/avatar'),
            'avatar_delete' => site_url('/panel/profile/avatar/delete'),
        ];

        return render('profile/index', $this->data);
    }

    public function update(Request $request)
    {
        $user = $this->currentUser($request);
        if ($user === null) {
            return json(['success' => 0, 'message' => 'Sesi berakhir. Silakan masuk kembali.']);
        }

        $name        = trim((string) $request->input('name', ''));
        $username    = strtolower(trim((string) $request->input('username', '')));
        $email       = strtolower(trim((string) $request->input('email', '')));
        $phone       = trim((string) $request->input('phone', ''));
        $description = trim((string) $request->input('short_description', ''));

        if ($name === '' || $username === '' || $email === '') {
            return json(['success' => 0, 'message' => 'Nama, username, dan email wajib diisi.']);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => 0, 'message' => 'Format email tidak valid.']);
        }

        if (Db::table('mein_users')->where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return json(['success' => 0, 'message' => 'Email sudah digunakan user lain.']);
        }

        if (Db::table('mein_users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
            return json(['success' => 0, 'message' => 'Username sudah digunakan user lain.']);
        }

        Db::table('mein_users')->where('id', $user->id)->update([
            'name'              => $name,
            'username'          => $username,
            'email'             => $email,
            'phone'             => $phone !== '' ? $phone : null,
            'short_description' => $description !== '' ? mb_substr($description, 0, 255) : null,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $this->refreshUserSession($request, [
            'name'     => $name,
            'username' => $username,
            'email'    => $email,
        ]);

        return json(['success' => 1, 'message' => 'Profil berhasil diperbarui.']);
    }

    public function password(Request $request)
    {
        $user = $this->currentUser($request);
        if ($user === null) {
            return json(['success' => 0, 'message' => 'Sesi berakhir. Silakan masuk kembali.']);
        }

        $current  = (string) $request->input('current_password', '');
        $password = (string) $request->input('password', '');
        $confirm  = (string) $request->input('password_confirmation', '');

        if ($current === '' || $password === '') {
            return json(['success' => 0, 'message' => 'Password lama dan password baru wajib diisi.']);
        }

        $phpass = new Phpass();
        if (! $phpass->CheckPassword($current, (string) $user->password)) {
            return json(['success' => 0, 'message' => 'Password lama tidak cocok.']);
        }

        if (strlen($password) < 8) {
            return json(['success' => 0, 'message' => 'Password baru minimal 8 karakter.']);
        }

        if ($password !== $confirm) {
            return json(['success' => 0, 'message' => 'Konfirmasi password baru tidak sama.']);
        }

        if ($phpass->CheckPassword($password, (string) $user->password)) {
            return json(['success' => 0, 'message' => 'Password baru harus berbeda dari password lama.']);
        }

        Db::table('mein_users')->where('id', $user->id)->update([
            'password'   => $phpass->HashPassword($password),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return json(['success' => 1, 'message' => 'Password berhasil diubah.']);
    }

    public function uploadAvatar(Request $request)
    {
        $user = $this->currentUser($request);
        if ($user === null) {
            return json(['success' => 0, 'message' => 'Sesi berakhir. Silakan masuk kembali.']);
        }

        return json($this->storeUserAvatar($request, $user));
    }

    public function removeAvatar(Request $request)
    {
        $user = $this->currentUser($request);
        if ($user === null) {
            return json(['success' => 0, 'message' => 'Sesi berakhir. Silakan masuk kembali.']);
        }

        return json($this->removeUserAvatar($request, $user));
    }

    private function currentUser(Request $request): ?object
    {
        $id = $this->sessionUserId($request);
        if ($id <= 0) {
            return null;
        }

        return Db::table('mein_users as u')
            ->leftJoin('mein_roles as r', 'u.role_id', '=', 'r.id')
            ->select('u.*', 'r.role_name')
            ->where('u.id', $id)
            ->first();
    }
}
