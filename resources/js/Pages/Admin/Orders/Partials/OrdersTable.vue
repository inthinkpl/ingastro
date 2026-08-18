<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowUpDown, Eye, Car, Store } from 'lucide-vue-next';

const props = defineProps({
    orders: Object,
    currentSortBy: String
});

const emit = defineEmits(['toggle-sort', 'open-details']);

const getStatusBadge = (status) => {
    switch (status) {
        case 'nowe':
            return { label: 'Nowe', class: 'bg-blue-500/10 text-blue-400 border-blue-500/30' };
        case 'w_przygotowaniu':
            return { label: 'W piecu', class: 'bg-amber-500/10 text-amber-400 border-amber-500/30' };
        case 'gotowe':
            return { label: 'Gotowe', class: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' };
        case 'w_dostawie':
        case 'w drodze':
            return { label: 'W trasie', class: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30' };
        case 'dostarczone':
        case 'wydane':
        case 'zrealizowane':
            return { label: 'Zrealizowane', class: 'bg-emerald-600/20 text-emerald-400 border-emerald-500/40' };
        case 'anulowane':
            return { label: 'Anulowane', class: 'bg-red-500/10 text-red-400 border-red-500/30' };
        default:
            return { label: status, class: 'bg-slate-800 text-slate-400 border-slate-700' };
    }
};
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#0B0F19] text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th @click="$emit('toggle-sort', 'id')" class="p-4 cursor-pointer hover:text-white transition">
                            <div class="flex items-center space-x-1">
                                <span>ID</span>
                                <ArrowUpDown class="w-3 h-3 text-amber-500" />
                            </div>
                        </th>
                        <th @click="$emit('toggle-sort', 'created_at')" class="p-4 cursor-pointer hover:text-white transition">
                            <div class="flex items-center space-x-1">
                                <span>Data i Czas</span>
                                <ArrowUpDown class="w-3 h-3 text-amber-500" />
                            </div>
                        </th>
                        <th class="p-4">Typ & Adres</th>
                        <th class="p-4">Płatność</th>
                        <th class="p-4">Status Zamówienia</th>
                        <th @click="$emit('toggle-sort', 'total_price')" class="p-4 cursor-pointer hover:text-white transition">
                            <div class="flex items-center space-x-1">
                                <span>Kwota</span>
                                <ArrowUpDown class="w-3 h-3 text-amber-500" />
                            </div>
                        </th>
                        <th class="p-4 text-right">Akcje</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/60 font-medium">
                    <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-800/40 transition">
                        
                        <td class="p-4 font-mono font-bold text-white">#{{ order.id }}</td>
                        
                        <td class="p-4 text-slate-300 whitespace-nowrap">
                            {{ new Date(order.created_at).toLocaleString('pl-PL', { dateStyle: 'short', timeStyle: 'short' }) }}
                        </td>

                        <td class="p-4 space-y-0.5">
                            <div class="flex items-center space-x-1.5 font-bold text-white">
                                <Car v-if="order.type === 'dostawa'" class="w-3.5 h-3.5 text-amber-500" />
                                <Store v-else class="w-3.5 h-3.5 text-indigo-400" />
                                <span>{{ order.type === 'dostawa' ? 'Dostawa' : 'Odbiór' }}</span>
                            </div>
                            <p v-if="order.delivery_address" class="text-[11px] text-slate-400 truncate max-w-xs">
                                {{ order.delivery_address }}
                            </p>
                        </td>

                        <td class="p-4 space-y-0.5">
                            <span class="uppercase font-mono font-bold text-slate-200 block">{{ order.payment_method }}</span>
                            <span :class="order.payment_status === 'opłacone' ? 'text-emerald-400' : 'text-amber-400'" class="text-[10px] font-bold">
                                {{ order.payment_status }}
                            </span>
                        </td>

                        <td class="p-4">
                            <span :class="getStatusBadge(order.status).class" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                                {{ getStatusBadge(order.status).label }}
                            </span>
                        </td>

                        <td class="p-4 font-mono font-bold text-emerald-400 text-sm">
                            {{ Number(order.total_price || order.total || 0).toFixed(2) }} zł
                        </td>

                        <td class="p-4 text-right">
                            <button @click="$emit('open-details', order)" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-xl font-bold text-xs transition inline-flex items-center space-x-1 cursor-pointer border border-slate-700">
                                <Eye class="w-3.5 h-3.5 text-amber-500" />
                                <span>Szczegóły</span>
                            </button>
                        </td>

                    </tr>

                    <tr v-if="orders.data.length === 0">
                        <td colspan="7" class="p-8 text-center text-slate-500 italic">
                            Brak zamówień spełniających wybrane kryteria wyszukiwania.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- STRONICOWANIE -->
        <div v-if="orders.links && orders.links.length > 3" class="p-4 bg-[#0B0F19] border-t border-slate-800 flex justify-center space-x-1">
            <Link 
                v-for="(link, i) in orders.links" 
                :key="i" 
                :href="link.url || '#'" 
                v-html="link.label"
                :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                    link.active ? 'bg-red-600 text-white font-bold' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800',
                    !link.url ? 'opacity-40 cursor-not-allowed' : ''
                ]"
            />
        </div>
    </div>
</template>