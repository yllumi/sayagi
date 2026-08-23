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
     *     'path'     => '/mobile/books/:id/',   // URL publik (dari atribut, bisa ber-param)
     *     'template' => '/mobile/books/detail/template', // path template statis
     *   ]
     *
     * Template bisa eksplisit dari atribut #[FrontendRoute(template: ...)] atau
     * diturunkan dari path route (buang segment parameter). URL data diturunkan
     * client (f7-app.js) dari template + parameter.
     *
     * @param string $section Root section F7 (default '/mobile/').
     * @return array
     */
    public static function getF7Routes(?string $section = null): array
    {
        // Normalisasi section F7 (mis. '/mobile/'); null = semua route publik
        // (standalone mobile di app/pages tanpa sub-section).
        $section = $section !== null ? '/' . trim($section, '/') . '/' : null;
        $map     = PageRouter::scanFrontendRouters();
        $routes  = [];

        foreach ($map as $route => $prop) {
            if ($section !== null && strpos($route, $section) !== 0) {
                continue;
            }

            // Template: eksplisit dari atribut, atau turunan path route dengan
            // membuang segment parameter. Contoh path '/course/:course_id/lessons'
            // tanpa template -> template '/course/lessons/template'.
            $template = $prop['template'] ?? null;
            if (empty($template)) {
                $cleanRoute = rtrim(preg_replace('#(^|/):[^/]+#', '$1', $route), '/');
                $cleanRoute = $cleanRoute === '' ? '/home' : $cleanRoute;
                $template = $cleanRoute . '/template';
            }

            $routes[] = [
                'path'     => $route,
                'template' => $template,
            ];
        }

        // Urutkan ala F7 (path-to-regexp): route statis dulu (segment pendek ->
        // panjang), baru route ber-param — urutan paling aman untuk matching.
        usort($routes, function ($a, $b) {
            $paramA = strpos($a['path'], ':') !== false;
            $paramB = strpos($b['path'], ':') !== false;
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
    public static function getF7RoutesScript(?string $section = null): string
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
