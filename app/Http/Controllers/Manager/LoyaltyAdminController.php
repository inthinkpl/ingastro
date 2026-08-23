<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LoyaltyAdminController extends Controller
{
    /**
     * Lista klientów, ich punkty oraz globale ustawienia programu lojalnościowego.
     */
    public function index()
    {
        return Inertia::render('Manager/Loyalty/Index', [
            'customers' => Customer::with(['transactions' => function ($q) {
                $q->latest()->take(5);
            }])->orderBy('points_balance', 'desc')->paginate(25),
            
            'settings' => LoyaltySetting::firstOrCreate([], [
                'enabled' => true,
                'earn_rate' => 1.00,
                'point_value' => 0.10,
                'min_points_to_redeem' => 50.00,
            ])
        ]);
    }

    /**
     * Zapisuje zmienione ustawienia (przeliczniki, minima).
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'earn_rate' => 'required|numeric|min:0.01',
            'point_value' => 'required|numeric|min:0.01',
            'min_points_to_redeem' => 'required|numeric|min:0',
        ]);

        $settings = LoyaltySetting::first();
        $settings->update($validated);

        return redirect()->back()->with('success', 'Ustawienia programu lojalnościowego zostały zaktualizowane.');
    }

    /**
     * Ręczna korekta punktów klienta przez Menedżera (np. w ramach przeprosin/bonusu).
     */
    public function adjustPoints(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'points' => 'required|numeric|not_in:0',
            'description' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($customer, $validated, $request) {
            $points = (float) $validated['points'];
            $type = $points > 0 ? 'MANUAL_ADD' : 'MANUAL_SUBTRACT';

            $customer->increment('points_balance', $points);

            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'type' => $type,
                'points' => $points,
                'description' => $validated['description'],
                'created_by_user_id' => $request->user()?->id,
            ]);
        });

        return redirect()->back()->with('success', 'Korekta punktów została pomyślnie zapisana.');
    }
}