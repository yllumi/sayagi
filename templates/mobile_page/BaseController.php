<?php

declare(strict_types=1);

namespace app\pages;

use support\Request;
use Yllumi\Sayagi\attributes\FrontendRoute;

class BaseController
{
    public $data = [];

    public function getIndex(Request $request)
    {
        // Webman meneruskan parameter route (mis. /books/{id} → $id) sebagai
        // argumen tambahan setelah $request — kumpulkan agar bisa diteruskan
        // otomatis ke getData() (penting untuk SSR deep-link /books/{id}).
        $routeParams = array_slice(func_get_args(), 1);

        // Populate $this->data dari getData() (SSR data gathering).
        // Parameter route (mis. id) ikut diteruskan supaya data detail termuat.
        if (method_exists($this, 'getData')) {
            $this->getData($request, ...$routeParams);
        }

        // Derive template path relative to app/pages/ (e.g. "home/template")
        $classWithNamespace = get_class($this);
        $templateRelPath = str_replace('app\\pages\\', '', $classWithNamespace);
        $templateRelPath = str_replace('\\PageController', '\\template', $templateRelPath);
        $templateRelPath = strtolower(str_replace('\\', '/', $templateRelPath));

        // Render template HTML to string for SSR injection
        $ssrContent = pageViewRoot(mobile_pages_path(), $templateRelPath, $this->data);

        return view('/app/pages/_layouts/index', array_merge($this->data, [
            // ssr_route aktual (placeholder :id disubstitusi, mis. /books/2/)
            'ssr_route'   => $this->resolveRouteParams($this->getPageRoute(), $routeParams),
            'ssr_content' => $ssrContent,
            'ssr_data'    => $this->data,
        ]));
    }

    public function getTemplate(Request $request)
    {
        // get current class namespace
        $classWithNamespace = get_class($this);

        // Remove classname
        $templatePath = str_replace('PageController', 'template', $classWithNamespace);

        // Change backslash to slash and lowercase
        $templatePath = '/' . strtolower(str_replace('\\', '/', $templatePath));

        return view($templatePath, $this->data);
    }

    protected function getPageRoute(): string
    {
        $reflectionClass = new \ReflectionClass($this);
        $attrs = $reflectionClass->getAttributes(FrontendRoute::class);

        if (!empty($attrs)) {
            $instance = $attrs[0]->newInstance();
            if (!empty($instance->route)) {
                return $instance->route;
            }
        }

        // Auto-derive from class namespace (same logic as PageRouter::scanFrontendRouters)
        $classWithNamespace = get_class($this);
        $relativePath = str_replace('app\\pages\\', '', $classWithNamespace);
        $relativePath = str_replace('\\PageController', '', $relativePath);
        $relativePath = strtolower(str_replace('\\', '/', $relativePath));
        return '/' . $relativePath;
    }

    /**
     * Substitusi placeholder route (mis. :id) dengan nilai param aktual agar
     * ssr_route sesuai URL publik (mis. /books/2/), bukan /books/:id/.
     */
    protected function resolveRouteParams(string $route, array $params): string
    {
        if ($params === []) {
            return $route;
        }
        $i = 0;
        return preg_replace_callback('/:([^\/]+)/', function () use ($params, &$i) {
            return $params[$i++] ?? '';
        }, $route);
    }

}
