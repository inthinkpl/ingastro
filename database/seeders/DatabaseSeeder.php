<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Jeśli seeding uruchamiany jest w kontekście tenanta
        if (tenant()) {
            $this->call(TenantDatabaseSeeder::class);
            return;
        }

        // Seeder dla bazy centralnej (Super Admin / SaaS HQ)
        $this->call(PlanSeeder::class);
    }
}