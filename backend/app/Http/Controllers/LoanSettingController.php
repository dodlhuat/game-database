<?php

namespace App\Http\Controllers;

use App\Models\LoanSetting;
use Illuminate\Http\JsonResponse;

class LoanSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(LoanSetting::instance());
    }
}
