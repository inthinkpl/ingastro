<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Exception;

class LoyaltyShopController extends Controller
{
    public function __construct(protected LoyaltyService $loyaltyService) {}

    /**
     * Sprawdza, czy numer telefonu posiada punkty zakwalifikowane do rabatu.
     */
    public function checkPoints(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:9|max:20'
        ]);

        $phone = $this->loyaltyService->normalizePhone($validated['phone']);
        $customer = \App\Models\Customer::where('phone', $phone)->first();
        $settings = \App\Models\LoyaltySetting::first();

        if (!$settings || !$settings->enabled || !$customer) {
            return response()->json(['eligible' => false]);
        }

        $isEligible = $customer->points_balance >= $settings->min_points_to_redeem;

        return response()->json([
            'eligible' => $isEligible,
            'min_required' => $settings->min_points_to_redeem
        ]);
    }

    /**
     * Wysyła kod weryfikacyjny SMS.
     */
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:9|max:20'
        ]);

        try {
            $this->loyaltyService->sendVerificationCode($validated['phone']);
            return response()->json(['success' => true, 'message' => 'Kod SMS został wysłany!']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Weryfikuje wpisany kod SMS i zwraca wartość rabatu.
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:9|max:20',
            'code' => 'required|string|size:4'
        ]);

        try {
            $discountAmount = $this->loyaltyService->verifyAndCalculateDiscount($validated['phone'], $validated['code']);
            return response()->json([
                'success' => true,
                'discount_amount' => $discountAmount,
                'message' => "Kod poprawny! Przyznano rabat {$discountAmount} zł."
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}