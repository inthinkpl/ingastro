<script setup>
import { DollarSign, ShoppingBag, Package, TrendingUp } from 'lucide-vue-next';

const props = defineProps({
    stats: Object
});

const calculateAverageCart = () => {
    const orders = props.stats?.total_orders || 0;
    const revenue = props.stats?.total_revenue || 0;
    return orders > 0 ? (revenue / orders).toFixed(2) : '0.00';
};
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- UTARG CAŁKOWITY -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-3xl shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Utarg Całkowity</span>
                <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <DollarSign class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="text-2xl font-black text-emerald-400 font-mono">
                    {{ Number(stats?.total_revenue || 0).toFixed(2) }} zł
                </div>
                <span class="text-[10px] text-slate-500 mt-1 block">Suma brutto zamkniętych zamówień</span>
            </div>
        </div>

        <!-- WSZYSTKIE ZAMÓWIENIA -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-3xl shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Wszystkie Zamówienia</span>
                <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                    <ShoppingBag class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="text-2xl font-black text-amber-400 font-mono">
                    {{ stats?.total_orders || 0 }} szt.
                </div>
                <span class="text-[10px] text-slate-500 mt-1 block">Łączny ruch w systemie POS i WWW</span>
            </div>
        </div>

        <!-- WARTOŚĆ MAGAZYNU -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-3xl shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Wartość Magazynu</span>
                <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <Package class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="text-2xl font-black text-blue-400 font-mono">
                    {{ Number(stats?.warehouse_value || 0).toFixed(2) }} zł
                </div>
                <span class="text-[10px] text-slate-500 mt-1 block">Stan surowców × cena zakupu</span>
            </div>
        </div>

        <!-- ŚREDNI KOSZYK -->
        <div class="bg-slate-900 border border-slate-800 p-5 rounded-3xl shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Średni Koszyk</span>
                <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                    <TrendingUp class="w-4 h-4" />
                </div>
            </div>
            <div>
                <div class="text-2xl font-black text-purple-400 font-mono">
                    {{ calculateAverageCart() }} zł
                </div>
                <span class="text-[10px] text-slate-500 mt-1 block">Średnia wartość jednego rachunku</span>
            </div>
        </div>
    </div>
</template>