<?php

namespace App\Services\Payment\drivers;

use App\Models\Order;
use App\Models\SystemSetting;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayUDriver implements PaymentGatewayInterface
{
    protected string $posId;
    protected string $clientId;
    protected string $clientSecret;
    protected string $secondKey;
    protected string $baseUrl;

    public function __construct()
    {
        // Pobieramy klucze konfiguracyjne z naszej tabeli ustawień systemowych
        $this->posId        = SystemSetting::get('payu_pos_id', '');
        $this->clientId     = SystemSetting::get('payu_client_id', '');
        $this->clientSecret = SystemSetting::get('payu_client_secret', '');
        $this->secondKey    = SystemSetting::get('payu_second_key', '');
        
        // Dynamiczne przełączanie między środowiskiem testowym (Sandbox) a produkcją
        $env = SystemSetting::get('payu_env', 'sandbox');
        $this->baseUrl = $env === 'production' 
            ? 'https://secure.payu.com' 
            : 'https://secure.snd.payu.com';
    }

    /**
     * Rejestruje zamówienie w API PayU i zwraca link przekierowania do płatności.
     */
    public function purchase(Order $order): string
    {
        try {
            // 1. Autoryzacja i pobranie tokena Access Token z PayU
            $accessToken = $this->getAccessToken();

            // 2. Przygotowanie danych zamówienia (PayU wymaga kwot w groszach!)
            $amountInGrosze = (int) round($order->total_price * 100);

            $payload = [
                'notifyUrl'   => route('payment.payu.webhook'), // Adres dla powiadomień PayU
                'continueUrl' => route('shop.index'),            // Gdzie klient wraca po płatności
                'customerIp'  => request()->ip() ?? '127.0.0.1',
                'merchantPosId' => $this->posId,
                'description' => "Zamówienie #{$order->id} w Pizzerii Savona",
                'currencyCode'=> 'PLN',
                'totalAmount' => $amountInGrosze,
                'extOrderId'  => (string) $order->id, // Nasz wewnętrzny ID zamówienia
                'products'    => [
                    [
                        'name'      => "Pozycje z zamówienia #{$order->id}",
                        'unitPrice' => $amountInGrosze,
                        'quantity'  => 1
                    ]
                ]
            ];

            // 3. Wysyłamy zapytanie POST tworzące zamówienie w PayU
            $response = Http::withToken($accessToken)
                ->withOptions(['allow_redirects' => false]) // Nie pozwól Laravelowi samoczynnie podążać za przekierowaniem PayU
                ->post("{$this->baseUrl}/api/v2_1/orders", $payload);

            // PayU przy sukcesie zwraca kod 302 i obiekt JSON z redirectUri
            if ($response->status() === 302 || $response->successful()) {
                $responseData = $response->json();
                
                // Zapisujemy oficjalny identyfikator transakcji PayU w naszej bazie danych
                $order->update([
                    'transaction_id' => $responseData['orderId'] ?? null
                ]);

                // Zwracamy URL bramki płatniczej (tam przekierujemy klienta)
                return $responseData['redirectUri'];
            }

            throw new \Exception("Błąd tworzenia zamówienia PayU: " . $response->body());

        } catch (\Exception $e) {
            Log::error("PayU Purchase Exception [Order #{$order->id}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Weryfikuje asynchroniczny webhook (powiadomienie) przysłany przez serwer PayU.
     */
    public function verify(Request $request): bool
    {
        // 1. Walidacja podpisu cyfrowego (X-OpenPayU-Signature) dla bezpieczeństwa ERP
        $signatureHeader = $request->header('OpenPayU-Signature');
        if (!$signatureHeader) {
            Log::warning("PayU Webhook: Brak nagłówka podpisu cyfrowego.");
            return false;
        }

        // Wyciągamy sygnaturę MD5 z nagłówka (PayU przesyła np. signature=123456...;algorithm=MD5)
        parse_str(str_replace(';', '&', $signatureHeader), $signatureArgs);
        $payuSignature = $signatureArgs['signature'] ?? '';

        // Obliczamy własną sygnaturę na podstawie surowego body i klucza SecondKey z bazy danych
        $expectedSignature = md5($request->getContent() . $this->secondKey);

        if ($payuSignature !== $expectedSignature) {
            Log::critical("ALERT: Próba sfałszowania płatności PayU! Sygnatury się nie zgadzają.");
            return false;
        }

        // 2. Przetwarzanie statusu transakcji
        $notification = $request->all();
        if (isset($notification['order'])) {
            $extOrderId = $notification['order']['extOrderId']; // Nasz ID zamówienia
            $payuStatus = $notification['order']['status'];     // Status płatności z banku

            $order = Order::find($extOrderId);
            if ($order && $payuStatus === 'COMPLETED' && $order->payment_status !== 'opłacone') {
                
                // Księgujemy wpłatę w bazie danych
                $order->update([
                    'payment_status' => 'opłacone'
                ]);

                // TRANSMISJA REAL-TIME: Budzimy monitor kucharza przez WebSocket!
                event(new \App\Events\OrderCreatedEvent($order));
                
                Log::info("PayU: Zamówienie #{$order->id} zostało pomyślnie opłacone online.");
                return true;
            }
        }

        return false;
    }

    /**
     * Pobiera tymczasowy token dostępowy OAuth2 z serwerów PayU.
     */
    protected function getAccessToken(): string
    {
        $response = Http::asForm()->post("{$this->baseUrl}/pl/standard/oauth/authorize", [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        throw new \Exception("Nie udało się pobrać tokenu autoryzacji OAuth z PayU: " . $response->body());
    }
}