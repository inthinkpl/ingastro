<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Jeśli seeding uruchamiany jest w kontekście tenanta (baza konkretnej pizzerii)
        if (tenant()) {
            $this->call(TenantDatabaseSeeder::class);
            return;
        }

        // Seeder dla bazy centralnej (Super Admin / SaaS HQ)
        $this->call([
            PlanSeeder::class,          // Zachowany dla ewentualnej wstecznej kompatybilności starych lokali
            SystemModuleSeeder::class,  // Nowy cennik i moduły A la Carte
            EmailTemplateSeeder::class, // Nowy system szablonów e-mail
        ]);
    }
}