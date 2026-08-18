<script setup>
import { Pencil, Trash2, Truck, MapPin } from 'lucide-vue-next';

defineProps({
    zones: Array
});

defineEmits(['open-edit', 'delete']);
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
            v-for="zone in zones" 
            :key="zone.id" 
            class="bg-slate-900 border border-slate-800 rounded-3xl p-5 flex flex-col justify-between relative overflow-hidden shadow-xl hover:border-slate-700 transition space-y-4"
        >
            <!-- COLOR BAR STREFY NA MAPIE -->
            <div 
                class="absolute top-0 left-0 h-1.5 w-full" 
                :style="{ backgroundColor: zone.color_code || '#f97316' }"
            ></div>

            <div class="space-y-4">
                <div class="flex justify-between items-start pt-1">
                    <div>
                        <h3 class="font-bold text-base uppercase text-white tracking-wide flex items-center space-x-2">
                            <MapPin class="w-4 h-4 text-amber-500" />
                            <span>{{ zone.name }}</span>
                        </h3>
                        <span 
                            class="text-[9px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full border mt-1 inline-block" 
                            :class="zone.is_active ? 'bg-emerald-950/60 text-emerald-400 border-emerald-900' : 'bg-red-950/60 text-red-400 border-red-900'"
                        >
                            {{ zone.is_active ? 'Aktywna' : 'Nieaktywna' }}
                        </span>
                    </div>

                    <span class="font-mono font-black text-lg text-emerald-400 bg-[#0B0F19] px-3 py-1 rounded-2xl border border-slate-800">
                        {{ Number(zone.price).toFixed(2) }} zł
                    </span>
                </div>

                <div class="space-y-2 text-xs bg-[#0B0F19] p-3.5 rounded-2xl border border-slate-800/80">
                    <div class="flex justify-between text-slate-400">
                        <span>Promień strefy:</span>
                        <span class="font-mono font-bold text-slate-200">do {{ zone.max_distance_km ? zone.max_distance_km + ' km' : '∞' }}</span>
                    </div>

                    <div class="flex justify-between text-slate-400">
                        <span>Min. wartość zamówienia:</span>
                        <span class="font-mono font-bold text-slate-200">{{ Number(zone.min_order_price || 0).toFixed(2) }} zł</span>
                    </div>

                    <div class="flex justify-between text-slate-400">
                        <span>Darmowa dostawa od:</span>
                        <span class="font-mono font-bold text-amber-400">
                            {{ zone.free_delivery_from ? Number(zone.free_delivery_from).toFixed(2) + ' zł' : 'Brak' }}
                        </span>
                    </div>

                    <div class="flex justify-between text-slate-400 pt-2 border-t border-slate-800/60 items-center">
                        <span class="flex items-center space-x-1">
                            <Truck class="w-3.5 h-3.5 text-amber-500" />
                            <span>Domyślny kurier:</span>
                        </span>
                        <span class="font-bold text-amber-500">
                            {{ zone.default_driver ? zone.default_driver.name : 'Dowolny' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- PRZYCISKI AKCJI -->
            <div class="flex space-x-2 pt-3 border-t border-slate-800/80">
                <button 
                    @click="$emit('open-edit', zone)" 
                    class="w-1/2 bg-slate-800 hover:bg-slate-700 text-slate-200 py-2 rounded-xl text-xs font-bold uppercase transition flex items-center justify-center space-x-1 cursor-pointer border border-slate-700"
                >
                    <Pencil class="w-3.5 h-3.5 text-amber-500" />
                    <span>Edytuj</span>
                </button>

                <button 
                    @click="$emit('delete', zone.id)" 
                    class="w-1/2 bg-[#0B0F19] hover:bg-red-950/60 text-red-400 hover:text-white py-2 rounded-xl text-xs font-bold uppercase transition border border-slate-800 hover:border-red-900 flex items-center justify-center space-x-1 cursor-pointer"
                >
                    <Trash2 class="w-3.5 h-3.5" />
                    <span>Usuń</span>
                </button>
            </div>
        </div>

        <div v-if="!zones || zones.length === 0" class="col-span-full text-center py-12 italic text-slate-500 bg-slate-900 border border-slate-800 rounded-3xl">
            Brak zdefiniowanych stref dostaw. Kliknij "Dodaj Nową Strefę", aby rozpocząć konfigurowanie!
        </div>
    </div>
</template>