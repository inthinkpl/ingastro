<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Implementacja ShouldBroadcast zmusza Laravela do wysłania tego przez WebSockets
class OrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Tworzy nową instancję zdarzenia i ładuje pełne drzewo danych (Eager Loading).
     */
    public function __construct(Order $order)
    {
        // Dociągamy relacje: pozycje, warianty, produkty oraz modyfikatory z nazwami składników
        $this->order = $order->load(['items.variant.product', 'items.modifiers.ingredient']);
    }

    /**
     * Definiuje kanał rozgłoszeniowy. Na razie użyjemy kanału publicznego 'kds'.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('kds'),
        ];
    }

    /**
     * Nazwa zdarzenia, którą odbierze frontend Vue 3.
     */
    public function broadcastAs(): string
    {
        return 'order.placed';
    }
} 