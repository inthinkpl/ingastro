<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromotionController extends Controller
{
    /**
     * Wyświetla listę promocji w panelu menedżera.
     */
    public function index()
    {
        $promotions = Promotion::with('rewardVariant.product')->latest()->get();
        $productVariants = ProductVariant::with('product')->get();

        return Inertia::render('Manager/Promotions/Index', [
            'promotions'      => $promotions,
            'productVariants' => $productVariants,
        ]);
    }

    /**
     * Zapisuje nową promocję w bazie danych.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'type'               => 'required|in:amount_discount,percent_discount,free_product',
            'discount_target'    => 'required|in:cart_total,cheapest_item',
            'value'              => 'nullable|numeric|min:0',
            'min_order_amount'   => 'nullable|numeric|min:0',
            'min_quantity'       => 'nullable|integer|min:1',
            'required_category'  => 'nullable|string',
            'required_size_name' => 'nullable|string',
            'reward_variant_id'  => 'nullable|exists:product_variants,id',
            'is_active'          => 'boolean',
        ]);

        Promotion::create($validated);

        return redirect()->back()->with('success', 'Promocja została utworzona.');
    }

    /**
     * Przełącza status aktywności promocji (włącz/wyłącz).
     */
    public function toggle(Promotion $promotion)
    {
        $promotion->update([
            'is_active' => !$promotion->is_active
        ]);

        return redirect()->back()->with('success', 'Status promocji został zmieniony.');
    }

    /**
     * Usuwa promocję z bazy.
     */
    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return redirect()->back()->with('success', 'Promocja została usunięta.');
    }
}