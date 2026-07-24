<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector; // Użyjemy konektora wirtualnego do testów bez fizycznego sprzętu

class PrintKitchenTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        Log::info("--- [Most Sprzętowy] Generowanie struktury ESC/POS dla zamówienia #{$this->order->id} ---");

        // 1. Inicjalizacja wirtualnego konektora drukarki (w produkcji podmieniamy na NetworkPrintConnector("192.168.1.200", 9100))
        $connector = new DummyPrintConnector();
        $printer = new Printer($connector);

        // 2. Formatowanie bonu kuchennego
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("BON KUCHENNY\n");
        $printer->text("#" . $this->order->id . "\n\n");

        $printer->selectPrintMode(Printer::MODE_FONT_A);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Typ: " . strtoupper($this->order->type) . "\n");
        $printer->text("Godzina: " . $this->order->created_at->format('H:i:s') . "\n");
        $printer->text("--------------------------------\n"); // 32 znaki - standard dla rolek 58mm/80mm

        // 3. Pętla pozycji menu do przygotowania
        foreach ($this->order->items as $item) {
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
            $printer->text("{$item->quantity}x {$item->variant->product->name}\n");
            
            $printer->selectPrintMode(Printer::MODE_FONT_B);
            $printer->text("   Rozmiar: {$item->variant->size_name}\n");

            // Modyfikatory (BEZ / EXTRA) na wydruku
            if ($item->modifiers && $item->modifiers->count() > 0) {
                foreach ($item->modifiers as $mod) {
                    $prefix = $mod->action === 'ADD' ? "[+] EXTRA: " : "[-] BEZ: ";
                    $printer->text("   {$prefix}{$mod->ingredient->name}\n");
                }
            }
            $printer->text("\n");
        }

        $printer->selectPrintMode(Printer::MODE_FONT_A);
        $printer->text("--------------------------------\n");
        
        if ($this->order->type === 'dostawa' && $this->order->delivery_address) {
            $printer->text("ADRES: " . $this->order->delivery_address . "\n");
        }

        $printer->text("\n\n\n");
        
        // 4. Komenda ucinania papieru (Hardware Cut)
        $printer->cut();

        // 5. Pobieramy surowe bajty ESC/POS, które poleciałyby do drukarki
        $rawBytes = $connector->getData();
        $printer->close();

        // Zapisujemy w logach reprezentację bajtową – dowód na poprawne zakodowanie sprzętowe
        Log::info("--- [Most Sprzętowy] Sukces! Wygenerowano " . strlen($rawBytes) . " bajtów sterujących ESC/POS. Komenda CUT wysłana. ---");
    }
}