<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    /**
     * Weryfikuje kod rabatowy podany przez klienta w koszyku.
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $code = strtoupper(trim($request->code));
        $subtotal = (float)$request->subtotal;

        $discount = DiscountCode::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$discount) {
            return response()->json([
                'valid' => false,
                'message' => 'Podany kod rabatowy jest nieprawidłowy lub wygasł.'
            ], 422);
        }

        // Sprawdzenie daty ważności
        if ($discount->expires_at && $discount->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'Ten kod rabatowy już wygasł.'
            ], 422);
        }

        // Sprawdzenie kwoty minimalnej koszyka
        if ($subtotal < $discount->min_order_amount) {
            return response()->json([
                'valid' => false,
                'message' => sprintf('Kod wymaga wartości zamówienia min. %.2f zł.', $discount->min_order_amount)
            ], 422);
        }

        $discountAmount = $discount->calculateDiscount($subtotal);

        return response()->json([
            'valid' => true,
            'code' => $discount->code,
            'type' => $discount->type,
            'value' => $discount->value,
            'discount_amount' => $discountAmount,
            'message' => sprintf('Kod aktywne! Naliczone potrącenie: %.2f zł', $discountAmount)
        ]);
    }
}