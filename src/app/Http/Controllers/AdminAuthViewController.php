<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAuthViewController extends Controller
{
       public function create()
    {
        return view('admin.login');
    }
}
