<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    /**
     * Display the admin help page.
     */
    public function index()
    {
        return view('admin.help.index', [
            'title' => 'Help',
            'subTitle' => 'Admin Panel Guide',
        ]);
    }
}