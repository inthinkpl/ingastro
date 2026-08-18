<script setup>
import { Calendar, Search } from 'lucide-vue-next';

const props = defineProps({
    filters: Object
});

const emit = defineEmits(['apply', 'set-preset', 'custom-date-change']);
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
        
        <!-- SZYBKIE PRESETY DATY -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800/80 pb-3">
            <div class="flex items-center space-x-2">
                <Calendar class="w-4 h-4 text-amber-500" />
                <span class="text-xs font-bold uppercase text-slate-300">Zakres Czasowy:</span>
            </div>

            <div class="flex flex-wrap gap-2">
                <button 
                    @click="$emit('set-preset', 'today')" 
                    :class="filters.preset_date === 'today' ? 'bg-red-600 text-white font-bold' : 'bg-[#0B0F19] text-slate-400 hover:text-white border-slate-800'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold uppercase border transition cursor-pointer"
                >
                    Dzisiaj
                </button>
                <button 
                    @click="$emit('set-preset', 'yesterday')" 
                    :class="filters.preset_date === 'yesterday' ? 'bg-red-600 text-white font-bold' : 'bg-[#0B0F19] text-slate-400 hover:text-white border-slate-800'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold uppercase border transition cursor-pointer"
                >
                    Wczoraj
                </button>
                <button 
                    @click="$emit('set-preset', 'this_week')" 
                    :class="filters.preset_date === 'this_week' ? 'bg-red-600 text-white font-bold' : 'bg-[#0B0F19] text-slate-400 hover:text-white border-slate-800'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold uppercase border transition cursor-pointer"
                >
                    Ten Tydzień
                </button>
                <button 
                    @click="$emit('set-preset', 'this_month')" 
                    :class="filters.preset_date === 'this_month' ? 'bg-red-600 text-white font-bold' : 'bg-[#0B0F19] text-slate-400 hover:text-white border-slate-800'"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold uppercase border transition cursor-pointer"
                >
                    Ten Miesiąc
                </button>
            </div>
        </div>

        <!-- FILTRY INPUTÓW -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="flex space-x-2">
                <input 
                    v-model="filters.date_from" 
                    @change="$emit('custom-date-change')"
                    type="date" 
                    class="w-1/2 bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 font-medium focus:border-red-500" 
                />
                <input 
                    v-model="filters.date_to" 
                    @change="$emit('custom-date-change')"
                    type="date" 
                    class="w-1/2 bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 font-medium focus:border-red-500" 
                />
            </div>

            <select 
                v-model="filters.status" 
                @change="$emit('apply')" 
                class="bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 font-medium focus:border-red-500"
            >
                <option value="all">Wszystkie Statusy</option>
                <option value="nowe">Nowe</option>
                <option value="w_przygotowaniu">W piecu</option>
                <option value="gotowe">Gotowe</option>
                <option value="w_dostawie">W trasie</option>
                <option value="dostarczone">Dostarczone / Wydane</option>
                <option value="anulowane">Anulowane</option>
            </select>

            <select 
                v-model="filters.type" 
                @change="$emit('apply')" 
                class="bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 font-medium focus:border-red-500"
            >
                <option value="all">Wszystkie Typy (Dostawa / Wynos)</option>
                <option value="dostawa">Dostawa Kurierem</option>
                <option value="wynos">Odbiór Osobisty</option>
            </select>

            <div class="relative">
                <input 
                    v-model="filters.search" 
                    @keyup.enter="$emit('apply')"
                    type="text" 
                    placeholder="Szukaj ID lub adresu..." 
                    class="w-full bg-[#0B0F19] border border-slate-800 text-xs text-white rounded-xl p-2.5 pl-9 font-medium placeholder:text-slate-600 focus:border-red-500" 
                />
                <Search class="w-4 h-4 text-slate-500 absolute left-3 top-3" />
            </div>
        </div>

    </div>
</template>