<?php

namespace App\Http\Controllers;

use App\Rules\AustrianPostalCode;
use App\Services\AddressValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressValidationController extends Controller
{
    public function __construct(private AddressValidationService $addressValidation) {}

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'street' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', new AustrianPostalCode],
            'city' => ['required', 'string', 'max:255'],
        ]);

        $verified = $this->addressValidation->exists(
            $validated['street'],
            $validated['postal_code'],
            $validated['city'],
        );

        return response()->json(['verified' => $verified]);
    }
}
