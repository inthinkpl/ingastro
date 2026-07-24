<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => []
    },
    ingredients: {
        type: Array,
        default: () => []
    }
});

// KATEGORIE DO WYBORU W FORMULARZU
const categories = ['Pizza', 'Sosy', 'Napoje', 'Sałatki', 'Desery'];

// STANY OKIEN MODALNYCH
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isRecipeModalOpen = ref(false);

const selectedProduct = ref(null);
const selectedVariantId = ref(null);

// FORMULARZ DODAWANIA NOWEGO PRODUKTU
const addForm = useForm({
    name: '',
    category: 'Pizza',
    description: '',
    image: null,
    is_active: true
});

// FORMULARZ EDYCJI ISTNIEJĄCEGO PRODUKTU
const editForm = useForm({
    name: '',
    category: '',
    description: '',
    image: null,
    is_active: true
});

// 🔥 FORMULARZ RECEPTURY BOM
const recipeForm = useForm({
    ingredients: [] // Tablica obiektów: { id: X, amount_needed: Y }
});

// DYNAMICZNY PODGLĄD WARIANTÓW DLA WYBRANEGO PRODUKTU W MODALU BOM
const activeProductVariants = computed(() => {
    return selectedProduct.value?.variants || [];
});

// OBSŁUGA WYSYŁKI: DODAWANIE PRODUKTU
const submitAdd = () => {
    addForm.post(route('manager.products.store'), {
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        }
    });
};

// OTWARCIE OKNA EDYCJI I WYPEŁNIENIE DANYCH
const openEditModal = (product) => {
    selectedProduct.value = product;
    editForm.name = product.name;
    editForm.category = product.category;
    editForm.description = product.description || '';
    editForm.is_active = !!product.is_active;
    editForm.image = null; 
    isEditModalOpen.value = true;
};

// OBSŁUGA WYSYŁKI: EDYCJA PRODUKTU (Z TRICKIEM MULTIPART POST -> PUT)
const submitUpdate = () => {
    editForm.transform((data) => ({
        ...data,
        _method: 'PUT' 
    })).post(route('manager.products.update', selectedProduct.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editForm.reset();
            selectedProduct.value = null;
        }
    });
};

// OBSŁUGA USUNIĘCIA PRODUKTU
const deleteProduct = (id) => {
    if (confirm('Czy na pewno chcesz bezpowrotnie usunąć ten produkt oraz wszystkie jego rozmiary/ceny z karty dań?')) {
        router.delete(route('manager.products.destroy', id));
    }
};

// 🔥 OTWARCIE MODALU RECEPTUR BOM
const openRecipeModal = (product) => {
    selectedProduct.value = product;
    recipeForm.reset();
    
    // Jeśli produkt ma warianty, domyślnie zaznaczamy pierwszy i ładujemy jego składniki
    if (product.variants && product.variants.length > 0) {
        changeActiveVariant(product.variants[0].id);
    } else {
        selectedVariantId.value = null;
        recipeForm.ingredients = [];
    }
    
    isRecipeModalOpen.value = true;
};

// 🔥 ZMIANA ROZMIARU/WARIANTU W MODALU I ŁADOWANIE JEGO PRZEPISU Z BAZY
const changeActiveVariant = (variantId) => {
    selectedVariantId.value = variantId;
    const variant = activeProductVariants.value.find(v => v.id === variantId);
    
    if (variant && variant.ingredients) {
        // Mapujemy istniejące w bazie powiązania pod formularz Vue
        recipeForm.ingredients = variant.ingredients.map(ing => ({
            id: ing.id,
            amount_needed: ing.pivot.amount_needed
        }));
    } else {
        recipeForm.ingredients = [];
    }
};

// 🔥 DODANIE NOWEGO PUSTEGO WIERASZA SKŁADNIKA DO PRZEPISU
const addIngredientRow = () => {
    // Podpowiadamy pierwszy surowiec z listy, jeśli w ogóle jakieś istnieją
    const defaultId = props.ingredients.length > 0 ? props.ingredients[0].id : '';
    recipeForm.ingredients.push({
        id: defaultId,
        amount_needed: 0.10 // domyślne 100g / 1szt
    });
};

// 🔥 USUNIĘCIE WIERASZA Z FORMULARZA (PRZED ZAPISANIEM)
const removeIngredientRow = (index) => {
    recipeForm.ingredients.splice(index, 1);
};

// 🔥 ZAPISANIE RECEPTURY BOM NA BACKENDZIE
const submitRecipe = () => {
    if (!selectedVariantId.value) return;
    
    recipeForm.post(route('manager.products.save_recipe', selectedVariantId.value), {
        preserveScroll: true,
        onSuccess: () => {
            alert('Receptura BOM została pomyślnie zsynchronizowana z magazynem!');
            isRecipeModalOpen.value = false;
        }
    });
};

// Pomocnicza funkcja do wyciągania jednostki miary surowca (np. kg, szt)
const getIngredientUnit = (id) => {
    const ing = props.ingredients.find(i => i.id === id);
    return ing ? ing.unit : '';
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            
            <header class="mb-8 border-b border-slate-800 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl font-black text-orange-400 uppercase tracking-wider">KREATOR MENU / KARTA DAŃ</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Zarządzaj pozycjami w menu, opisami potraw oraz recepturami produkcyjnymi BOM.</p>
                </div>
                <button 
                    @click="isAddModalOpen = true"
                    class="bg-orange-600 hover:bg-orange-500 text-white font-black text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition-all shadow-md shrink-0"
                >
                    + Dodaj nowe danie
                </button>
            </header>

            <div v-if="$page.props.flash?.success" class="mb-6 bg-emerald-950/40 border border-emerald-900 text-emerald-400 p-3 rounded-xl text-xs font-bold">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950 border-b border-slate-800 text-slate-400 font-black uppercase tracking-wider">
                                <th class="p-4 w-20">Zdjęcie</th>
                                <th class="p-4">Nazwa potrawy</th>
                                <th class="p-4">Kategoria</th>
                                <th class="p-4 hidden md:table-cell">Opis / Składniki</th>
                                <th class="p-4 text-center w-24">Status</th>
                                <th class="p-4 text-center w-48">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="product in products" :key="product.id" class="hover:bg-slate-850/40 transition-colors">
                                <td class="p-4">
                                    <div class="h-12 w-12 rounded-xl overflow-hidden bg-slate-950 border border-slate-850 flex items-center justify-center shrink-0">
                                        <img 
                                            v-if="product.image_path" 
                                            :src="'/storage/' + product.image_path" 
                                            alt="Foto" 
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else class="text-lg text-slate-700">🍕</span>
                                    </div>
                                </td>
                                <td class="p-4 font-bold text-slate-200 text-sm">
                                    {{ product.name }}
                                </td>
                                <td class="p-4">
                                    <span class="bg-slate-950 px-2 py-0.5 border border-slate-800 text-slate-400 rounded-md uppercase text-[10px] font-bold">
                                        {{ product.category }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400 max-w-xs truncate hidden md:table-cell">
                                    {{ product.description || 'Brak opisu potrawy.' }}
                                </td>
                                <td class="p-4 text-center">
                                    <span 
                                        :class="product.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-red-950 text-red-400 border-red-900'"
                                        class="text-[9px] px-2 py-0.5 rounded-full border font-black uppercase tracking-wider"
                                    >
                                        {{ product.is_active ? 'Aktywny' : 'Ukryty' }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center items-center space-x-1">
                                        <!-- 🔥 PRZYCISK MODUŁU RECEPTUR BOM -->
                                        <button 
                                            @click="openRecipeModal(product)"
                                            class="bg-slate-950 hover:bg-emerald-950 border border-slate-850 hover:border-emerald-900 text-emerald-400 px-2 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                            title="Zarządzaj surowcami zużywanymi przez to danie"
                                        >BOM
                                        </button>
                                        <button 
                                            @click="openEditModal(product)"
                                            class="bg-slate-800 hover:bg-orange-600 border border-slate-750 text-slate-200 hover:text-white px-2 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                        >
                                            Edytuj
                                        </button>
                                        <button 
                                            @click="deleteProduct(product.id)"
                                            class="bg-slate-950 hover:bg-red-950 border border-slate-850 hover:border-red-900 text-red-400 px-2 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                        >
                                            Usuń
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td colspan="6" class="p-10 text-center italic text-slate-600">
                                    Brak pozycji w menu lokalu. Kliknij przycisk powyżej, by dodać pierwszą potrawę!
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL: DODAWANIE -->
            <div v-if="isAddModalOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl">
                    <h3 class="text-base font-black text-orange-400 uppercase border-b border-slate-800 pb-2 mb-4">Dodaj nową pozycję w Menu</h3>
                    
                    <form @submit.prevent="submitAdd" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa potrawy</label>
                            <input v-model="addForm.name" type="text" placeholder="np. Pizza Capricciosa" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white" required />
                            <span v-if="addForm.errors.name" class="text-red-400 block mt-1">{{ addForm.errors.name }}</span>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Kategoria</label>
                            <select v-model="addForm.category" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white" required>
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Opis dania / Receptura marketingowa</label>
                            <textarea v-model="addForm.description" rows="3" placeholder="np. Sos pomidorowy, ser mozzarella..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white"></textarea>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Zdjęcie potrawy</label>
                            <input type="file" @input="addForm.image = $event.target.files[0]" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white" accept="image/*" />
                        </div>
                        <div class="flex items-center space-x-2 py-1">
                            <input type="checkbox" v-model="addForm.is_active" id="add_active" class="rounded border-slate-800 bg-slate-950 text-orange-600 focus:ring-0" />
                            <label for="add_active" class="font-bold text-slate-300 uppercase select-none">Pozycja aktywna w menu</label>
                        </div>
                        <div class="flex space-x-3 pt-3 border-t border-slate-800">
                            <button type="button" @click="isAddModalOpen = false" class="w-1/3 bg-slate-800 py-3 rounded-xl font-bold uppercase text-slate-300">Anuluj</button>
                            <button type="submit" :disabled="addForm.processing" class="w-2/3 bg-emerald-600 hover:bg-emerald-500 font-bold text-white py-3 rounded-xl uppercase tracking-wider transition-colors">
                                Zapisz w menu
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: EDYCJA -->
            <div v-if="isEditModalOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl">
                    <h3 class="text-base font-black text-orange-400 uppercase border-b border-slate-800 pb-2 mb-4">Edytuj właściwości pozycji</h3>
                    
                    <form @submit.prevent="submitUpdate" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa potrawy</label>
                            <input v-model="editForm.name" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white" required />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Kategoria</label>
                            <select v-model="editForm.category" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white" required>
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Opis dania / Składniki</label>
                            <textarea v-model="editForm.description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white"></textarea>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Podmień zdjęcie potrawy</label>
                            <input type="file" @input="editForm.image = $event.target.files[0]" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-white" accept="image/*" />
                        </div>
                        <div class="flex items-center space-x-2 py-1">
                            <input type="checkbox" v-model="editForm.is_active" id="edit_active" class="rounded border-slate-800 bg-slate-950 text-orange-600 focus:ring-0" />
                            <label for="edit_active" class="font-bold text-slate-300 uppercase select-none">Pozycja aktywna w menu</label>
                        </div>
                        <div class="flex space-x-3 pt-3 border-t border-slate-800">
                            <button type="button" @click="isEditModalOpen = false" class="w-1/3 bg-slate-800 py-3 rounded-xl font-bold uppercase text-slate-300">Anuluj</button>
                            <button type="submit" :disabled="editForm.processing" class="w-2/3 bg-orange-600 hover:bg-orange-500 font-bold text-white py-3 rounded-xl uppercase tracking-wider transition-colors">
                                Zaktualizuj
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 🔥 KROK KRYTYCZNY: NOWY DYNAMICZNY MODAL RECEPTURY SUROWCOWEJ BOM -->
            <div v-if="isRecipeModalOpen" class="fixed inset-0 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-2xl shadow-2xl max-h-[90vh] flex flex-col justify-between">
                    <div>
                        <h3 class="text-base font-black text-emerald-400 uppercase border-b border-slate-800 pb-2 mb-4 flex justify-between items-center">
                            <span>🌾 Konfiguracja BOM: {{ selectedProduct?.name }}</span>
                            <span class="text-[10px] bg-slate-950 px-2 py-0.5 rounded border border-slate-850 text-slate-400">Gospodarka Magazynowa</span>
                        </h3>

                        <!-- WYBÓR ROZMIARU / WARIANTU DANIA -->
                        <div class="mb-6" v-if="activeProductVariants.length > 0">
                            <label class="block font-bold text-slate-400 uppercase text-[10px] tracking-wider mb-2">Wybierz wariant / rozmiar do edycji przepisu:</label>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="variant in activeProductVariants" 
                                    :key="variant.id"
                                    type="button"
                                    @click="changeActiveVariant(variant.id)"
                                    :class="selectedVariantId === variant.id ? 'bg-emerald-600 text-white font-bold border-emerald-500' : 'bg-slate-950 border-slate-850 text-slate-400 hover:bg-slate-850'"
                                    class="text-xs px-3 py-2 border rounded-xl transition-all"
                                >
                                    {{ variant.size ?? variant.name }} ({{ variant.price }} zł)
                                </button>
                            </div>
                        </div>
                        <div v-else class="mb-6 bg-amber-950/40 border border-amber-900 text-amber-400 p-3 rounded-xl text-xs font-medium">
                            ⚠️ Ta pozycja nie posiada jeszcze zdefiniowanych wariantów (rozmiarów) w bazie danych. Dodaj warianty, aby przypisać surowce.
                        </div>

                        <!-- LISTA SKŁADNIKÓW RECEPTURY -->
                        <div v-if="selectedVariantId" class="space-y-3 overflow-y-auto max-h-[45vh] pr-2">
                            <div class="flex justify-between items-center text-[10px] uppercase font-bold text-slate-500 tracking-wider px-2">
                                <span class="w-7/12">Surowiec z magazynu</span>
                                <span class="w-4/12 text-center">Ilość zużywana przy wydaniu</span>
                                <span class="w-1/12"></span>
                            </div>

                            <div v-for="(row, index) in recipeForm.ingredients" :key="index" class="flex items-center space-x-3 bg-slate-950 p-2.5 rounded-xl border border-slate-850">
                                <!-- SELEKTOR SUROWCA -->
                                <div class="w-7/12">
                                    <select v-model="row.id" class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-white focus:ring-0">
                                        <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                                            {{ ing.name }} (w magazynie)
                                        </option>
                                    </select>
                                </div>
                                <!-- GRAMATURA / WARTOŚĆ UBYTKU -->
                                <div class="w-4/12 flex items-center space-x-2 bg-slate-900 border border-slate-800 rounded-lg px-2">
                                    <input 
                                        v-model.number="row.amount_needed" 
                                        type="number" 
                                        step="0.001" 
                                        min="0.001"
                                        class="w-full bg-transparent border-none text-xs text-white focus:ring-0 p-2 text-center font-mono font-bold" 
                                        required
                                    />
                                    <span class="text-[10px] text-slate-500 font-bold uppercase pr-1 font-mono shrink-0">
                                        {{ getIngredientUnit(row.id) || 'kg' }}
                                    </span>
                                </div>
                                <!-- USUWANIE POZYCJI Z FORMULARZA -->
                                <div class="w-1/12 text-center">
                                    <button 
                                        type="button" 
                                        @click="removeIngredientRow(index)" 
                                        class="text-red-400 hover:text-red-300 font-black text-sm"
                                        title="Usuń surowiec z receptury"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <!-- BRAK SKŁADNIKÓW -->
                            <div v-if="recipeForm.ingredients.length === 0" class="text-center py-6 text-xs text-slate-500 italic border border-dashed border-slate-800 rounded-xl">
                                Receptura pusta. Kliknij poniższy przycisk, aby dodać np. mąkę, ser lub opakowanie.
                            </div>

                            <!-- PRZYCISK DODAWANIA KOLEJNEGO SKŁADNIKA -->
                            <button 
                                type="button" 
                                @click="addIngredientRow" 
                                class="w-full border border-dashed border-slate-800 hover:border-emerald-900 hover:bg-emerald-950/20 text-slate-400 hover:text-emerald-400 font-bold text-xs p-2.5 rounded-xl transition-all"
                            >
                                ➕ Dodaj kolejny składnik do przepisu
                            </button>
                        </div>
                    </div>

                    <!-- STOPKA MODALU Z AKCJAMI BIZNESOWYMI -->
                    <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-6">
                        <button type="button" @click="isRecipeModalOpen = false" class="w-1/3 bg-slate-800 py-3 rounded-xl font-bold uppercase text-xs text-slate-300">Zamknij</button>
                        <button 
                            type="button" 
                            @click="submitRecipe" 
                            :disabled="recipeForm.processing || !selectedVariantId" 
                            class="w-2/3 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-800 disabled:text-slate-600 font-bold text-white py-3 rounded-xl uppercase text-xs tracking-wider transition-colors"
                        >
                            {{ recipeForm.processing ? 'Synchronizacja...' : '🔒 Zapisz recepturę BOM w bazie' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>