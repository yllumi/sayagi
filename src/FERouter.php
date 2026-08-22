<?php

namespace Yllumi\Sayagi;

class FERouter
{
    public array $router;

    public function __construct()
    {
        $this->router = PageRouter::scanFrontendRouters();
    }

    public static function getRouterArray(): array
    {
        return (new self())->router;
    }

    public static function getRouter(string $ssrRoute = '', string $ssrContent = '', ?array $ssrData = null): string
    {
        $html = ltrim(PageRouter::renderRouter((new self())->router));

        if ($ssrRoute !== '' && $ssrContent !== '') {
            $html = (new self())->injectSsrContent($html, $ssrRoute, $ssrContent);
        }

        if ($ssrData !== null) {
            $ssrUrl = ($ssrRoute === '/' || $ssrRoute === '') ? 'home/data' : ltrim($ssrRoute, '/') . '/data';
            $html .= "\n<script>" . self::ssrDataScript($ssrData, $ssrUrl) . "</script>";
        }

        return $html;
    }

    /**
     * Generate the inline JS assignment for window.__HEROIC_SSR_DATA__.
     * Drop this inside the existing <script> block in your layout.
     *
     * Usage: <?= \Yllumi\Sayagi\FERouter::ssrDataScript($ssr_data ?? null) ?>
     */
    public static function ssrDataScript(?array $data, string $url = ''): string
    {
        $json = $data !== null ? json_encode($data, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) : 'null';
        $urlJson = json_encode($url);
        return "window.__HEROIC_SSR_DATA__ = {$json}; window.__HEROIC_SSR_URL__ = {$urlJson};";
    }

    /**
     * Daftar routes F7 (section /mobile) yang di-generate dari atribut
     * #[FrontendRoute] tiap class PageController — analog dari getRouterArray()
     * untuk Pinecone Router, tapi dalam bentuk config yang siap dikonsumsi
     * f7-app.js.
     *
     * Tiap item route:
     *   [
     *     'path'       => '/mobile/books/:id/',   // URL publik (dari atribut)
     *     'serverPath' => 'mobile/books/detail',  // folder server template/data
     *     'param'      => 'id',                   // segment dinamis, null jika tidak ada
     *   ]
     *
     * @param string $section Root section F7 (default '/mobile/').
     * @return array
     */
    public static function getF7Routes(string $section = '/mobile/'): array
    {
        $section = '/' . trim($section, '/') . '/';
        $map     = PageRouter::scanFrontendRouters();
        $routes  = [];

        foreach ($map as $route => $prop) {
            // Hanya route di dalam section F7 (mis. /mobile/...)
            if (strpos($route, $section) !== 0) {
                continue;
            }

            // serverPath diturunkan dari template:
            // '/mobile/books/detail/template' -> 'mobile/books/detail'
            $template   = $prop['template'] ?? ($route . '/template');
            $serverPath = trim(str_replace('\\', '/', $template), '/');
            $serverPath = preg_replace('#/template$#', '', $serverPath);

            // Deteksi segment dinamis pada URL publik (mis. ':id')
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

        return $routes;
    }

    /**
     * Generate inline JS assignment untuk window.__F7_ROUTES__.
     * Diletakkan di dalam <script> pada layout F7 (_layouts/mobile.php) —
     * dipakai f7-app.js untuk membangun daftar routes Framework7.
     *
     * Usage: <?= \Yllumi\Sayagi\FERouter::getF7RoutesScript() ?>
     *
     * @param string $section Root section F7 (default '/mobile/').
     * @return string
     */
    public static function getF7RoutesScript(string $section = '/mobile/'): string
    {
        $json = json_encode(
            self::getF7Routes($section),
            JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        return 'window.__F7_ROUTES__ = ' . $json . ';';
    }

    /**
     * Replace the external x-template for the matched SSR route with an inline
     * template containing the server-rendered HTML. Pinecone Router will then
     * render the content immediately without an extra network request.
     */
    private function injectSsrContent(string $html, string $ssrRoute, string $ssrContent): string
    {
        $escapedRoute = preg_quote($ssrRoute, '#');

        return preg_replace_callback(
            '#(<template \nx-route="' . $escapedRoute . '" \n)x-template[^\n]+\n(x-handler="[^"]*")?><\/template>#',
            function ($matches) use ($ssrContent) {
                $open    = $matches[1];
                $handler = !empty($matches[2]) ? $matches[2] : '';
                return $open . "x-template\n" . $handler . ">\n" . $ssrContent . "\n</template>";
            },
            $html
        );
    }
}
