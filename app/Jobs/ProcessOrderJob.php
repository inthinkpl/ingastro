<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Konstruktor przyjmuje model zamówienia.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Tutaj dzieje się cała magia w tle (asynchronicznie).
     */
    public function handle(): void
    {
        // Symulujemy ciężką pracę (np. generowanie dokumentów, kontakt z API zewnętrznym)
        Log::info("--- [Kolejka Redis] Rozpoczynam asynchroniczne przetwarzanie zamówienia #{$this->order->id} ---");
        
        // Udajemy, że generujemy PDF lub wysyłamy powiadomienia push (trwa to np. 1.5 sekundy)
        usleep(1500000); 

        Log::info("--- [Kolejka Redis] Zamówienie #{$this->order->id} przetworzone pomyślnie! ---");
    }
}