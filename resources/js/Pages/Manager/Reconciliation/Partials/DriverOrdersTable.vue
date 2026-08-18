<script setup>
import { MapPin, Phone, Receipt } from 'lucide-vue-next';

defineProps({
    orders: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <div class="space-y-2">
        <h4 class="text-[10px] uppercase font-bold text-slate-400 tracking-wider flex items-center space-x-1">
            <Receipt class="w-3.5 h-3.5 text-amber-500" />
            <span>Wykaz dostarczonych zamówień gotówkowych</span>
        </h4>
        
        <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 overflow-hidden">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#0B0F19] text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800 text-[10px]">
                        <th class="p-3 w-24">ID Bonu</th>
                        <th class="p-3">Adres dostawy</th>
                        <th class="p-3">Telefon klienta</th>
                        <th class="p-3 text-right">Kwota pobrana</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    <tr v-for="order in orders" :key="order.id" class="hover:bg-slate-800/40 text-slate-300 transition">
                        
                        <td class="p-3 font-mono font-bold text-slate-400">
                            #{{ order.id }}
                        </td>

                        <td class="p-3 font-bold text-white">
                            <span class="flex items-center space-x-1.5">
                                <MapPin class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                <span>{{ order.delivery_address }}</span>
                            </span>
                        </td>

                        <td class="p-3 text-slate-400 font-mono">
                            <span class="flex items-center space-x-1.5">
                                <Phone class="w-3.5 h-3.5 text-slate-500 shrink-0" />
                                <span>{{ order.customer_phone || 'Brak telefonu' }}</span>
                            </span>
                        </td>

                        <td class="p-3 text-right font-mono text-amber-400 font-bold">
                            {{ Number(order.total_price || order.total || 0).toFixed(2) }} zł
                        </td>

                    </tr>

                    <tr v-if="!orders || orders.length === 0">
                        <td colspan="4" class="p-6 text-center italic text-slate-500">
                            Brak szczegółowych informacji o bonach dla tego kierowcy.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>