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
            'start_date' => ['required', 'date'],
            'interval_days' => ['required', 'integer', 'min:1'],
            'grace_days' => ['required', 'integer', 'min:0'],
            // Booking for a personal pickup event closes this many hours before it starts
            'pickup_event_lead_hours' => ['sometimes', 'integer', 'min:0', 'max:336'],
            'loan_duration_weeks' => ['required', 'integer', 'min:1'],
            'max_extensions' => ['required', 'integer', 'min:0'],
            'loan_cost_small' => ['required', 'integer', 'min:0'],
            'loan_cost_medium' => ['required', 'integer', 'min:0'],
            'loan_cost_large' => ['required', 'integer', 'min:0'],
            'package_loan_cost' => ['required', 'integer', 'min:0'],
            'extension_cost' => ['required', 'integer', 'min:0'],
            'reservation_cost' => ['required', 'integer', 'min:0'],
            'condition_very_good_after' => ['required', 'integer', 'min:1'],
            'condition_good_after' => ['required', 'integer', 'min:1'],
            'deposit_pct_very_good' => ['required', 'integer', 'min:0', 'max:100'],
            'deposit_pct_good' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $setting = LoanSetting::instance();
        $setting->update($data);

        return response()->json($setting);
    }
}
