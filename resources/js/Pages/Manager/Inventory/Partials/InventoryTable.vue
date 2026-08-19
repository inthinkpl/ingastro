<script setup>
import { ref, computed } from 'vue';
import { 
    Search, Building2, Store, PlusCircle, Edit, Trash2, 
    AlertTriangle, ArrowRightLeft 
} from 'lucide-vue-next';

const props = defineProps({
    ingredients: { type: Array, default: () => [] }
});

const emit = defineEmits(['open-restock', 'open-edit', 'delete']);

const searchQuery = ref('');

const filteredIngredients = computed(() => {
    if (!searchQuery.value) return props.ingredients;
    return props.ingredients.filter(ing => 
        ing.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                Stany Surowców w Magazynach
            </h2>

            <div class="relative w-full sm:w-64">
                <Search class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" />
                <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Szukaj surowca..." 
                    class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl pl-9 pr-3 py-1.5 text-xs text-white"
                />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                        <th class="p-3">Surowiec</th>
                        
                        <th class="p-3 text-right">
                            <span class="inline-flex items-center space-x-1 text-amber-400">
                                <Building2 class="w-3.5 h-3.5" />
                                <span>Magazyn Główny</span>
                            </span>
                        </th>

                        <th class="p-3 text-center"></th>

                        <th class="p-3 text-right">
                            <span class="inline-flex items-center space-x-1 text-emerald-400">
                                <Store class="w-3.5 h-3.5" />
                                <span>Magazyn Lokalny (Pizzeria)</span>
                            </span>
                        </th>

                        <th class="p-3 text-right">Cena / Jedn.</th>
                        <th class="p-3 text-center">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <tr v-for="item in filteredIngredients" :key="item.id" class="hover:bg-slate-800/30 transition">
                        
                        <td class="p-3 font-bold text-white">
                            {{ item.name }}
                            <span class="block text-[10px] text-slate-500 font-normal">Jednostka: {{ item.unit }}</span>
                        </td>
                        
                        <!-- Stan: Magazyn Główny -->
                        <td class="p-3 text-right font-mono font-bold text-amber-400">
                            {{ Number(item.stock_main ?? item.stock_quantity ?? item.stock ?? 0).toFixed(3) }} {{ item.unit }}
                        </td>
                        
                        <td class="p-3 text-center text-slate-600">
                            <ArrowRightLeft class="w-3.5 h-3.5 mx-auto" />
                        </td>

                        <!-- Stan: Magazyn Lokalny (Sprawdzanie stanu vs min_stock_local) -->
                        <td class="p-3 text-right font-mono font-bold" :class="Number(item.stock_local ?? 0) <= Number(item.min_stock_local ?? item.min_limit ?? 0) ? 'text-red-400' : 'text-emerald-400'">
                            {{ Number(item.stock_local ?? 0).toFixed(3) }} {{ item.unit }}
                            
                            <span 
                                v-if="Number(item.stock_local ?? 0) <= Number(item.min_stock_local ?? item.min_limit ?? 0)" 
                                class="flex items-center justify-end space-x-1 text-[9px] text-red-500 font-sans uppercase font-bold mt-0.5"
                            >
                                <AlertTriangle class="w-3 h-3" />
                                <span>Uzupełnij!</span>
                            </span>
                        </td>

                        <!-- Cena Zakupu (Poprawione mapowanie na purchase_price) -->
                        <td class="p-3 text-right font-mono text-slate-400">
                            {{ Number(item.purchase_price ?? item.price ?? item.cost ?? 0).toFixed(2) }} zł
                        </td>

                        <!-- Akcje -->
                        <td class="p-3 text-center">
                            <div class="flex items-center justify-center space-x-1.5">
                                <button 
                                    @click="emit('open-restock', item)" 
                                    title="Przyjmij dostawę zewnętrzną (do Głównego)"
                                    class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 rounded-lg transition cursor-pointer"
                                >
                                    <PlusCircle class="w-4 h-4" />
                                </button>

                                <button 
                                    @click="emit('open-edit', item)" 
                                    title="Edytuj surowiec"
                                    class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition cursor-pointer"
                                >
                                    <Edit class="w-4 h-4" />
                                </button>

                                <button 
                                    @click="emit('delete', item.id)" 
                                    title="Usuń surowiec"
                                    class="p-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition cursor-pointer"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="filteredIngredients.length === 0">
                        <td colspan="6" class="text-center py-8 text-slate-500 italic">
                            Brak surowców w magazynie.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>