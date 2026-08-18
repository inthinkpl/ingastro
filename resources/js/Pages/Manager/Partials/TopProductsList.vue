<script setup>
import { Award, Pizza } from 'lucide-vue-next';

defineProps({
    topProducts: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest border-b border-slate-800 pb-3 flex items-center space-x-2">
            <Award class="w-4 h-4 text-amber-500" />
            <span>Najlepiej Sprzedające się Pozycje (TOP 5)</span>
        </h3>
        
        <div v-if="topProducts.length === 0" class="text-center py-10 text-xs text-slate-500 italic">
            Brak danych sprzedażowych w analizowanym okresie.
        </div>
        
        <div v-else class="divide-y divide-slate-800/60">
            <div 
                v-for="(prod, i) in topProducts" 
                :key="i" 
                class="py-3 flex justify-between items-center text-xs first:pt-0 last:pb-0"
            >
                <div class="flex items-center space-x-3">
                    <span class="font-mono font-bold text-xs h-7 w-7 bg-[#0B0F19] border border-slate-800 text-amber-400 rounded-xl flex items-center justify-center shrink-0">
                        #{{ i + 1 }}
                    </span>
                    <div class="flex items-center space-x-2">
                        <Pizza class="w-4 h-4 text-slate-500" />
                        <span class="font-bold text-white">{{ prod.name }}</span>
                    </div>
                </div>

                <div class="bg-[#0B0F19] px-3 py-1.5 rounded-xl border border-slate-800 text-xs font-bold font-mono">
                    Sprzedano: <span class="text-amber-400">{{ prod.qty }} szt.</span>
                </div>
            </div>
        </div>
    </div>
</template>