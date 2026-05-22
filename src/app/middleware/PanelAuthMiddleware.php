<?php
namespace Yllumi\Sayagi\app\middleware;

use ReflectionClass;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Yllumi\Sayagi\attributes\RequirePrivilege;

class PanelAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler) : Response
    {
        $path = $request->path();

        $controller = new ReflectionClass($request->controller);
        $noNeedLogin = $controller->getDefaultProperties()['noNeedLogin'] ?? [];

        // ── 1. Auth check ─────────────────────────────────────────
        if (!session('user')) {
            if (!in_array($request->action, $noNeedLogin)) {
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
}
