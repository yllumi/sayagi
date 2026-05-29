<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\attributes\RequirePrivilege;
use support\Db;
use support\Request;

class DashboardController extends AdminController
{
    protected $data = [
        'page_title' => '',
        'module' => 'dashboard',
        'submodule' => 'dashboard',
    ];

    #[RequirePrivilege('dashboard.read')]
    public function index(Request $request)
    {
        $sessionUser = $request->session()->get('user', []);

        $this->data['page_title'] = 'Dashboard';
        $this->data['user_name'] = $sessionUser['name'] ?? $sessionUser['username'] ?? 'User';
        $this->data['active_users'] = (int) Db::table('mein_users')->where('status', 'active')->count();
        $this->data['inactive_users'] = (int) Db::table('mein_users')->where('status', 'inactive')->count();

        return render('dashboard/index', $this->data);
    }
}
