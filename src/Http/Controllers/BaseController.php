<?php

namespace Chatify\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;

class BaseController extends Controller
{

    public function __construct()
    {
        Config::set('auth.defaults.guard', 'user');
    }
}
