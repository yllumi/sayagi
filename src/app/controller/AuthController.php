<?php

namespace Yllumi\Sayagi\app\controller;

use support\Request;
use support\Db;

class AuthController extends AdminController
{
    protected $noNeedLogin = ['login', 'doLogin', 'register', 'doRegister', 'forgot', 'doForgot', 'reset', 'doReset'];

    // ── GET /panel/auth/login ──────────────────────────────
    public function login(Request $request)
    {
        if (session('user')) {
            return redirect(site_url('panel'));
        }
        $data['page_title'] = 'Login';
        return render('auth/login', $data, null, 'auth');
    }

    // ── POST /panel/auth/login ─────────────────────────────
    public function doLogin(Request $request)
    {
        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');

        if (!$username || !$password) {
            return json(['success' => 0, 'message' => 'Username dan password wajib diisi.']);
        }

        $user = Db::table('mein_users')
            ->where(function ($q) use ($username) {
                $q->where('email', $username)->orWhere('username', $username);
            })
            ->where('status', 'active')
            ->first();

        if (!$user) {
            return json(['success' => 0, 'message' => 'Akun tidak ditemukan atau belum aktif.']);
        }

        $Phpass = new \Yllumi\Sayagi\libraries\Phpass();
        if (!$Phpass->CheckPassword($password, $user->password)) {
            return json(['success' => 0, 'message' => 'Username atau password salah.']);
        }

        // Store session
        $request->session()->set('user', [
            'user_id'  => $user->id,
            'name'     => $user->name,
            'username' => $user->username,
            'email'    => $user->email,
            'role_id'  => $user->role_id,
        ]);

        return json(['success' => 1, 'redirect' => site_url('panel')]);
    }

    // ── GET /panel/auth/logout ─────────────────────────────
    public function logout(Request $request)
    {
        $request->session()->delete('user');
        return redirect(site_url('panel/auth/login'));
    }

    // ── GET /panel/auth/register ──────────────────────────
    public function register(Request $request)
    {
        // Show 404 page if registration is disabled
        if (setting('user.enable_registration') !== 'on') {
            return view('404')->withStatus(404);
        }

        if (session('user')) {
            return redirect(site_url('panel'));
        }
        $data['page_title'] = 'Daftar Akun';
        return render('auth/register', $data, null, 'auth');
    }

    // ── POST /panel/auth/register ─────────────────────────
    public function doRegister(Request $request)
    {
        $name     = trim($request->input('name', ''));
        $email    = strtolower(trim($request->input('email', '')));
        $username = strtolower(trim($request->input('username', '')));
        $password = $request->input('password', '');
        $confirm  = $request->input('password_confirmation', '');

        // Validate
        if (!$name || !$email || !$username || !$password) {
            return json(['success' => 0, 'message' => 'Semua field wajib diisi.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => 0, 'message' => 'Format email tidak valid.']);
        }
        if (strlen($password) < 8) {
            return json(['success' => 0, 'message' => 'Password minimal 8 karakter.']);
        }
        if ($password !== $confirm) {
            return json(['success' => 0, 'message' => 'Konfirmasi password tidak cocok.']);
        }

        // Check uniqueness
        if (Db::table('mein_users')->where('email', $email)->exists()) {
            return json(['success' => 0, 'message' => 'Email sudah terdaftar.']);
        }
        if (Db::table('mein_users')->where('username', $username)->exists()) {
            return json(['success' => 0, 'message' => 'Username sudah digunakan.']);
        }

        $Phpass = new \Yllumi\Sayagi\libraries\Phpass();
        $hashed = $Phpass->HashPassword($password);

        $defaultRole = setting('user.user_registration_role', 'member');
        // Resolve role name to role_id
        $role = Db::table('mein_roles')->where('name', $defaultRole)->first();
        $roleId = $role ? $role->id : 0;

        $defaultStatus = setting('user.user_registration_status', 'active');

        Db::table('mein_users')->insert([
            'name'       => $name,
            'email'      => $email,
            'username'   => $username,
            'password'   => $hashed,
            'role_id'    => $roleId,
            'status'     => $defaultStatus,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return json(['success' => 1, 'message' => 'Akun berhasil dibuat. Silakan login.', 'redirect' => site_url(setting('user.login_url'))]);
    }

    // ── GET /panel/auth/forgot ─────────────────────────────
    public function forgot(Request $request)
    {
        if (session('user')) return redirect(site_url('panel'));
        $data['page_title']          = 'Lupa Password';
        $data['recaptcha_version']  = \Yllumi\Sayagi\libraries\Recaptcha::version();
        $data['recaptcha_site_key'] = \Yllumi\Sayagi\libraries\Recaptcha::siteKey();
        return render('auth/forgot', $data, null, 'auth');
    }

    // ── POST /panel/auth/forgot ────────────────────────────
    public function doForgot(Request $request)
    {
        $email          = strtolower(trim($request->input('email', '')));
        $recaptchaToken = $request->input('recaptcha_token', '');

        if (!$email) {
            return json(['success' => 0, 'message' => 'Email wajib diisi.']);
        }

        // Verify reCAPTCHA v3
        if (!\Yllumi\Sayagi\libraries\Recaptcha::verify($recaptchaToken)) {
            return json(['success' => 0, 'message' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.']);
        }

        $user = Db::table('mein_users')
            ->where('email', $email)
            ->first();

        if (!$user) {
            // Respond generically to prevent email enumeration
            return json(['success' => 1, 'message' => 'Jika email terdaftar, kode OTP telah dikirim.']);
        }

        $otp   = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = hash('sha256', $otp . $user->email . time());

        Db::table('mein_users')
            ->where('id', $user->id)
            ->update([
                'otp'        => $otp,
                'token'      => $token,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $appName = getenv('mail.from_name') ?: 'Panel';
        try {
            $sender = new \Yllumi\Sayagi\libraries\EmailSender();
            $sender->sendEmail(
                $user->email,
                "Kode OTP Reset Password — {$appName}",
                \Yllumi\Sayagi\libraries\EmailSender::otpTemplate($user->name ?? $user->username, $otp, $appName)
            );
        } catch (\Throwable $e) {
            return json(['success' => 0, 'message' => 'Gagal mengirim email. Silakan coba lagi.']);
        }

        return json(['success' => 1, 'token' => $token]);
    }

    // ── GET /panel/auth/reset ──────────────────────────────
    public function reset(Request $request)
    {
        if (session('user')) return redirect(site_url('panel'));
        $token = $request->input('token', '');
        if (!$token) return redirect(site_url('panel/auth/forgot'));

        $data['page_title'] = 'Reset Password';
        $data['token']      = htmlspecialchars($token);
        return render('auth/reset', $data, null, 'auth');
    }

    // ── POST /panel/auth/reset ─────────────────────────────
    public function doReset(Request $request)
    {
        $token    = $request->input('token', '');
        $otp      = trim($request->input('otp', ''));
        $password = $request->input('password', '');
        $confirm  = $request->input('password_confirmation', '');

        if (!$token || !$otp || !$password) {
            return json(['success' => 0, 'message' => 'Semua field wajib diisi.']);
        }
        if (strlen($password) < 8) {
            return json(['success' => 0, 'message' => 'Password minimal 8 karakter.']);
        }
        if ($password !== $confirm) {
            return json(['success' => 0, 'message' => 'Konfirmasi password tidak cocok.']);
        }

        $user = Db::table('mein_users')
            ->where('token', $token)
            ->where('otp', $otp)
            ->first();

        if (!$user) {
            return json(['success' => 0, 'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }

        $Phpass = new \Yllumi\Sayagi\libraries\Phpass();
        Db::table('mein_users')
            ->where('id', $user->id)
            ->update([
                'password'   => $Phpass->HashPassword($password),
                'otp'        => null,
                'token'      => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return json(['success' => 1, 'message' => 'Password berhasil direset. Silakan login.', 'redirect' => site_url('panel/auth/login')]);
    }
}
