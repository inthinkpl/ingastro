<script setup>
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Pizza, Plus } from 'lucide-vue-next';

// Komponenty cząstkowe
import ProductTable from './Products/Partials/ProductTable.vue';
import AddProductModal from './Products/Partials/AddProductModal.vue';
import EditProductModal from './Products/Partials/EditProductModal.vue';
import BomRecipeModal from './Products/Partials/BomRecipeModal.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] }
});

const categories = ['Pizza', 'Sosy', 'Napoje', 'Sałatki', 'Desery'];

// Stany okien modalnych
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isRecipeModalOpen = ref(false);

const selectedProduct = ref(null);

const handleOpenEditModal = (product) => {
    selectedProduct.value = product;
    isEditModalOpen.value = true;
};

const handleOpenRecipeModal = (product) => {
    selectedProduct.value = product;
    isRecipeModalOpen.value = true;
};

const handleDeleteProduct = (id) => {
    if (confirm('Czy na pewno chcesz bezpowrotnie usunąć ten produkt oraz wszystkie jego warianty z karty dań?')) {
        router.delete(route('manager.products.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Kreator Menu & BOM - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                        <Pizza class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Kreator Menu & Karta Dań</h1>
                        <p class="text-xs text-slate-400">Zarządzaj pozycjami w menu, opisami potraw oraz recepturami produkcyjnymi BOM.</p>
                    </div>
                </div>

                <button 
                    @click="isAddModalOpen = true"
                    class="bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition shadow-lg flex items-center space-x-2 cursor-pointer shrink-0"
                >
                    <Plus class="w-4 h-4" />
                    <span>Dodaj Nowe Danie</span>
                </button>
            </header>

            <!-- KOMUNIKAT FLASH -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-950/60 border border-emerald-900 text-emerald-400 p-3.5 rounded-2xl text-xs font-bold">
                {{ $page.props.flash.success }}
            </div>

            <!-- TABELA PRODUKTÓW -->
            <ProductTable 
                :products="products"
                @open-recipe="handleOpenRecipeModal"
                @open-edit="handleOpenEditModal"
                @delete="handleDeleteProduct"
            />

            <!-- MODAL: DODAWANIE PRODUKTU -->
            <AddProductModal 
                :is-open="isAddModalOpen"
                :categories="categories"
                @close="isAddModalOpen = false"
            />

            <!-- MODAL: EDYCJA PRODUKTU -->
            <EditProductModal 
                :is-open="isEditModalOpen"
                :product="selectedProduct"
                :categories="categories"
                @close="isEditModalOpen = false"
            />

            <!-- MODAL: KONFIGURATOR RECEPTUR BOM -->
            <BomRecipeModal 
                :is-open="isRecipeModalOpen"
                :product="selectedProduct"
                :ingredients="ingredients"
                @close="isRecipeModalOpen = false"
            />

        </div>
    </AuthenticatedLayout>
</template>