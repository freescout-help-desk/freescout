<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //Index Route
    public function index()
    {
        return view('/dashboard/dashboard');
    }

}
