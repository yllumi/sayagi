<?php

declare(strict_types=1);

namespace app\pages\notfound;

use support\Request;
use app\pages\BaseController;

/**
 * Halaman 404 web mobile — root app/pages/, port 8779.
 *
 * Tanpa atribut #[FrontendRoute] agar tidak masuk daftar route F7.
 */
class PageController extends BaseController
{
    public $data = [];
}
