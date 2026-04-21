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
            'start_date'                => ['required', 'date'],
            'interval_days'             => ['required', 'integer', 'min:1'],
            'grace_days'                => ['required', 'integer', 'min:0'],
            'loan_duration_weeks'       => ['required', 'integer', 'min:1'],
            'max_extensions'            => ['required', 'integer', 'min:0'],
            'loan_cost'                 => ['required', 'integer', 'min:0'],
            'condition_very_good_after' => ['required', 'integer', 'min:1'],
            'condition_good_after'      => ['required', 'integer', 'min:1'],
            'deposit_pct_very_good'     => ['required', 'integer', 'min:0', 'max:100'],
            'deposit_pct_good'          => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $setting = LoanSetting::instance();
        $setting->update($data);

        return response()->json($setting);
    }
}
