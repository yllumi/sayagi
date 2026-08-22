<?php namespace app\pages\offline;

use app\pages\BaseController;
use Yllumi\Sayagi\attributes\FrontendRoute;

#[FrontendRoute(route: 'offline', preload: true)]
class PageController extends BaseController
{
    public $data = [
        'page_title' => 'You are Offline'
    ];
   
}
