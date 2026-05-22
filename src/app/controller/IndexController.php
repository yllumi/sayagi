<?php

namespace Yllumi\Sayagi\app\controller;

use Yllumi\Sayagi\attributes\RequirePrivilege;
use support\Request;

class IndexController extends AdminController
{
    #[RequirePrivilege('dashboard.read')]
    public function index(Request $request)
    {
        $data['page_title'] = 'Dashboard';

        return render('index/index', $data);
    }

    public function testSendEmail(Request $request)
    {
        $emailSender = new \Yllumi\Sayagi\libraries\EmailSender();

        try {
            $emailSender->sendEmail(
                'recipient@example.com',
                'Test Email',
                '<p>This is a test email.</p>'
            );
        } catch (\Exception $e) {
            // Handle the exception
        }
    }

}
