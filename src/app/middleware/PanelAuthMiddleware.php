<?php
namespace Yllumi\Sayagi\app\middleware;

use ReflectionClass;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Yllumi\Sayagi\attributes\RequirePrivilege;

class PanelAuthMiddleware implements MiddlewareInterface
{
    /**
     * Session key holding the panel page a guest tried to open before login.
     * Consumed by the app's PanelAuthController after a successful login.
     */
    public const SESSION_INTENDED_KEY = 'panel_intended_url';

    public function process(Request $request, callable $handler) : Response
    {
        $path = $request->path();

        $controller = new ReflectionClass($request->controller);
        $noNeedLogin = $controller->getDefaultProperties()['noNeedLogin'] ?? [];

        // ── 1. Auth check ─────────────────────────────────────────
        if (!session('user')) {
            if (!in_array($request->action, $noNeedLogin)) {
                $this->rememberIntendedUrl($request);
                return redirect('/panel/auth/login');
            }
            return $handler($request);
        }

        // ── 2. Privilege check via #[RequirePrivilege] attribute ──
        if ($controller->hasMethod($request->action)) {
            $method = $controller->getMethod($request->action);
            $attrs  = $method->getAttributes(RequirePrivilege::class);

            foreach ($attrs as $attr) {
                /** @var RequirePrivilege $rp */
                $rp = $attr->newInstance();

                if (!isAllow($rp->privilege, $rp->whitelistIds)) {
                    return view('errors/404');
                }
            }
        }

        return $handler($request);
    }

    /**
     * Simpan halaman yang gagal dibuka karena belum login, supaya setelah login
     * pengguna dikembalikan ke halaman itu (bukan selalu ke dashboard).
     *
     * Hanya GET: target POST tidak bisa diputar ulang lewat redirect. Path
     * dashboard root juga dilewati agar landing khusus per role tetap berlaku.
     */
    protected function rememberIntendedUrl(Request $request): void
    {
        if ($request->method() !== 'GET') {
            return;
        }

        // Navigasi halaman mengirim Accept yang memuat text/html. Request fetch/XHR
        // (Accept: */*) dilewati agar setelah login tidak mendarat di endpoint JSON.
        $accept = (string) $request->header('accept', '');

        if ($accept !== '' && !str_contains($accept, 'text/html')) {
            return;
        }

        $path = '/' . ltrim($request->path(), '/');

        // Path bisa sudah memuat APP_BASE_PATH (tergantung proxy). Deteksi root
        // panel dilakukan pada path tanpa prefix; PanelAuthController yang
        // menambahkan prefix bila belum ada saat redirect.
        $base = '/' . trim((string) env('APP_BASE_PATH', ''), '/');
        $normalized = ($base !== '/' && str_starts_with($path, $base . '/'))
            ? substr($path, strlen($base))
            : $path;

        if (in_array($normalized, ['/', '/panel', '/panel/'], true)) {
            return;
        }

        if (str_starts_with($normalized, '/panel/auth')) {
            return;
        }

        $query = (string) $request->queryString();

        $request->session()->set(
            self::SESSION_INTENDED_KEY,
            $query === '' ? $path : $path . '?' . $query
        );
    }
}
