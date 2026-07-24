<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;

class DiscountCodeController extends Controller
{
    /**
     * Tworzy nowy kod rabatowy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:discount_codes,code',
            'type'             => 'required|string|in:percent,fixed',
            'value'            => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'expires_at'       => 'nullable|date',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0.00;

        DiscountCode::create($validated);

        return redirect()->back()->with('success', 'Kod rabatowy został pomyślnie utworzony.');
    }

    /**
     * Włącza lub wyłącza dany kod rabatowy.
     */
    public function toggle(DiscountCode $discountCode)
    {
        $discountCode->update([
            'is_active' => !$discountCode->is_active
        ]);

        return redirect()->back()->with('success', 'Status kodu został zmieniony.');
    }

    /**
     * Usuwa kod rabatowy z bazy danych.
     */
    public function destroy(DiscountCode $discountCode)
    {
        $discountCode->delete();

        return redirect()->back()->with('success', 'Kod rabatowy został usunięty.');
    }
}