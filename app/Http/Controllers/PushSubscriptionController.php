<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $this->validate($request, [
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required'
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['p256dh'];
        $key = $request->keys['auth'];

        $user = $request->user();

        if ($user) {
            $user->updatePushSubscription($endpoint, $token, $key);
        } else {
            // Jeśli klient składasz zamówienie bez rejestracji (jako gość)
            // Możesz zapisać subskrypcję przypisaną do sesji lub konkretnego ID zamówienia
            session(['push_subscription' => [
                'endpoint' => $endpoint,
                'token' => $token,
                'key' => $key
            ]]);
        }

        return back()->with('success', 'Subskrypcja powiadomień aktywna.');
    }
}