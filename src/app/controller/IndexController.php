<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\attributes\RequirePrivilege;
use support\Request;

class IndexController extends AdminController
{
    #[RequirePrivilege('dashboard.read')]
    public function index(Request $request)
    {
        return redirect(setting('site.default_admin_page') ?? '/panel/dashboard');
    }

}
