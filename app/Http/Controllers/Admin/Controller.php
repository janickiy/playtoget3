<?php

namespace App\Http\Controllers\Admin;

class Controller extends \App\Http\Controllers\Controller
{
    /**
     * Connects middleware authorization для всех админских контроллеров.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }
}
