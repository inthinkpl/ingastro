<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ShouldBroadcastNow wymusza natychmiastową wysyłkę przez WebSockets (Reverb)
class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    /**
     * Tworzy nową instancję zdarzenia po zmianie statusu zamówienia.
     */
    public function __construct(Order $order)
    {
        // Dociągamy relacje, by frontend (np. Live Tracker czy KDS) miał aktualny stan
        $this->order = $order->load(['items.variant.product', 'items.modifiers.ingredient']);
    }

    /**
     * Nadajemy na dwóch kanałach:
     * - 'kds' -> odświeża status na ekranie kuchni
     * - 'order.{id}' -> odświeża pasek postępu u klienta na stronie śledzenia
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('kds'),
            new Channel('order.' . $this->order->id),
        ];
    }

    /**
     * Nazwa zdarzenia odbierana przez Echo/Reverb we frontendzie.
     */
    public function broadcastAs(): string
    {
        return 'order.updated';
    }
}