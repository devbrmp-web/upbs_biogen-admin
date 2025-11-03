<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display the admin profile page.
     */
    public function show()
    {
        $user = auth()->user();
        
        return view('admin.profile.show', [
            'title' => 'Profile',
            'subTitle' => 'User Profile',
            'user' => $user
        ]);
    }
}