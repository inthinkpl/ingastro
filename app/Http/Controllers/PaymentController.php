<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payment\PaymentFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Przechwytuje proces po złożeniu zamówienia i kieruje klienta do aktywnej bramki.
     */
    public function process(Order $order)
    {
        try {
            $gateway = PaymentFactory::make($order->payment_method);
            $redirectUrl = $gateway->purchase($order);

            return response()->json(['redirect_url' => $redirectUrl]);
        } catch (\Exception $e) {
            Log::error('Błąd procesowania płatności: ' . $e->getMessage());
            return response()->json(['error' => 'Błąd bramki płatniczej: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Wyświetla dedykowany ekran symulatora kodu BLIK dla klienta.
     */
    public function simulationView(Order $order)
    {
        if ($order->payment_status === 'opłacone') {
            return redirect()->route('shop.index');
        }

        return Inertia::render('Shop/SimulationPayment', [
            'order' => $order
        ]);
    }

    /**
     * FINALIZACJA TRANSKCJI (Symulator - kliknięcie "Potwierdź w banku")
     */
    public function simulationConfirm(Order $order)
    {
        // 1. Uruchamiamy pancerną procedurę aktywacji zamówienia
        $this->activatePaidOrder($order);

        return redirect()->route('shop.index')->with('success', 'Płatność autoryzowana pomyślnie! Pizza trafia na kuchnię.');
    }

    /**
     * PRODUKCJA: Obsługa powiadomień błyskawicznych Webhook (PayU / Tpay IPN)
     */
    public function webhook(Request $request)
    {
        // Logujemy przyjście powiadomienia do pliku storage/logs/laravel.log w celach audytowych
        Log::info('Odebrano webhook płatności online:', $request->all());

        // 1. Wyciągamy ID zamówienia przysłane przez bramkę (dostosuj klucz zależnie od dostawcy, np. extOrderId)
        $orderId = $request->input('orderId') ?? $request->input('extOrderId');
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Zamówienie nie istnieje'], 404);
        }

        // 2. Sprawdzamy stan autoryzacji (np. PayU wysyła status 'COMPLETED')
        $status = $request->input('status');
        
        if ($status === 'COMPLETED' || $request->input('payment_status') === 'SUCCESS') {
            if ($order->payment_status !== 'opłacone') {
                $this->activatePaidOrder($order);
            }
        }

        // Bramki płatnicze wymagają czystej odpowiedzi HTTP 200 OK, by zaprzestać wysyłania powiadomień
        return response()->json(['status' => 'ok']);
    }

    /**
     * PRYWATNY SILNIK AKTYWACYJNY: Wdraża zamówienie do życia w systemie ERP
     */
    private function activatePaidOrder(Order $order): void
    {
        // Krok A: Zmiana statusu finansowego oraz WŁĄCZENIE zamówienia na kuchnię
        $order->update([
            'payment_status' => 'opłacone',
            'status'         => 'nowe' // 🔥 Zamówienie staje się oficjalnie "nowe" i legalne dla KDS
        ]);

        // Krok B: TRANSMISJA REAL-TIME - Wpychamy pizzę na monitor kucharza przez WebSockets
        if (class_exists('\App\Events\OrderPlaced')) {
            broadcast(new \App\Events\OrderPlaced($order))->toOthers();
        }

        // Krok C: ODPAWIANIE WSTRZYMANYCH ZADAŃ W TLE (Asynchroniczny Redis)
        if (class_exists('\App\Jobs\ProcessOrderJob')) {
            \App\Jobs\ProcessOrderJob::dispatch($order);
        }

        // Krok D: MOST SPRZĘTOWY - Wyrzucenie fizycznego wydruku na drukarce bonowej w kuchni
        if (class_exists('\App\Jobs\PrintKitchenTicketJob')) {
            \App\Jobs\PrintKitchenTicketJob::dispatch($order);
        }
    }
}