<script setup>
import { ref, computed } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, Plus, CheckCircle2, ArrowRightLeft, History } from 'lucide-vue-next';

// Komponenty cząstkowe
import InventoryTable from './Inventory/Partials/InventoryTable.vue';
import RestockModal from './Inventory/Partials/RestockModal.vue';
import IngredientCrudModal from './Inventory/Partials/IngredientCrudModal.vue';
import TransferModal from './Inventory/Partials/TransferModal.vue';

const props = defineProps({
    ingredients: { type: Array, default: () => [] },
    transfers: { type: Object, default: () => ({ data: [] }) }
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

// Zakładki (Stany Magazynowe / Historia MM)
const activeTab = ref('stocks');

// Kontrola stanów okien modalnych
const selectedRestockIngredient = ref(null);
const selectedEditIngredient = ref(null);

const isRestockModalOpen = ref(false);
const isCrudModalOpen = ref(false);
const isTransferModalOpen = ref(false);
const isEditMode = ref(false);

// Obsługa dostawy zewnętrznej (Magazyn Główny)
const handleOpenRestock = (ingredient) => {
    selectedRestockIngredient.value = ingredient;
    isRestockModalOpen.value = true;
};

// Obsługa dodawania surowca
const handleOpenCreate = () => {
    isEditMode.value = false;
    selectedEditIngredient.value = null;
    isCrudModalOpen.value = true;
};

// Obsługa edycji surowca
const handleOpenEdit = (ingredient) => {
    isEditMode.value = true;
    selectedEditIngredient.value = ingredient;
    isCrudModalOpen.value = true;
};

// Usuwanie surowca
const handleDelete = (id) => {
    if (confirm('Czy na pewno chcesz usunąć ten surowiec z magazynu ERP?')) {
        router.delete(route('manager.inventory.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Magazyn Surowców ERP - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                        <Package class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Magazyn Surowców ERP</h1>
                        <p class="text-xs text-slate-400">Gospodarka dwumagazynowa: Magazyn Główny oraz Magazyn Lokalny (Pizzeria).</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Przełącznik Zakładek Widoku -->
                    <div class="flex space-x-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
                        <button 
                            @click="activeTab = 'stocks'"
                            :class="activeTab === 'stocks' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5 cursor-pointer"
                        >
                            <Package class="w-3.5 h-3.5" />
                            <span>Stany Magazynowe</span>
                        </button>
                        <button 
                            @click="activeTab = 'transfers'"
                            :class="activeTab === 'transfers' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5 cursor-pointer"
                        >
                            <History class="w-3.5 h-3.5" />
                            <span>Historia MM</span>
                        </button>
                    </div>

                    <!-- Przycisk Przesunięcia MM -->
                    <button 
                        @click="isTransferModalOpen = true"
                        class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition shadow-lg flex items-center space-x-2 cursor-pointer shrink-0"
                    >
                        <ArrowRightLeft class="w-4 h-4" />
                        <span>Przekaż do Lokalu (MM)</span>
                    </button>

                    <!-- Przycisk Nowego Surowca -->
                    <button 
                        @click="handleOpenCreate"
                        class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition shadow-lg flex items-center space-x-2 cursor-pointer shrink-0"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Nowy Surowiec</span>
                    </button>
                </div>
            </header>

            <!-- KOMUNIKAT FLASH -->
            <div v-if="successMessage" class="bg-emerald-950/60 border border-emerald-900 text-emerald-400 p-3.5 rounded-2xl text-xs font-bold flex items-center space-x-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                <span>{{ successMessage }}</span>
            </div>

            <!-- WIDOK 1: TABELA STANY MAGAZYNOWE -->
            <div v-if="activeTab === 'stocks'">
                <InventoryTable 
                    :ingredients="ingredients"
                    @open-restock="handleOpenRestock"
                    @open-edit="handleOpenEdit"
                    @delete="handleDelete"
                />
            </div>

            <!-- WIDOK 2: HISTORIA PRZESUNIĘĆ MM -->
            <div v-if="activeTab === 'transfers'" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3">
                    Dokumenty Przesunięć Międzymagazynowych (Magazyn Główny ➔ Lokalny)
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
                            <tr v-if="!transfers.data || transfers.data.length === 0">
                                <td colspan="5" class="text-center py-8 text-slate-500 italic">Brak zarejestrowanych przesunięć magazynowych.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL: PRZYJĘCIE DOSTAWY ZEWNĘTRZNEJ -->
            <RestockModal 
                :is-open="isRestockModalOpen"
                :ingredient="selectedRestockIngredient"
                @close="isRestockModalOpen = false"
            />

            <!-- MODAL: PRZESUNIĘCIE MM (GŁÓWNY -> LOKALNY) -->
            <TransferModal 
                :is-open="isTransferModalOpen"
                :ingredients="ingredients"
                @close="isTransferModalOpen = false"
            />

            <!-- MODAL: DODAWANIE / EDYCJA SUROWCA -->
            <IngredientCrudModal 
                :is-open="isCrudModalOpen"
                :is-edit-mode="isEditMode"
                :ingredient="selectedEditIngredient"
                @close="isCrudModalOpen = false"
            />

        </div>
    </AuthenticatedLayout>
</template>