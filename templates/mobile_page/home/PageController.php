<?php

declare(strict_types=1);

namespace app\pages\home;

use support\Request;
use app\pages\BaseController;
use Yllumi\Sayagi\attributes\FrontendRoute;
/**
 * Landing web mobile — root app/pages/, port 8779.
 *
 * SSR:  /            -> getIndex (landing)
 * CSR:  F7 async route memanggil:
 *       /home/template -> getTemplate (fragmen .page, via BaseController)
 *       /home/data     -> getData (JSON)
 */
#[FrontendRoute(route: '/', template: '/home/template')]
class PageController extends BaseController
{
    public $data = [];

    public function getData(Request $request)
    {
        $this->data = [
            'version'     => '1.0.0',
            'php_version' => PHP_VERSION,
            'stack' => [
                ['name' => 'Webman',         'desc' => 'High-performance PHP framework built on Workerman. Persistent process, ~1ms response.', 'icon' => 'bi-lightning-charge-fill', 'color' => '#6366f1'],
                ['name' => 'Alpine.js',      'desc' => 'Lightweight reactive framework. Declarative DOM manipulation without the overhead.', 'icon' => 'bi-snow',                'color' => '#06b6d4'],
                ['name' => 'Framework7',     'desc' => 'Mobile-first framework for building iOS and Android apps.', 'icon' => 'bi-signpost-split-fill', 'color' => '#10b981'],
            ],
            'features' => [
                ['icon' => 'bi-server',           'title' => 'SSR',  'desc' => 'Server-Side Rendering. First load delivers fully rendered HTML — no layout shift.'],
                ['icon' => 'bi-arrow-repeat',     'title' => 'ISR',  'desc' => 'Incremental Static Regeneration. Cache rendered pages, revalidate on a TTL schedule.'],
                ['icon' => 'bi-grid-1x2',         'title' => 'SPA',  'desc' => 'Subsequent navigations handled client-side. Instant page transitions, zero reload.'],
                ['icon' => 'bi-file-earmark-code','title' => 'File-based Routing', 'desc' => 'Drop a PageController in app/pages/ and the route is registered automatically.'],
            ],
        ];

        return json($this->data);
    }
}
