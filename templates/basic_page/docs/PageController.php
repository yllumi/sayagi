<?php namespace app\pages\docs;

use app\pages\BaseController;
use Yllumi\Sayagi\attributes\FrontendRoute;

#[FrontendRoute()]
class PageController extends BaseController
{
    public $data = [
        'page_title' => 'Documentation'
    ];

}
