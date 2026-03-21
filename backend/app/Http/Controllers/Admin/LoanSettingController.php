<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(LoanSetting::instance());
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date'          => ['required', 'date'],
            'interval_days'       => ['required', 'integer', 'min:1'],
            'grace_days'          => ['required', 'integer', 'min:0'],
            'loan_duration_weeks' => ['required', 'integer', 'min:1'],
        ]);

        $setting = LoanSetting::instance();
        $setting->update($data);

        return response()->json($setting);
    }
}
