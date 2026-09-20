<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Carbon;

class CheckExpiredSubscriptions extends Command
{
    /**
     * Nazwa i sygnatura wywołania komendy w terminalu.
     * Uruchomienie: php artisan app:check-subscriptions
     */
    protected $signature = 'app:check-subscriptions';

    /**
     * Opis komendy w konsoli Artisan.
     */
    protected $description = 'Weryfikuje daty wygaśnięcia subskrypcji wszystkich tenantów i zmienia ich status na expired po upływie terminu.';

    /**
     * Główna logika wykonywania komendy.
     */
    public function handle(): int
    {
        $this->info('🔍 Rozpoczynam sprawdzanie wygasłych subskrypcji...');

        $now = Carbon::now();

        // Pobieramy aktywnych tenantów, których data wygaśnięcia minęła
        $expiredTenants = Tenant::where('subscription_status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', $now)
            ->get();

        if ($expiredTenants->isEmpty()) {
            $this->info('✅ Brak subskrypcji wymagających oznaczenia jako wygasłe.');
            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($expiredTenants as $tenant) {
            $tenant->update([
                'subscription_status' => 'expired',
            ]);

            $this->warn("⚠️ Subskrypcja dla tenanta [{$tenant->id}] wygasła dnia {$tenant->subscription_ends_at->format('Y-m-d H:i')}. Status zmieniony na 'expired'.");
            $count++;
        }

        $this->info("🎉 Zakończono! Zaktualizowano status dla {$count} lokali.");

        return Command::SUCCESS;
    }
}