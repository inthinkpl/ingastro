<script setup>
import { ref, computed } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Pizza, Plus, Filter } from 'lucide-vue-next';

// Komponenty cząstkowe
import ProductTable from './Products/Partials/ProductTable.vue';
import AddProductModal from './Products/Partials/AddProductModal.vue';
import EditProductModal from './Products/Partials/EditProductModal.vue';
import BomRecipeModal from './Products/Partials/BomRecipeModal.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    ingredients: { type: Array, default: () => [] }
});

// STAN FILTROWANIA KATEGORII
const selectedCategory = ref('Wszystko');

// Domyślne kategorie + automatyczne wykrywanie nowych z produktów
const defaultCategories = ['Pizza', 'Sałatki', 'Makarony', 'Napoje', 'Desery', 'Sosy'];

const categories = computed(() => {
    const fromProducts = props.products ? props.products.map(p => p.category).filter(Boolean) : [];
    return [...new Set([...defaultCategories, ...fromProducts])];
});

// PRZEFILTROWANA LISTA DAŃ PRZEKAZYWANA DO TABELI
const filteredProducts = computed(() => {
    if (selectedCategory.value === 'Wszystko') {
        return props.products;
    }
    return props.products.filter(p => p.category === selectedCategory.value);
});

// Stany okien modalnych
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isRecipeModalOpen = ref(false);

const selectedProduct = ref(null);

// Reaktywne powiązanie: aktualizuje warianty w otwartym modalu po przeładowaniu danych przez Inertia
const activeSelectedProduct = computed(() => {
    if (!selectedProduct.value) return null;
    return props.products.find(p => p.id === selectedProduct.value.id) || selectedProduct.value;
});

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
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 shrink-0">
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

            <!-- PASEK FILTROWANIA KATEGORII -->
            <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl flex flex-wrap items-center gap-2 shadow-xl">
                <div class="flex items-center space-x-2 text-slate-400 font-bold text-xs uppercase tracking-wider mr-2 shrink-0">
                    <Filter class="w-4 h-4 text-amber-500" />
                    <span>Kategoria:</span>
                </div>

                <!-- Przycisk "Wszystko" -->
                <button 
                    @click="selectedCategory = 'Wszystko'"
                    :class="selectedCategory === 'Wszystko' ? 'bg-amber-500 text-slate-950 font-black border-amber-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800 hover:text-white'"
                    class="px-3.5 py-1.5 rounded-xl text-xs uppercase font-bold tracking-wider border transition cursor-pointer flex items-center space-x-1.5"
                >
                    <span>Wszystko</span>
                    <span class="bg-black/20 px-1.5 py-0.5 rounded-full text-[10px]">{{ products.length }}</span>
                </button>

                <!-- Przyciski poszczególnych kategorii -->
                <button 
                    v-for="cat in categories" 
                    :key="cat"
                    @click="selectedCategory = cat"
                    :class="selectedCategory === cat ? 'bg-amber-500 text-slate-950 font-black border-amber-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800 hover:text-white'"
                    class="px-3.5 py-1.5 rounded-xl text-xs uppercase font-bold tracking-wider border transition cursor-pointer flex items-center space-x-1.5"
                >
                    <span>{{ cat }}</span>
                    <span class="bg-black/20 px-1.5 py-0.5 rounded-full text-[10px]">
                        {{ products.filter(p => p.category === cat).length }}
                    </span>
                </button>
            </div>

            <!-- TABELA PRODUKTÓW -->
            <ProductTable 
                :products="filteredProducts"
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
                :product="activeSelectedProduct"
                :categories="categories"
                @close="isEditModalOpen = false"
            />

            <!-- MODAL: KONFIGURATOR RECEPTUR BOM -->
            <BomRecipeModal 
                :is-open="isRecipeModalOpen"
                :product="activeSelectedProduct"
                :ingredients="ingredients"
                @close="isRecipeModalOpen = false"
            />

        </div>
    </AuthenticatedLayout>
</template>