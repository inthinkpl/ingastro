<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
    ArrowRightLeft, Package, History, AlertTriangle, 
    CheckCircle2, Building2, Store, Search 
} from 'lucide-vue-next';

const props = defineProps({
    ingredients: { type: Array, default: () => [] },
    transfers: { type: Object, default: () => ({ data: [] }) }
});

const activeTab = ref('stocks'); // 'stocks' | 'transfers'
const searchQuery = ref('');

// Filtrowanie listy surowców po nazwie
const filteredIngredients = computed(() => {
    if (!searchQuery.value) return props.ingredients;
    return props.ingredients.filter(i => 
        i.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// Formularz Przesunięcia MM
const transferForm = useForm({
    ingredient_id: '',
    quantity: '',
    notes: ''
});

const selectedIngredient = ref(null);

const onIngredientChange = () => {
    selectedIngredient.value = props.ingredients.find(i => i.id === transferForm.ingredient_id) || null;
};

const submitTransfer = () => {
    transferForm.post(route('admin.warehouse.transfer'), {
        preserveScroll: true,
        onSuccess: () => {
            transferForm.reset('quantity', 'notes');
            // Zresetuj wybrany składnik lub zaktualizuj referencję
            if (transferForm.ingredient_id) {
                onIngredientChange();
            }
        }
    });
};
</script>

<template>
    <div class="p-6 bg-[#0B0F19] text-slate-300 min-h-screen space-y-6">
        
        <!-- NAGŁÓWEK ORAZ PRZEŁĄCZNIK ZAKŁADEK -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
            <div>
                <h1 class="text-2xl font-black text-white uppercase tracking-wide flex items-center space-x-2">
                    <Package class="w-7 h-7 text-red-500" />
                    <span>Zarządzanie Magazynami</span>
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Przesunięcia surowców z Magazynu Głównego do Magazynu Lokalnego (Pizzerii)
                </p>
            </div>

            <div class="flex space-x-2 bg-slate-900 p-1.5 rounded-xl border border-slate-800">
                <button 
                    @click="activeTab = 'stocks'"
                    :class="activeTab === 'stocks' ? 'bg-red-600 text-white' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
                >
                    <Package class="w-4 h-4" />
                    <span>Stany Magazynowe</span>
                </button>
                <button 
                    @click="activeTab = 'transfers'"
                    :class="activeTab === 'transfers' ? 'bg-red-600 text-white' : 'text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
                >
                    <History class="w-4 h-4" />
                    <span>Historia Przesunięć (MM)</span>
                </button>
            </div>
        </div>

        <!-- ZAKŁADKA 1: STANY MAGAZYNOWE ORAZ FORMULARZ PRZESUNIĘCIA -->
        <div v-if="activeTab === 'stocks'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- TABELA STANÓW OBUT MAGAZYNÓW -->
            <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                        <span>Zestawienie Stanów Surowców</span>
                    </h3>

                    <!-- Wyszukiwarka -->
                    <div class="relative w-full sm:w-64">
                        <Search class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Szukaj surowca..." 
                            class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl pl-9 pr-3 py-1.5 text-xs text-white"
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="item in filteredIngredients" :key="item.id" class="hover:bg-slate-800/30 transition">
                                <td class="p-3 font-bold text-white">
                                    {{ item.name }}
                                    <span class="block text-[10px] text-slate-500 font-normal">Jednostka: {{ item.unit }}</span>
                                </td>
                                
                                <!-- Stan w Magazynie Głównym -->
                                <td class="p-3 text-right font-mono font-bold text-amber-400">
                                    {{ Number(item.stock_main).toFixed(3) }} {{ item.unit }}
                                </td>
                                
                                <td class="p-3 text-center text-slate-600">
                                    <ArrowRightLeft class="w-3.5 h-3.5 mx-auto" />
                                </td>

                                <!-- Stan w Magazynie Lokalnym -->
                                <td class="p-3 text-right font-mono font-bold" :class="Number(item.stock_local) <= Number(item.min_stock_local) ? 'text-red-400' : 'text-emerald-400'">
                                    {{ Number(item.stock_local).toFixed(3) }} {{ item.unit }}
                                    <span v-if="Number(item.stock_local) <= Number(item.min_stock_local)" class="flex items-center justify-end space-x-1 text-[9px] text-red-500 font-sans uppercase font-bold mt-0.5">
                                        <AlertTriangle class="w-3 h-3" />
                                        <span>Niski stan!</span>
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filteredIngredients.length === 0">
                                <td colspan="4" class="text-center py-8 text-slate-500 italic">Brak surowców spełniających kryteria.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORMULARZ PRZESUNIĘCIA MM (GŁÓWNY -> LOKALNY) -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl h-fit">
                <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-800 pb-3">
                    <ArrowRightLeft class="w-4 h-4 text-amber-400" />
                    <span>Przekaż do Lokalu (MM)</span>
                </h3>

                <form @submit.prevent="submitTransfer" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Select Surowiec:</label>
                        <select 
                            v-model="transferForm.ingredient_id" 
                            @change="onIngredientChange"
                            class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white cursor-pointer"
                        >
                            <option value="" disabled>-- Wybierz surowiec --</option>
                            <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                                {{ ing.name }} (Dostępne w głównym: {{ Number(ing.stock_main).toFixed(2) }} {{ ing.unit }})
                            </option>
                        </select>
                    </div>

                    <!-- Podgląd aktualnych stanów wybranego surowca -->
                    <div v-if="selectedIngredient" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 text-xs space-y-1">
                        <div class="flex justify-between text-slate-400">
                            <span>Dostępne w Głównym:</span>
                            <strong class="text-amber-400 font-mono">{{ Number(selectedIngredient.stock_main).toFixed(3) }} {{ selectedIngredient.unit }}</strong>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>Aktualnie w Lokalu:</span>
                            <strong class="text-emerald-400 font-mono">{{ Number(selectedIngredient.stock_local).toFixed(3) }} {{ selectedIngredient.unit }}</strong>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">
                            Ilość do przekazania {{ selectedIngredient ? `(${selectedIngredient.unit})` : '' }}:
                        </label>
                        <input 
                            v-model="transferForm.quantity" 
                            type="number" 
                            step="0.001" 
                            min="0.001"
                            placeholder="np. 5.500" 
                            class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white font-mono"
                        />
                        <span v-if="transferForm.errors.quantity" class="text-red-400 text-[10px] font-bold mt-1 block">
                            {{ transferForm.errors.quantity }}
                        </span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Notatka / Uwagi (Opcjonalnie):</label>
                        <input 
                            v-model="transferForm.notes" 
                            type="text" 
                            placeholder="np. Wydanie na zmianę poranną" 
                            class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white"
                        />
                    </div>

                    <button 
                        type="submit" 
                        :disabled="transferForm.processing || !transferForm.ingredient_id || !transferForm.quantity"
                        class="w-full bg-red-600 hover:bg-red-700 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider transition cursor-pointer shadow-lg flex justify-center items-center space-x-2"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ transferForm.processing ? 'Przetwarzanie...' : 'Zatwierdź Przesunięcie MM' }}</span>
                    </button>
                </form>
            </div>

        </div>

        <!-- ZAKŁADKA 2: HISTORIA PRZESUNIĘĆ MM -->
        <div v-if="activeTab === 'transfers'" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                Dokumenty Przesunięć Międzymagazynowych (MM)
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                            <th class="p-3">Data i Godzina</th>
                            <th class="p-3">Surowiec</th>
                            <th class="p-3 text-right">Przekazana Ilość</th>
                            <th class="p-3">Przekazał(a)</th>
                            <th class="p-3">Uwagi / Notatka</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="t in transfers.data" :key="t.id" class="hover:bg-slate-800/30 transition">
                            <td class="p-3 font-mono text-slate-400">
                                {{ new Date(t.created_at).toLocaleString('pl-PL') }}
                            </td>
                            <td class="p-3 font-bold text-white">
                                {{ t.ingredient?.name || 'Surowiec Usunięty' }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-400">
                                +{{ Number(t.quantity).toFixed(3) }} {{ t.unit }}
                            </td>
                            <td class="p-3 text-slate-300">
                                {{ t.user?.name || 'Administrator' }}
                            </td>
                            <td class="p-3 text-slate-500 italic">
                                {{ t.notes || '---' }}
                            </td>
                        </tr>
                        <tr v-if="transfers.data.length === 0">
                            <td colspan="5" class="text-center py-8 text-slate-500 italic">Brak historii przesunięć magazynowych.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>