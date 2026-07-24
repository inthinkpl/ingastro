<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
const props = defineProps({
    stats: Object
});

</script>

<template>
    <AuthenticatedLayout>
    <div class="min-h-screen bg-slate-950 text-white p-6">
        <header class="mb-8 border-b border-slate-800 pb-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">📈</span>
                <h1 class="text-2xl font-black tracking-wider text-emerald-400">DASHBOARD MENEDŻERA: BI & ANALYTICS</h1>
            </div>
            <span class="text-xs bg-slate-900 border border-slate-800 px-3 py-1 rounded text-slate-400">Status: Dane aktualne</span>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-xl flex flex-col justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Utarg całkowity</span>
                <div class="text-3xl font-black text-emerald-400 mt-2 font-mono">{{ stats.total_revenue }} zł</div>
                <span class="text-[10px] text-slate-400 mt-2">Suma brutto zamkniętych zamówień</span>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-xl flex flex-col justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Wszystkie zamówienia</span>
                <div class="text-3xl font-black text-orange-400 mt-2 font-mono">{{ stats.total_orders }} szt.</div>
                <span class="text-[10px] text-slate-400 mt-2">Łączny ruch w systemie POS i WWW</span>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-xl flex flex-col justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Wartość magazynu</span>
                <div class="text-3xl font-black text-blue-400 mt-2 font-mono">{{ stats.warehouse_value }} zł</div>
                <span class="text-[10px] text-slate-400 mt-2">Aktualny stan surowców × cena zakupu</span>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-xl flex flex-col justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Średni koszyk</span>
                <div class="text-3xl font-black text-purple-400 mt-2 font-mono">
                    {{ stats.total_orders > 0 ? (stats.total_revenue / stats.total_orders).toFixed(2) : '0.00' }} zł
                </div>
                <span class="text-[10px] text-slate-400 mt-2">Średnia wartość jednego rachunku</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">Kanały realizacji</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-xs mb-1 font-semibold">
                            <span class="text-slate-300">W lokalu</span>
                            <span class="font-mono">{{ stats.types.lokal }}</span>
                        </div>
                        <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full" :style="{ width: (stats.total_orders > 0 ? (stats.types.lokal / stats.total_orders) * 100 : 0) + '%' }"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1 font-semibold">
                            <span class="text-slate-300">Na wynos</span>
                            <span class="font-mono">{{ stats.types.wynos }}</span>
                        </div>
                        <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-orange-500 h-full" :style="{ width: (stats.total_orders > 0 ? (stats.types.wynos / stats.total_orders) * 100 : 0) + '%' }"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1 font-semibold">
                            <span class="text-slate-300">Dostawa</span>
                            <span class="font-mono">{{ stats.types.dostawa }}</span>
                        </div>
                        <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-500 h-full" :style="{ width: (stats.total_orders > 0 ? (stats.types.dostawa / stats.total_orders) * 100 : 0) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">Najlepiej sprzedające się pozycje (TOP 5)</h3>
                
                <div v-if="stats.top_products.length === 0" class="text-center py-8 text-xs text-slate-500 italic">
                    Brak danych sprzedażowych. Złóż pierwsze zamówienie!
                </div>
                
                <div v-else class="divide-y divide-slate-850">
                    <div v-for="(prod, i) in stats.top_products" :key="i" class="py-3 flex justify-between items-center text-sm first:pt-0 last:pb-0">
                        <div class="flex items-center space-x-3">
                            <span class="font-black text-xs h-5 w-5 bg-slate-800 border border-slate-700 text-orange-400 rounded-md flex items-center justify-center font-mono">
                                #{{ i + 1 }}
                            </span>
                            <span class="font-bold text-slate-200">{{ prod.name }}</span>
                        </div>
                        <div class="bg-slate-950 px-3 py-1 rounded-lg border border-slate-850 text-xs font-bold font-mono">
                            Sprzedano: <span class="text-orange-400">{{ prod.qty }} szt.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </AuthenticatedLayout>
</template>