<?php

namespace App\Http\Controllers;

class AdminV2Controller extends Controller
{
    public function index()
    {
        return view('admin.index');
    }

    public function archive()
    {
        return view('admin.archive');
    }

    public function log()
    {
        return view('admin.log');
    }

    public function userManage()
    {
        return view('admin.user-manage');
    }

    public function dataMaster()
    {
        return view('admin.datamaster');
    }

    public function backup()
    {
        return view('admin.backup');
    }

    public function help()
    {
        return view('admin.help');
    }
}
