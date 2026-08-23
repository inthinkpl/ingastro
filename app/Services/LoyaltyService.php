<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\LoyaltySetting;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class LoyaltyService
{
    /**
     * Normalizuje numer telefonu do jednolitego formatu (np. +48785555455).
     */
    public function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleaned) === 9) {
            return '+48' . $cleaned;
        }
        return '+' . $cleaned;
    }

    /**
     * Znajduje lub tworzy klienta na podstawie numeru telefonu.
     */
    public function getOrCreateCustomer(string $phone, ?string $name = null, ?string $email = null): Customer
    {
        $normalizedPhone = $this->normalizePhone($phone);

        return Customer::firstOrCreate(
            ['phone' => $normalizedPhone],
            [
                'name' => $name,
                'email' => $email,
                'points_balance' => 0,
                'total_spent' => 0,
                'total_orders' => 0,
            ]
        );
    }

    /**
     * Nalicza punkty po pomyślnym zrealizowaniu zamówienia.
     */
    public function addPointsForOrder(Order $order): void
    {
        $settings = LoyaltySetting::first();
        if (!$settings || !$settings->enabled || empty($order->phone)) {
            return;
        }

        DB::transaction(function () use ($order, $settings) {
            $customer = $this->getOrCreateCustomer($order->phone, $order->customer_name, $order->customer_email);
            
            // Kwota zamówienia bez kosztów dostawy
            $orderTotal = $order->total_price ?? $order->total ?? 0;
            $earnedPoints = floor($orderTotal * $settings->earn_rate);

            if ($earnedPoints <= 0) {
                return;
            }

            // Aktualizacja konta klienta
            $customer->increment('points_balance', $earnedPoints);
            $customer->increment('total_spent', $orderTotal);
            $customer->increment('total_orders');
            $customer->update(['last_order_at' => now()]);

            // Zapis historii
            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'type' => 'EARNED',
                'points' => $earnedPoints,
                'description' => "Punkty za zamówienie #{$order->id}",
            ]);
        });
    }

    /**
     * TWORZENIE KODU SMS ZABEZPIECZAJĄCEGO PRZED KRADZIEŻĄ.
     */
    public function sendVerificationCode(string $phone): bool
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $customer = Customer::where('phone', $normalizedPhone)->first();
        $settings = LoyaltySetting::first();

        if (!$customer || !$settings || !$settings->enabled) {
            throw new Exception("Brak kwalifikujących się punktów dla podanego numeru.");
        }

        if ($customer->points_balance < $settings->min_points_to_redeem) {
            throw new Exception("Wymagane minimum to {$settings->min_points_to_redeem} pkt, aby skorzystać z rabatu.");
        }

        // Generujemy 4-cyfrowy kod i zapisujemy w pamięci podręcznej na 5 minut
        $code = rand(1000, 9999);
        $cacheKey = "loyalty_otp_" . md5($normalizedPhone);
        
        Cache::put($cacheKey, [
            'code' => $code,
            'points' => $customer->points_balance,
            'attempts' => 0
        ], now()->addMinutes(5));

        // TODO: Integracja z Twoją bramką SMS (np. SMSAPI / SMSLabs)
        // \App\Services\SmsService::send($normalizedPhone, "Twój kod do odbioru rabatu w Pizzerii Savona to: {$code}");
        
        // Zapis w logach na potrzeby środowiska deweloperskiego
        \Illuminate\Support\Facades\Log::info("KOD SMS LOJALNOŚĆ [{$normalizedPhone}]: {$code}");

        return true;
    }

    /**
     * WERYFIKACJA KODU SMS I ZABLOKOWANIE PUNKTÓW DLA KOSZYKA.
     */
    public function verifyAndCalculateDiscount(string $phone, string $code): float
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $cacheKey = "loyalty_otp_" . md5($normalizedPhone);
        $cachedData = Cache::get($cacheKey);

        if (!$cachedData) {
            throw new Exception("Kod weryfikacyjny wygasł lub nie został wygenerowany.");
        }

        if ($cachedData['attempts'] >= 3) {
            Cache::forget($cacheKey);
            throw new Exception("Przekroczono limit prób. Wygeneruj nowy kod SMS.");
        }

        if ((string)$cachedData['code'] !== (string)$code) {
            $cachedData['attempts']++;
            Cache::put($cacheKey, $cachedData, now()->addMinutes(5));
            throw new Exception("Nieprawidłowy kod weryfikacyjny!");
        }

        $settings = LoyaltySetting::first();
        $customer = Customer::where('phone', $normalizedPhone)->first();

        // Wyliczamy wartość rabatu w złotówkach
        $discountAmount = $customer->points_balance * $settings->point_value;

        // Oznaczamy w sesji/cache, że ten numer ma zautoryzowany rabat
        Cache::put("loyalty_verified_" . md5($normalizedPhone), [
            'customer_id' => $customer->id,
            'points_to_spend' => $customer->points_balance,
            'discount_amount' => $discountAmount
        ], now()->addMinutes(30));

        // Czyszczenie kodu OTP po udanej weryfikacji
        Cache::forget($cacheKey);

        return $discountAmount;
    }
}