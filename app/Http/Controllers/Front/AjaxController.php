<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function handle(Request $request, string $action): JsonResponse
    {
        return response()->json([
            'action' => $action,
            'status' => 'not_implemented',
            'payload' => $request->except(['_token']),
        ]);
    }
}
