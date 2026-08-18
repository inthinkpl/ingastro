<script setup>
import { Truck, Pencil, Trash2, AlertTriangle, CheckCircle2 } from 'lucide-vue-next';

defineProps({
    ingredients: Array
});

defineEmits(['open-restock', 'open-edit', 'delete']);
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#0B0F19] text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800">
                        <th class="p-4">Nazwa surowca</th>
                        <th class="p-4">Aktualny stan</th>
                        <th class="p-4">Minimum logistyczne</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Cena zakupu j.</th>
                        <th class="p-4 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    <tr v-for="ing in ingredients" :key="ing.id" class="hover:bg-slate-800/40 transition">
                        
                        <td class="p-4 font-bold text-white text-sm">
                            {{ ing.name }}
                        </td>

                        <td class="p-4 font-mono font-bold text-slate-200">
                            {{ parseFloat(ing.stock_quantity).toFixed(2) }} <span class="text-slate-500 font-normal text-[10px] uppercase">{{ ing.unit }}</span>
                        </td>

                        <td class="p-4 font-mono text-slate-400">
                            {{ parseFloat(ing.min_limit).toFixed(2) }} <span class="text-slate-500 font-normal text-[10px] uppercase">{{ ing.unit }}</span>
                        </td>

                        <td class="p-4">
                            <span 
                                v-if="parseFloat(ing.stock_quantity) <= parseFloat(ing.min_limit)" 
                                class="bg-red-950/80 text-red-400 border border-red-900 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wide inline-flex items-center space-x-1"
                            >
                                <AlertTriangle class="w-3 h-3 text-red-400" />
                                <span>Braki</span>
                            </span>
                            <span 
                                v-else 
                                class="bg-emerald-950/80 text-emerald-400 border border-emerald-900 text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wide inline-flex items-center space-x-1"
                            >
                                <CheckCircle2 class="w-3 h-3 text-emerald-400" />
                                <span>OK</span>
                            </span>
                        </td>

                        <td class="p-4 font-mono text-slate-300">
                            {{ Number(ing.purchase_price || 0).toFixed(2) }} zł / {{ ing.unit }}
                        </td>

                        <td class="p-4 text-right">
                            <div class="flex justify-end items-center space-x-1.5">
                                <button 
                                    @click="$emit('open-restock', ing)" 
                                    class="bg-[#0B0F19] hover:bg-slate-800 text-amber-500 border border-slate-800 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Truck class="w-3.5 h-3.5" />
                                    <span>+ Dostawa</span>
                                </button>

                                <button 
                                    @click="$emit('open-edit', ing)" 
                                    class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Pencil class="w-3.5 h-3.5 text-blue-400" />
                                    <span>Edytuj</span>
                                </button>

                                <button 
                                    @click="$emit('delete', ing.id)" 
                                    class="bg-[#0B0F19] hover:bg-red-950 text-red-400 border border-slate-800 hover:border-red-900 px-2.5 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                    <span>Usuń</span>
                                </button>
                            </div>
                        </td>

                    </tr>

                    <tr v-if="ingredients.length === 0">
                        <td colspan="6" class="p-10 text-center italic text-slate-500">
                            Brak surowców w magazynie. Kliknij "Nowy Surowiec", aby wprowadzić pozycje!
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>