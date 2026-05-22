<?php

namespace Yllumi\Sayagi;

use Yllumi\Sayagi\attributes\FrontendRoute;

class PageRouter
{
    /**
     * Inisialisasi routing berbasis halaman
     */
    public static function init()
    {
        \Webman\Route::fallback(function (\support\Request $request) {
            // Gunakan static agar cache callback bertahan di memori worker
            static $callbackCache = [];

            // Gunakan path() bukan uri() untuk menghindari query string masuk ke pengecekan file
            $path = trim($request->path(), '/');
            $httpVerb = strtolower($request->method());

            if ($path === '') {
                $path = config('app.default_page', 'home');
            }

            // Cache key per method + path, sama seperti mekanisme cache internal Webman
            $cacheKey = strtoupper($httpVerb) . '/' . $path;

            if (isset($callbackCache[$cacheKey])) {
                [$callback, $request->controller, $request->action] = $callbackCache[$cacheKey];
                $request->plugin = '';
                $request->app    = '';
                return $callback($request);
            }

            $pagesPath      = config('app.pages_path', app_path('pages'));
            $controllerName = 'PageController';
            $methodName     = $httpVerb . 'Index';
            $params         = [];

            $uriSegments = explode('/', $path);

            // Normalize segments: replace dashes with underscores for folder lookup
            $normalizedSegments = array_map(fn($s) => str_replace('-', '_', $s), $uriSegments);
            while ($normalizedSegments !== []) {
                $subPath = implode('/', $normalizedSegments);
                $folderPath = $pagesPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subPath);

                if (is_dir($folderPath) && file_exists($folderPath . DIRECTORY_SEPARATOR . $controllerName . '.php')) {

                    $controllerNamespace = "app\\pages\\" . str_replace('/', '\\', $subPath) . "\\$controllerName";
                    $params = array_reverse($params);

                    // Tentukan Method: apakah itu method spesifik (GET/POST + Action) atau Index
                    if (isset($params[0]) && method_exists($controllerNamespace, $httpVerb . ucfirst($params[0]))) {
                        $methodName = $httpVerb . ucfirst($params[0]);
                        array_shift($params);
                    } elseif (!method_exists($controllerNamespace, $methodName)) {
                        // Method Index tidak ada, lempar ke NotFound
                        return static::notFoundResponse($request, $callbackCache);
                    }

                    if (!class_exists($controllerNamespace)) {
                        return static::notFoundResponse($request, $callbackCache);
                    }

                    // Konversi positional params ke named params agar sesuai dengan
                    // sistem DI Webman yang melakukan lookup berdasarkan nama parameter
                    try {
                        $refParams = (new \ReflectionMethod($controllerNamespace, $methodName))->getParameters();
                        if (!empty($refParams)) {
                            $fp = $refParams[0];
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
                    } catch (\ReflectionException $e) {}

                    // Set request context agar middleware bisa membaca controller & action
                    $request->plugin     = '';
                    $request->app        = '';
                    $request->controller = $controllerNamespace;
                    $request->action     = $methodName;

                    // Bungkus controller call dengan middleware menggunakan App::getCallback()
                    // Params URL di-bake ke dalam callback dan di-cache per path unik
                    $callback = \Webman\App::getCallback('', '', [$controllerNamespace, $methodName], $params, true, null);
                    $callbackCache[$cacheKey] = [$callback, $controllerNamespace, $methodName];

                    return $callback($request);
                }

                $params[] = array_pop($uriSegments);
                array_pop($normalizedSegments);
            }

            return static::notFoundResponse($request, $callbackCache);
        });
    }

    /**
     * Return NotFound response, with middleware applied.
     */
    protected static function notFoundResponse(\support\Request $request, array &$callbackCache): \Webman\Http\Response
    {
        $notFoundCacheKey = 'GET/__notfound__';
        if (!isset($callbackCache[$notFoundCacheKey])) {
            $callbackCache[$notFoundCacheKey] = [
                \Webman\App::getCallback('', '', [\app\pages\notfound\PageController::class, 'getIndex'], [], true, null),
                \app\pages\notfound\PageController::class,
                'getIndex',
            ];
        }
        [$callback, $request->controller, $request->action] = $callbackCache[$notFoundCacheKey];
        $request->plugin = '';
        $request->app    = '';
        return $callback($request);
    }

    public static function scanFrontendRouters($pagesPath = null)
    {
        $pagesPath = $pagesPath ?? config('app.pages_path', app_path('pages'));
        $router = [];

        // Scan pagePath recursively
        $directory = new \RecursiveDirectoryIterator($pagesPath);
        $iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            // Get pathname starts from pages folder
            $folderPath = str_replace($pagesPath . DIRECTORY_SEPARATOR, '', $file->getPath());

            // Exclude page folder with prefix underscore
            if (
                strpos($folderPath, '_') === 0
                || strpos($folderPath, DIRECTORY_SEPARATOR . '_') !== false
            ) {
                continue;
            }

            // Only process page with PageController.php files
            if ($file->isFile() && $file->getFilename() === 'PageController.php') {
                $relativePath = str_replace($pagesPath . DIRECTORY_SEPARATOR, '', $file->getPath());
                $namespacePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                $controllerClass = "\\app\\pages\\$namespacePath\\PageController";

                if (class_exists($controllerClass)) {
                    $reflectionClass = new \ReflectionClass($controllerClass);
                    $classAttributes = $reflectionClass->getAttributes(FrontendRoute::class);
                    foreach ($classAttributes as $attribute) {
                        $instance = $attribute->newInstance();

                        // Jika route tidak di-set, gunakan path berdasarkan folder
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

        // Urutkan router berdasarkan path yang lebih spesifik
        uksort($router, function ($a, $b) {
            $hasParamA = strpos($a, ':') !== false;
            $hasParamB = strpos($b, ':') !== false;
            
            // Dahulukan path dengan parameter
            if ($hasParamA && !$hasParamB) {
                return -1;
            }
            if (!$hasParamA && $hasParamB) {
                return 1;
            }
            
            // Jika sama-sama punya/tidak punya parameter, urutkan berdasarkan jumlah segment
            $segmentsA = count(explode('/', trim($a, '/')));
            $segmentsB = count(explode('/', trim($b, '/')));
            return $segmentsB - $segmentsA;
        });

        return $router;
    }

    public static function renderRouter($router = [], $minify = false)
    {
        $routerString = "";

        foreach ($router as $route => $routeProp) {
            $routePath = is_string($routeProp) ? $routeProp : $route;
            $routerString .= "<template \nx-route=\"{$routePath}\" \n";

            // Siapkan nilai template
            $templateStr = "";
            $hasParamInTemplate = false;

            if (isset($routeProp['template'])) {
                if (is_array($routeProp['template'])) {
                    $templateStr = str_replace(['"', '\/'], ["'", '/'], json_encode($routeProp['template']));
                    $hasParamInTemplate = preg_match('/:([^\/]+)/', implode('', $routeProp['template']));
                } else {
                    $templateStr = $routeProp['template'];
                    $hasParamInTemplate = preg_match('/:([^\/]+)/', $templateStr);
                }
            } else {
                // Default template jika tidak disediakan
                $cleanedPath = "/" . trim(preg_replace('/:([^\/]+)/', '', $routePath), '/');
                $cleanedPath = $cleanedPath === '/' ? '/home' : $cleanedPath;
                $templateStr = $cleanedPath . "/template";
            }

            $routerString .= "x-template"
                . (($routeProp['preload'] ?? false) ? ".preload" : "")
                . ($hasParamInTemplate ? ".interpolate" : "")
                . "=\"{$templateStr}\" \n";

            if (isset($routeProp['handler']) && !empty($routeProp['handler'])) {
                $handlerString = is_array($routeProp['handler']) ? implode(',', $routeProp['handler']) : $routeProp['handler'];
                $routerString .= "x-handler=\"" . $handlerString . "\"";
            }

            $routerString .= "></template>\n\n";
        }

        return $minify ? str_replace("\n", "", $routerString) : $routerString;
    }
}
