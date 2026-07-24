<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    driversData: {
        type: Array,
        default: () => []
    }
});

// Stan do rozwijania szczegółowych zamówień danego kuriera
const expandedDriverId = ref(null);

const toggleDriverOrders = (id) => {
    expandedDriverId.value = expandedDriverId.value === id ? null : id;
};

// Funkcja zatwierdzająca odbiór gotówki przez managera
const settleDriver = (driverId, name, amount) => {
    if (confirm(`Czy na pewno odebrałeś od kuriera [${name}] pełną kwotę ${amount} zł i chcesz zamknąć jego zmianę finansową?`)) {
        router.post(route('manager.reconciliation.settle', driverId), {}, {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            
            <!-- Nagłówek modułu finansowego -->
            <header class="mb-8 border-b border-slate-800 pb-4">
                <div>
                    <h1 class="text-xl font-black text-emerald-400 uppercase tracking-wider">ROZLICZENIA GOTÓWKOWE KURIERÓW</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Kontroluj stan gotówki w terenie, przeglądaj dostarczone zamówienia i zamykaj raporty kasowe kierowców.</p>
                </div>
            </header>

            <!-- Komunikaty sukcesu (Flash) -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-emerald-950/40 border border-emerald-900 text-emerald-400 p-3 rounded-xl text-xs font-bold">
                {{ $page.props.flash.success }}
            </div>

            <!-- GŁÓWNA LISTA ROZLICZEŃ -->
            <div class="space-y-4">
                <div 
                    v-for="data in driversData" 
                    :key="data.driver_id" 
                    class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl transition-all"
                >
                    <!-- Główny pasek kuriera -->
                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/60">
                        <div class="flex items-center space-x-4">
                            <div class="h-11 w-11 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-xl shadow-inner">
                                🏃‍♂️
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-slate-200 uppercase tracking-wide">{{ data.driver_name }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Dostarczył gotówkowo: <span class="text-slate-300 font-bold font-mono">{{ data.orders_count }} szt.</span>
                                </p>
                            </div>
                        </div>

                        <!-- Finanse i akcje -->
                        <div class="flex items-center space-x-4 w-full sm:w-auto justify-between sm:justify-end border-t sm:border-t-0 border-slate-800/60 pt-3 sm:pt-0">
                            <div class="text-left sm:text-right">
                                <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider">Stan portfela (Gotówka):</span>
                                <span class="text-base font-mono font-black text-orange-400">{{ data.cash_to_collect.toFixed(2) }} zł</span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <!-- Podgląd bonów -->
                                <button 
                                    @click="toggleDriverOrders(data.driver_id)"
                                    class="bg-slate-950 hover:bg-slate-850 border border-slate-800 text-slate-400 hover:text-slate-200 px-3 py-2 rounded-xl text-xs font-bold transition-all"
                                >
                                    {{ expandedDriverId === data.driver_id ? '🔼 Ukryj bony' : '👁️ Zobacz bony' }}
                                </button>
                                
                                <!-- Odbiór kasy -->
                                <button 
                                    @click="settleDriver(data.driver_id, data.driver_name, data.cash_to_collect)"
                                    class="bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl shadow-md transition-all active:scale-95"
                                >
                                    💰 Rozlicz i odbierz kasę
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Rozwijana lista szczegółowych zamówień (Audyt dla managera) -->
                    <div 
                        v-if="expandedDriverId === data.driver_id" 
                        class="border-t border-slate-850 bg-slate-950/40 p-4 shadow-inner"
                    >
                        <h4 class="text-[10px] uppercase font-black text-slate-500 tracking-wider mb-3 px-1">Wykaz dostarczonych zamówień gotówkowych:</h4>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="text-slate-500 font-bold border-b border-slate-850 uppercase text-[10px]">
                                        <th class="pb-2 pl-2 w-20">ID Bonu</th>
                                        <th class="pb-2">Adres dostawy</th>
                                        <th class="pb-2">Telefon klienta</th>
                                        <th class="pb-2 text-right pr-2">Kwota</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-850/40 font-medium">
                                    <tr v-for="order in data.orders" :key="order.id" class="hover:bg-slate-900/30 text-slate-300">
                                        <td class="py-2.5 pl-2 font-mono text-slate-500">#{{ order.id }}</td>
                                        <td class="py-2.5 font-bold text-slate-200">{{ order.delivery_address }}</td>
                                        <td class="py-2.5 text-slate-400 font-mono">{{ order.customer_phone || 'Brak' }}</td>
                                        <td class="py-2.5 text-right pr-2 font-mono text-orange-400 font-bold">{{ order.total_price }} zł</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Stan pusty - wszyscy kurierzy rozliczeni idealnie -->
                <div v-if="driversData.length === 0" class="text-center py-12 border-2 border-dashed border-slate-850 rounded-2xl bg-slate-900/30">
                    <span class="text-3xl block mb-3">💼</span>
                    <h3 class="text-sm font-black text-slate-400 uppercase tracking-wider">Wszyscy kierowcy są rozliczeni</h3>
                    <p class="text-xs text-slate-600 mt-1">W terenie nie przebywa obecnie żadna nierozliczona gotówka z dostaw.</p>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>