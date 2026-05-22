<?php
namespace Yllumi\Sayagi\attributes;

use Attribute;

/**
 * Pasang di atas method controller untuk mengatur route frontend.
 *
 * Contoh penggunaan:
 * 
 ** Penggunaan sederhana 
 *  #[FrontendRoute('/about')]
 *  #[FrontendRoute(route: '/about')]
 * 
 ** Bila page path template berbeda dengan route
 *  #[FrontendRoute(route: '/contact', template: 'contact_us/template')]
 *
 ** Route dengan preload template
 *  #[FrontendRoute(route: '/profile', template: 'profile/index', preload: true)]
 * 
 ** Route dengan frontend handler
 *  #[FrontendRoute(route: '/dashboard', template: 'dashboard/index', handler: [isloggedin])] 
 * 
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class FrontendRoute 
{
    public function __construct(
        public string $route = '',
        public string $template = '',
        public bool $preload = false,
        public array $handler = []
    ) {}
}