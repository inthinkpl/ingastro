<script setup>
import { PieChart, Utensils, ShoppingBag, Truck } from 'lucide-vue-next';

const props = defineProps({
    types: {
        type: Object,
        default: () => ({ lokal: 0, wynos: 0, dostawa: 0 })
    },
    totalOrders: {
        type: Number,
        default: 0
    }
});

const calculatePercentage = (count) => {
    if (props.totalOrders === 0) return 0;
    return Math.round((count / props.totalOrders) * 100);
};
</script>

<template>
    <div class="lg:col-span-1 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest border-b border-slate-800 pb-3 flex items-center space-x-2">
            <PieChart class="w-4 h-4 text-amber-500" />
            <span>Kanały Realizacji</span>
        </h3>

        <div class="space-y-4 pt-1">
            <!-- W LOKALU -->
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs font-bold">
                    <span class="text-slate-300 flex items-center space-x-1.5">
                        <Utensils class="w-3.5 h-3.5 text-emerald-400" />
                        <span>W lokalu</span>
                    </span>
                    <span class="font-mono text-emerald-400">{{ types.lokal || 0 }} <span class="text-slate-500 text-[10px]">({{ calculatePercentage(types.lokal || 0) }}%)</span></span>
                </div>
                <div class="w-full bg-[#0B0F19] h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-800">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: calculatePercentage(types.lokal || 0) + '%' }"></div>
                </div>
            </div>

            <!-- NA WYNOS -->
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs font-bold">
                    <span class="text-slate-300 flex items-center space-x-1.5">
                        <ShoppingBag class="w-3.5 h-3.5 text-amber-400" />
                        <span>Na wynos</span>
                    </span>
                    <span class="font-mono text-amber-400">{{ types.wynos || 0 }} <span class="text-slate-500 text-[10px]">({{ calculatePercentage(types.wynos || 0) }}%)</span></span>
                </div>
                <div class="w-full bg-[#0B0F19] h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-800">
                    <div class="bg-amber-500 h-full rounded-full transition-all duration-500" :style="{ width: calculatePercentage(types.wynos || 0) + '%' }"></div>
                </div>
            </div>

            <!-- DOSTAWA -->
            <div class="space-y-1.5">
                <div class="flex justify-between text-xs font-bold">
                    <span class="text-slate-300 flex items-center space-x-1.5">
                        <Truck class="w-3.5 h-3.5 text-blue-400" />
                        <span>Dostawa</span>
                    </span>
                    <span class="font-mono text-blue-400">{{ types.dostawa || 0 }} <span class="text-slate-500 text-[10px]">({{ calculatePercentage(types.dostawa || 0) }}%)</span></span>
                </div>
                <div class="w-full bg-[#0B0F19] h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-800">
                    <div class="bg-blue-500 h-full rounded-full transition-all duration-500" :style="{ width: calculatePercentage(types.dostawa || 0) + '%' }"></div>
                </div>
            </div>
        </div>
    </div>
</template>