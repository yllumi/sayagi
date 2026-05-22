<?php namespace app\pages\docs;

use Yllumi\Sayagi\attributes\FrontendRoute;
use Yllumi\Sayagi\BaseController;

#[FrontendRoute()]
class PageController extends BaseController
{
    public $data = [
        'page_title' => 'Documentation'
    ];

}
