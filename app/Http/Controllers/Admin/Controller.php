<?php

namespace App\Http\Controllers\Admin;

class Controller extends \App\Http\Controllers\Controller
{
    /**
     * Connects middleware authorization for all admin controllers.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }
}
