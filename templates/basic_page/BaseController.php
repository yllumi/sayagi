<?php

namespace app\pages;

use support\Request;
use Yllumi\Sayagi\attributes\FrontendRoute;

class BaseController
{
    public $data = [];

    public function getIndex(Request $request)
    {
        // Populate $this->data from getData() if it exists (SSR data gathering)
        if (method_exists($this, 'getData')) {
            $this->getData($request);
        }

        // Derive template path relative to app/pages/ (e.g. "home/template")
        $classWithNamespace = get_class($this);
        $templateRelPath = str_replace('app\\pages\\', '', $classWithNamespace);
        $templateRelPath = str_replace('\\PageController', '\\template', $templateRelPath);
        $templateRelPath = strtolower(str_replace('\\', '/', $templateRelPath));

        // Render template HTML to string for SSR injection
        $ssrContent = pageView($templateRelPath, $this->data);

        return view('/app/pages/_layouts/index', array_merge($this->data, [
            'ssr_route'   => $this->getPageRoute(),
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

}
