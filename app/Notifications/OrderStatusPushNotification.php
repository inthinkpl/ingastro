<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\NotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class OrderStatusPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $statusKey
    ) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        // 1. Pobranie szablonu z bazy danych
        $setting = NotificationSetting::where('status_key', $this->statusKey)->first();

        $titleTemplate = $setting?->title_template ?? 'Ingastro';
        $bodyTemplate  = $setting?->body_template ?? 'Zmiana statusu zamówienia #{order_id}';

        // 2. Bezpieczna podmiana dynamicznych pól z modelu Order
        $replacements = [
            '{name}'     => $this->order->customer_name ?? $this->order->name ?? 'Kliencie',
            '{order_id}' => $this->order->id,
            '{address}'  => $this->order->delivery_address ?? $this->order->address ?? 'Odbiór osobisty',
            '{total}'    => number_format($this->order->total_amount ?? $this->order->total ?? 0, 2, ',', ' ') . ' zł',
        ];

        $title = str_replace(array_keys($replacements), array_values($replacements), $titleTemplate);
        $body  = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);

        $targetUrl = "/status/" . ($this->order->hash ?? $this->order->id);

        // 3. Konfiguracja struktury pakietu Web Push
        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/images/icon-192.png')
            ->data(['actionUrl' => $targetUrl])
            ->options([
                'TTL' => 3600, // Czas ważności powiadomienia (1 godzina)
            ]);
    }
}