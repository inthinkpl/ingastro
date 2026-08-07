<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Notifications\OrderStatusPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderStatusPushListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Obsługuje zdarzenie zmiany statusu zamówienia.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        // 1. Scenariusz A: Zalogowany użytkownik z aktywną subskrypcją Web Push na swoim koncie
        if ($order->user && $order->user->pushSubscriptions()->exists()) {
            $order->user->notify(new OrderStatusPushNotification($order, $order->status));
            return;
        }

        // 2. Scenariusz B: Klient niezalogowany (Gość) z subskrypcją przypisaną do zamówienia
        if ($order->pushSubscriptions()->exists()) {
            $order->notify(new OrderStatusPushNotification($order, $order->status));
        }
    }
}