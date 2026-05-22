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
