<?php namespace app\pages\offline;

use Yllumi\Sayagi\BaseController;
use Yllumi\Sayagi\attributes\FrontendRoute;

#[FrontendRoute(route: 'offline', preload: true)]
class PageController extends BaseController
{
    public $data = [
        'page_title' => 'You are Offline'
    ];
   
}
