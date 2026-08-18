<script setup>
import { ref, computed } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, Plus, CheckCircle2 } from 'lucide-vue-next';

// Komponenty cząstkowe
import InventoryTable from './Inventory/Partials/InventoryTable.vue';
import RestockModal from './Inventory/Partials/RestockModal.vue';
import IngredientCrudModal from './Inventory/Partials/IngredientCrudModal.vue';

const props = defineProps({
    ingredients: { type: Array, default: () => [] }
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

// Kontrola stanów okien modalnych
const selectedRestockIngredient = ref(null);
const selectedEditIngredient = ref(null);

const isRestockModalOpen = ref(false);
const isCrudModalOpen = ref(false);
const isEditMode = ref(false);

// Obsługa dostawy
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
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                        <Package class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Magazyn Surowców ERP</h1>
                        <p class="text-xs text-slate-400">Kontrola stanów magazynowych, przyjęcia dostaw i minima logistyczne.</p>
                    </div>
                </div>

                <button 
                    @click="handleOpenCreate"
                    class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition shadow-lg flex items-center space-x-2 cursor-pointer shrink-0"
                >
                    <Plus class="w-4 h-4" />
                    <span>Nowy Surowiec</span>
                </button>
            </header>

            <!-- KOMUNIKAT FLASH -->
            <div v-if="successMessage" class="bg-emerald-950/60 border border-emerald-900 text-emerald-400 p-3.5 rounded-2xl text-xs font-bold flex items-center space-x-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                <span>{{ successMessage }}</span>
            </div>

            <!-- TABELA MAGAZYNOWA -->
            <InventoryTable 
                :ingredients="ingredients"
                @open-restock="handleOpenRestock"
                @open-edit="handleOpenEdit"
                @delete="handleDelete"
            />

            <!-- MODAL: PRZYJĘCIE DOSTAWY -->
            <RestockModal 
                :is-open="isRestockModalOpen"
                :ingredient="selectedRestockIngredient"
                @close="isRestockModalOpen = false"
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