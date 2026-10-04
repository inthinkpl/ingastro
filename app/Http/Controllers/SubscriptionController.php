<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Central\CentralSubscriptionController;

class SubscriptionController extends Controller
{
    /**
     * Obsługa tradycyjnego przekierowania GET z adresu /subscription/checkout/{planId}
     */
    public function checkout(Request $request, $planId)
    {
        $request->merge(['plan_id' => $planId]);

        $response = app(CentralSubscriptionController::class)->checkoutStripe($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            if (!empty($data['url'])) {
                return redirect()->away($data['url']);
            }
        }

        return back()->with('error', 'Nie udało się wygenerować sesji płatności Stripe.');
    }
}