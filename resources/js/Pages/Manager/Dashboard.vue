<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { TrendingUp, Activity } from 'lucide-vue-next';

// Komponenty cząstkowe
import DashboardKpiCards from './Partials/DashboardKpiCards.vue';
import OrderChannelsChart from './Partials/OrderChannelsChart.vue';
import TopProductsList from './Partials/TopProductsList.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_revenue: 0,
            total_orders: 0,
            warehouse_value: 0,
            types: { lokal: 0, wynos: 0, dostawa: 0 },
            top_products: []
        })
    }
});
</script>

<template>
    <Head title="Dashboard Menedżera - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK DASHBOARDU -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-emerald-600/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <TrendingUp class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Dashboard Menedżera: BI & Analytics</h1>
                        <p class="text-xs text-slate-400">Analityka finansowa, wydajność kanałów sprzedaży.</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2 bg-slate-900 border border-slate-800 px-3.5 py-1.5 rounded-xl text-xs font-bold">
                    <Activity class="w-4 h-4 text-emerald-400 animate-pulse" />
                    <span class="text-slate-400">Status:</span>
                    <span class="text-emerald-400 font-mono">Dane aktualne</span>
                </div>
            </header>

            <!-- KARTY STATYSTYK KPI -->
            <DashboardKpiCards :stats="stats" />

            <!-- SEKCJA SZCZEGÓŁOWA: KANAŁY I TOP PRODUKTY -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- KANAŁY REALIZACJI -->
                <OrderChannelsChart 
                    :types="stats.types || { lokal: 0, wynos: 0, dostawa: 0 }" 
                    :total-orders="stats.total_orders || 0" 
                />

                <!-- TOP 5 PRODUKTÓW -->
                <TopProductsList 
                    :top-products="stats.top_products || []" 
                />
            </div>

        </div>
    </AuthenticatedLayout>
</template>