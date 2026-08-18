<script setup>
import { ShoppingBag, DollarSign, Car, Store } from 'lucide-vue-next';

defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_orders: 0,
            total_revenue: 0,
            delivery_count: 0,
            takeout_count: 0
        })
    }
});
</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex items-center space-x-4 shadow-xl">
            <div class="bg-red-500/10 p-3 rounded-xl text-red-500 shrink-0 border border-red-500/20">
                <ShoppingBag class="w-5 h-5" />
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block">Zamówienia</span>
                <span class="text-xl font-black text-white font-mono">{{ stats.total_orders }}</span>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex items-center space-x-4 shadow-xl">
            <div class="bg-emerald-500/10 p-3 rounded-xl text-emerald-400 shrink-0 border border-emerald-500/20">
                <DollarSign class="w-5 h-5" />
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block">Utarg (Razem)</span>
                <span class="text-xl font-black text-emerald-400 font-mono">{{ Number(stats.total_revenue || 0).toFixed(2) }} zł</span>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex items-center space-x-4 shadow-xl">
            <div class="bg-amber-500/10 p-3 rounded-xl text-amber-500 shrink-0 border border-amber-500/20">
                <Car class="w-5 h-5" />
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block">Dostawy Kurierem</span>
                <span class="text-xl font-black text-white font-mono">{{ stats.delivery_count }}</span>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex items-center space-x-4 shadow-xl">
            <div class="bg-indigo-500/10 p-3 rounded-xl text-indigo-400 shrink-0 border border-indigo-500/20">
                <Store class="w-5 h-5" />
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase block">Odbiory w Lokalu</span>
                <span class="text-xl font-black text-white font-mono">{{ stats.takeout_count }}</span>
            </div>
        </div>
    </div>
</template>