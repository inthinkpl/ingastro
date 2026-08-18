<script setup>
import { ref } from 'vue';
import { Truck, Eye, EyeOff, DollarSign, Wallet } from 'lucide-vue-next';
import DriverOrdersTable from './DriverOrdersTable.vue';

defineProps({
    driversData: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['settle-driver']);

// Rozwijanie szczegółów dla danego kuriera
const expandedDriverId = ref(null);

const toggleDriverOrders = (id) => {
    expandedDriverId.value = expandedDriverId.value === id ? null : id;
};
</script>

<template>
    <div class="space-y-4">
        <div 
            v-for="data in driversData" 
            :key="data.driver_id" 
            class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl transition-all"
        >
            <!-- GŁÓWNY PASEK KURIERA -->
            <div class="p-4 sm:p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/80">
                
                <div class="flex items-center space-x-4">
                    <div class="h-12 w-12 rounded-2xl bg-[#0B0F19] border border-slate-800 flex items-center justify-center font-bold text-amber-500 font-mono text-lg shadow-inner shrink-0">
                        {{ data.driver_name?.charAt(0) }}
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wide flex items-center space-x-2">
                            <Truck class="w-4 h-4 text-emerald-400" />
                            <span>{{ data.driver_name }}</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Dostarczył gotówkowo: <span class="text-slate-200 font-bold font-mono">{{ data.orders_count }} szt.</span>
                        </p>
                    </div>
                </div>

                <!-- FINANSE I PRZYCISKI AKCJI -->
                <div class="flex items-center space-x-4 w-full sm:w-auto justify-between sm:justify-end border-t sm:border-t-0 border-slate-800/80 pt-3 sm:pt-0">
                    
                    <div class="text-left sm:text-right">
                        <span class="block text-[10px] text-slate-500 uppercase font-bold tracking-wider">Stan portfela (Gotówka)</span>
                        <span class="text-base font-mono font-black text-amber-400">{{ Number(data.cash_to_collect).toFixed(2) }} zł</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- PRZYCISK PODGLĄDU BONÓW -->
                        <button 
                            @click="toggleDriverOrders(data.driver_id)"
                            class="bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white px-3 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer"
                        >
                            <component :is="expandedDriverId === data.driver_id ? EyeOff : Eye" class="w-3.5 h-3.5 text-amber-500" />
                            <span>{{ expandedDriverId === data.driver_id ? 'Ukryj bony' : 'Zobacz bony' }}</span>
                        </button>
                        
                        <!-- PRZYCISK ODBIORU KASY -->
                        <button 
                            @click="$emit('settle-driver', { driverId: data.driver_id, name: data.driver_name, amount: data.cash_to_collect })"
                            class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs uppercase tracking-wider px-4 py-2 rounded-xl shadow-lg transition flex items-center space-x-1.5 cursor-pointer"
                        >
                            <DollarSign class="w-4 h-4" />
                            <span>Rozlicz & Odbierz Kasę</span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- ROZWIJANA TABELA SZCZEGÓŁOWYCH BONÓW -->
            <div 
                v-if="expandedDriverId === data.driver_id" 
                class="border-t border-slate-800/80 bg-[#0B0F19]/60 p-4"
            >
                <DriverOrdersTable :orders="data.orders" />
            </div>
        </div>

        <!-- STAN PUSTY - KURIERZY ROZLICZENI -->
        <div v-if="!driversData || driversData.length === 0" class="text-center py-16 border-2 border-dashed border-slate-800 rounded-3xl bg-slate-900/40 space-y-3">
            <div class="h-12 w-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto">
                <Wallet class="w-6 h-6" />
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider">Wszyscy kierowcy są rozliczeni</h3>
                <p class="text-xs text-slate-500 mt-1">W terenie nie znajduje się obecnie żadna nierozliczona gotówka z dostaw.</p>
            </div>
        </div>
    </div>
</template>