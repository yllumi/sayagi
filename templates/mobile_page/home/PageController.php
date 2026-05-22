<?php namespace app\pages\home;

use Yllumi\Sayagi\attributes\FrontendRoute;
use Yllumi\Sayagi\BaseController;

#[FrontendRoute(route: '/', template: '/home/template', preload: true)]
class PageController extends BaseController
{
    public $data = [];

    public function getData()
    {
        $this->data = [
            'version'     => '1.0.0',
            'php_version' => PHP_VERSION,
            'stack' => [
                ['name' => 'Webman',         'desc' => 'High-performance PHP framework built on Workerman. Persistent process, ~1ms response.', 'icon' => 'bi-lightning-charge-fill', 'color' => '#6366f1'],
                ['name' => 'Alpine.js',      'desc' => 'Lightweight reactive framework. Declarative DOM manipulation without the overhead.', 'icon' => 'bi-snow',                'color' => '#06b6d4'],
                ['name' => 'Pinecone Router','desc' => 'Client-side SPA routing with inline template support for seamless SSR hydration.', 'icon' => 'bi-signpost-split-fill', 'color' => '#10b981'],
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
