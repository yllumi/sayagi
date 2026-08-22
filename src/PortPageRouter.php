<?php

declare(strict_types=1);

namespace Yllumi\Sayagi;

use support\Request;
use Webman\Route;
use Yllumi\Sayagi\attributes\FrontendRoute;

/**
 * Port-aware page router (multi-root / multi-port).
 *
 * Melayani beberapa root halaman berdasarkan port lokal koneksi:
 *   - port 8778 -> app/pages/         (web utama, namespace app\pages\)
 *   - port 8779 -> app/pages/  (web mobile, namespace app\pages\)
 *
 * Menggantikan \Yllumi\Sayagi\PageRouter::init() di config/route.php (plugin
 * maupun aplikasi). Registry root dibaca dari (prioritas):
 *   1) config('plugin.yllumi.sayagi.app.pages')  — default package (published)
 *   2) config('app.pages')                       — override legacy host
 *   3) built-in default (main 8778 / mobile 8779)
 *
 * Kontrak tiap root:
 *   'main' => ['port' => 8778, 'path' => app_path('pages'),        'ns' => 'app\\pages\\',        'default' => 'home'],
 *   'mobile'=> ['port' => 8779, 'path' => app_path('pages'), 'ns' => 'app\\pages\\', 'default' => 'home'],
 */
class PortPageRouter
{
    /**
     * @var array<string, array{port:int, path:string, ns:string, default:string}>
     */
    protected static ?array $roots = null;

    /**
     * Ambil registry root (resolved: plugin config -> app config -> built-in).
     *
     * @return array<string, array{port:int, path:string, ns:string, default:string}>
     */
    public static function getRoots(): array
    {
        if (static::$roots !== null) {
            return static::$roots;
        }

        $config = (array) config('plugin.yllumi.sayagi.app.pages', []);
        if ($config === []) {
            $config = (array) config('app.pages', []);
        }
        if ($config === []) {
            $config = [
                'main' => [
                    'port'    => 8778,
                    'path'    => app_path('pages'),
                    'ns'      => 'app\\pages\\',
                    'default' => 'home',
                ],
                'mobile' => [
                    'port'    => 8779,
                    'path'    => app_path('pages'),
                    'ns'      => 'app\\pages\\',
                    'default' => 'home',
                ],
            ];
        }

        // Normalisasi & beri default agar struktur selalu konsisten.
        $normalized = [];
        foreach ($config as $name => $cfg) {
            $normalized[(string) $name] = [
                'port'    => (int) ($cfg['port'] ?? 0),
                'path'    => (string) ($cfg['path'] ?? app_path('pages')),
                'ns'      => (string) ($cfg['ns'] ?? 'app\\pages\\'),
                'default' => (string) ($cfg['default'] ?? 'home'),
            ];
        }

        return static::$roots = $normalized;
    }

    /**
     * Ambil port dari root bernama (mis. 'mobile').
     */
    public static function getPort(string $name, int $default = 0): int
    {
        $root = static::getRoots()[$name] ?? null;
        return $root ? (int) $root['port'] : $default;
    }

    /**
     * Ambil path root halaman untuk port tertentu (mis. app/pages).
     */
    public static function getPagesPath(int $port): string
    {
        foreach (static::getRoots() as $root) {
            if ((int) $root['port'] === $port) {
                return $root['path'];
            }
        }
        return app_path('pages');
    }

    /**
     * Ambil prefix namespace untuk port tertentu (mis. app\pages\).
     */
    public static function getNsPrefix(int $port): string
    {
        foreach (static::getRoots() as $root) {
            if ((int) $root['port'] === $port) {
                return $root['ns'];
            }
        }
        return 'app\\pages\\';
    }

    /**
     * Ambil root untuk request berdasarkan port lokal koneksi.
     *
     * @return array{port:int, path:string, ns:string, default:string}
     */
    protected static function resolveRoot(Request $request): array
    {
        $port = 0;
        $connection = $request->connection ?? null;
        if ($connection !== null && method_exists($connection, 'getLocalPort')) {
            $port = (int) $connection->getLocalPort();
        }

        foreach (static::getRoots() as $root) {
            if ((int) $root['port'] === $port) {
                return $root;
            }
        }

        // Default: root pertama (biasanya 'main').
        $first = reset(static::getRoots());
        return $first !== false ? $first : [
            'port'    => 8778,
            'path'    => app_path('pages'),
            'ns'      => 'app\\pages\\',
            'default' => 'home',
        ];
    }

    /**
     * Registrasi fallback route port-aware.
     * Panggil di config/route.php (plugin maupun aplikasi host).
     */
    public static function init(): void
    {
        Route::fallback(function (Request $request) {
            // Gunakan static agar cache callback bertahan di memori worker.
            static $callbackCache = [];

            $root        = static::resolveRoot($request);
            $pagesPath   = $root['path'];
            $nsPrefix    = $root['ns'];
            $defaultPage = $root['default'];

            // Gunakan path() bukan uri() agar query string tidak masuk pengecekan file.
            $path     = trim($request->path(), '/');
            $httpVerb = strtolower($request->method());

            if ($path === '') {
                $path = $defaultPage;
            }

            // Cache key per method + path (mekanisme sama dengan Webman internal).
            // Aman lintas proses karena satu worker hanya melayani satu port.
            $cacheKey = strtoupper($httpVerb) . '/' . $path;

            if (isset($callbackCache[$cacheKey])) {
                [$callback, $request->controller, $request->action] = $callbackCache[$cacheKey];
                $request->plugin = '';
                $request->app    = '';
                return $callback($request);
            }

            $controllerName = 'PageController';
            $methodName     = $httpVerb . 'Index';
            $params         = [];

            $uriSegments = explode('/', $path);

            // Normalize segments: ganti dash dengan underscore untuk lookup folder.
            $normalizedSegments = array_map(fn($s) => str_replace('-', '_', $s), $uriSegments);
            while ($normalizedSegments !== []) {
                $subPath    = implode('/', $normalizedSegments);
                $folderPath = $pagesPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);

                if (is_dir($folderPath) && file_exists($folderPath . DIRECTORY_SEPARATOR . $controllerName . '.php')) {
                    $controllerNamespace = $nsPrefix . str_replace('/', '\\', $subPath) . "\\$controllerName";
                    $params              = array_reverse($params);

                    // Tentukan method: method spesifik (GET/POST + Action) atau Index.
                    if (isset($params[0]) && method_exists($controllerNamespace, $httpVerb . ucfirst($params[0]))) {
                        $methodName = $httpVerb . ucfirst($params[0]);
                        array_shift($params);
                    } elseif (!method_exists($controllerNamespace, $methodName)) {
                        return static::notFoundResponse($request, $root, $callbackCache);
                    }

                    if (!class_exists($controllerNamespace)) {
                        return static::notFoundResponse($request, $root, $callbackCache);
                    }

                    // Konversi positional params ke named params agar cocok dengan DI Webman.
                    try {
                        $refParams = (new \ReflectionMethod($controllerNamespace, $methodName))->getParameters();
                        if (!empty($refParams)) {
                            $fp        = $refParams[0];
                            $isRequest = ($fp->hasType() && stripos($fp->getType()->getName(), 'Request') !== false)
                                || strtolower($fp->getName()) === 'request';
                            if ($isRequest) {
                                array_shift($refParams);
                            }
                        }
                        $namedParams = [];
                        foreach ($params as $i => $value) {
                            if (isset($refParams[$i])) {
                                $namedParams[$refParams[$i]->getName()] = $value;
                            }
                        }
                        $params = $namedParams;
                    } catch (\ReflectionException $e) {
                    }

                    // Set request context agar middleware bisa membaca controller & action.
                    $request->plugin     = '';
                    $request->app        = '';
                    $request->controller = $controllerNamespace;
                    $request->action     = $methodName;

                    // Bungkus controller call dengan middleware via App::getCallback().
                    $callback = \Webman\App::getCallback('', '', [$controllerNamespace, $methodName], $params, true, null);
                    $callbackCache[$cacheKey] = [$callback, $controllerNamespace, $methodName];

                    return $callback($request);
                }

                $params[] = array_pop($uriSegments);
                array_pop($normalizedSegments);
            }

            return static::notFoundResponse($request, $root, $callbackCache);
        });
    }

    /**
     * Respons NotFound dengan middleware, per root.
     * Fallback: notfound root -> notfound utama (app\pages) -> 404 minimal.
     */
    protected static function notFoundResponse(Request $request, array $root, array &$callbackCache): \Webman\Http\Response
    {
        $ns         = $root['ns'];
        $controller = $ns . 'notfound\\PageController';

        if (!class_exists($controller)) {
            $controller = 'app\\pages\\notfound\\PageController';
        }

        // Root tidak punya halaman notfound -> kembalikan 404 minimal.
        if (!class_exists($controller)) {
            return \Webman\Http\Response::create(404, ['Content-Type' => 'text/html; charset=utf-8'], '<h1>404 Not Found</h1>');
        }

        $notFoundCacheKey = 'GET/__notfound__/' . $controller;
        if (!isset($callbackCache[$notFoundCacheKey])) {
            $callbackCache[$notFoundCacheKey] = [
                \Webman\App::getCallback('', '', [$controller, 'getIndex'], [], true, null),
                $controller,
                'getIndex',
            ];
        }
        [$callback, $request->controller, $request->action] = $callbackCache[$notFoundCacheKey];
        $request->plugin = '';
        $request->app    = '';
        $response = $callback($request);

        // Halaman "tidak ditemukan" harus berstatus HTTP 404, bukan 200.
        return $response instanceof \Webman\Http\Response ? $response->withStatus(404) : $response;
    }

    /**
     * Scan frontend routes (atribut #[FrontendRoute]) di sebuah root tertentu.
     *
     * @param int $port Port root yang discan.
     * @return array<int|string, array{template:string, preload:mixed, handler:mixed}>
     */
    public static function scanFrontendRouters(int $port): array
    {
        $pagesPath = static::getPagesPath($port);
        $nsPrefix  = static::getNsPrefix($port);
        $router    = [];

        $directory = new \RecursiveDirectoryIterator($pagesPath);
        $iterator  = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            $folderPath = str_replace($pagesPath . DIRECTORY_SEPARATOR, '', $file->getPath());

            // Exclude folder dengan prefix underscore (mis. _layouts).
            if (
                strpos($folderPath, '_') === 0
                || strpos($folderPath, DIRECTORY_SEPARATOR . '_') !== false
            ) {
                continue;
            }

            // Hanya proses PageController.php
            if ($file->isFile() && $file->getFilename() === 'PageController.php') {
                $relativePath    = str_replace($pagesPath . DIRECTORY_SEPARATOR, '', $file->getPath());
                $namespacePath   = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                $controllerClass = $nsPrefix . $namespacePath . '\\PageController';

                if (class_exists($controllerClass)) {
                    $reflectionClass = new \ReflectionClass($controllerClass);
                    $classAttributes = $reflectionClass->getAttributes(FrontendRoute::class);
                    foreach ($classAttributes as $attribute) {
                        $instance = $attribute->newInstance();

                        if (empty($instance->route)) {
                            $instance->route = '/' . trim(str_replace(DIRECTORY_SEPARATOR, '/', $relativePath), '/');
                        }

                        $router[$instance->route] = [
                            'template' => $instance->template ? $instance->template : $instance->route . '/template',
                            'preload'  => $instance->preload,
                            'handler'  => $instance->handler,
                        ];
                    }
                }
            }
        }

        // Urutkan: path ber-param lebih dulu, lalu jumlah segment menurun.
        uksort($router, function ($a, $b) {
            $hasParamA = strpos($a, ':') !== false;
            $hasParamB = strpos($b, ':') !== false;

            if ($hasParamA && !$hasParamB) {
                return -1;
            }
            if (!$hasParamA && $hasParamB) {
                return 1;
            }

            $segmentsA = count(explode('/', trim($a, '/')));
            $segmentsB = count(explode('/', trim($b, '/')));
            return $segmentsB - $segmentsA;
        });

        return $router;
    }

    /**
     * Generate inline JS `window.__F7_ROUTES__` untuk root tertentu —
     * analog FERouter::getF7RoutesScript() tapi root-aware (scan per port).
     */
    public static function getF7RoutesScript(int $port): string
    {
        $map    = static::scanFrontendRouters($port);
        $routes = [];

        foreach ($map as $route => $prop) {
            // Hanya route publik (berawalan '/'); hindari route khusus (mis. notfound).
            if (strpos($route, '/') !== 0) {
                continue;
            }

            $template   = $prop['template'] ?? ($route . '/template');
            $serverPath = trim(str_replace('\\', '/', $template), '/');
            $serverPath = preg_replace('#/template$#', '', $serverPath);

            $param = null;
            if (preg_match('#:([^/]+)#', $route, $m)) {
                $param = $m[1];
            }

            $routes[] = [
                'path'       => $route,
                'serverPath' => $serverPath,
                'param'      => $param,
            ];
        }

        // Urutkan ala F7 (path-to-regexp): route statis dulu (segment pendek ->
        // panjang), baru route ber-param — urutan paling aman untuk matching.
        usort($routes, function ($a, $b) {
            $paramA = $a['param'] !== null;
            $paramB = $b['param'] !== null;
            if ($paramA !== $paramB) {
                return $paramA ? 1 : -1;
            }
            $segA = count(explode('/', trim($a['path'], '/')));
            $segB = count(explode('/', trim($b['path'], '/')));
            return $segA <=> $segB;
        });

        $json = json_encode($routes, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return 'window.__F7_ROUTES__ = ' . $json . ';';
    }
}
