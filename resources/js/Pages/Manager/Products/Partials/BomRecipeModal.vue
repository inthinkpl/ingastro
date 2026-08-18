<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { X, Plus, Save, Utensils, Loader2, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    product: { type: Object, default: null },
    ingredients: { type: Array, default: () => [] }
});

const emit = defineEmits(['close']);

const selectedVariantId = ref(null);
const isAddingVariant = ref(false);

// Formularz dodawania nowego wariantu
const variantForm = useForm({
    size_name: '',
    price: ''
});

// Formularz receptury BOM
const recipeForm = useForm({
    ingredients: []
});

// Aktywny wybrany wariant
const selectedVariant = computed(() => {
    if (!props.product?.variants?.length) return null;
    return props.product.variants.find(v => v.id === selectedVariantId.value) || props.product.variants[0];
});

// Reakcja na zmianę wybranego dania (reset stanu formularzy)
watch(() => props.product, (newProduct) => {
    variantForm.reset();
    variantForm.clearErrors();
    isAddingVariant.value = false;

    if (newProduct?.variants?.length) {
        if (!selectedVariantId.value || !newProduct.variants.some(v => v.id === selectedVariantId.value)) {
            selectedVariantId.value = newProduct.variants[0].id;
        }
    } else {
        selectedVariantId.value = null;
    }
}, { immediate: true, deep: true });

// Wypełnianie listy składników po zmianie aktywnego wariantu
watch(selectedVariant, (variant) => {
    if (variant && variant.ingredients) {
        recipeForm.ingredients = variant.ingredients.map(ing => ({
            id: ing.id,
            name: ing.name,
            unit: ing.unit,
            amount_needed: ing.pivot?.amount_needed || ''
        }));
    } else {
        recipeForm.ingredients = [];
    }
}, { immediate: true });

// Zamykanie modalu
const handleClose = () => {
    variantForm.reset();
    variantForm.clearErrors();
    isAddingVariant.value = false;
    emit('close');
};

// Szybkie dodawanie nowego wariantu
const handleAddVariant = () => {
    if (!props.product) return;

    variantForm.post(route('manager.products.variants.store', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            variantForm.reset();
            variantForm.clearErrors();
            isAddingVariant.value = false;
        }
    });
};

// Usuwanie wariantu rozmiarowego
const handleDeleteVariant = (variantId) => {
    if (confirm('Czy na pewno chcesz usunąć ten wariant rozmiarowy?')) {
        router.delete(route('manager.products.variants.destroy', variantId), {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedVariantId.value === variantId) {
                    selectedVariantId.value = null;
                }
            }
        });
    }
};

// Dodawanie wiersza surowca
const addIngredientRow = () => {
    recipeForm.ingredients.push({
        id: '',
        name: '',
        unit: '',
        amount_needed: ''
    });
};

const removeIngredientRow = (index) => {
    recipeForm.ingredients.splice(index, 1);
};

const handleIngredientSelect = (index, id) => {
    const found = props.ingredients.find(i => i.id === parseInt(id));
    if (found) {
        recipeForm.ingredients[index].id = found.id;
        recipeForm.ingredients[index].name = found.name;
        recipeForm.ingredients[index].unit = found.unit;
    }
};

// Zapis receptury BOM
const handleSaveRecipe = () => {
    if (!selectedVariant.value) return;

    const validIngredients = recipeForm.ingredients
        .filter(ing => ing.id && parseFloat(ing.amount_needed) > 0)
        .map(ing => ({
            id: ing.id,
            amount_needed: parseFloat(ing.amount_needed)
        }));

    recipeForm.transform(() => ({
        ingredients: validIngredients
    })).post(route('manager.products.save_recipe', selectedVariant.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            alert('Receptura BOM została pomyślnie zapisana!');
        }
    });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-2xl shadow-2xl flex flex-col max-h-[90vh]">
            
            <!-- NAGŁÓWEK MODALU -->
            <div class="border-b border-slate-800 pb-4 mb-4 flex justify-between items-start">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-xl">
                        <Utensils class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white uppercase tracking-wide">Receptura BOM — {{ product?.name }}</h3>
                        <p class="text-xs text-slate-400">Określ zużycie surowców z magazynu dla każdego wariantu dań.</p>
                    </div>
                </div>
                <button type="button" @click="handleClose" class="text-slate-500 hover:text-white transition cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- PASEK WYBORU WARIANTU + PRZYCISKI WARIANTÓW -->
            <div class="space-y-3 mb-4">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Wybierz Wariant:</span>
                    <button 
                        type="button"
                        @click="isAddingVariant = !isAddingVariant"
                        class="text-xs text-amber-400 hover:text-amber-300 font-bold flex items-center space-x-1 cursor-pointer"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        <span>{{ isAddingVariant ? 'Zamknij formularz' : 'Dodaj nowy wariant/rozmiar' }}</span>
                    </button>
                </div>

                <!-- LISTA WARIANTÓW Z PRZYCISKIEM USUWANIA ("X") -->
                <div class="flex flex-wrap gap-2">
                    <div 
                        v-for="variant in product?.variants" 
                        :key="variant.id"
                        class="flex items-center overflow-hidden rounded-xl border transition"
                        :class="selectedVariant?.id === variant.id ? 'bg-amber-500 border-amber-500 text-slate-950 font-black' : 'bg-[#0B0F19] border-slate-800 text-slate-300'"
                    >
                        <button 
                            type="button"
                            @click="selectedVariantId = variant.id"
                            class="px-3 py-1.5 text-xs uppercase transition cursor-pointer flex items-center space-x-1.5"
                        >
                            <span>{{ variant.size_name }}</span>
                            <span class="opacity-75 font-mono">({{ variant.price }} zł)</span>
                        </button>

                        <button 
                            type="button"
                            @click.stop="handleDeleteVariant(variant.id)"
                            title="Usuń wariant"
                            class="px-2 py-1.5 hover:bg-red-600 hover:text-white transition cursor-pointer border-l"
                            :class="selectedVariant?.id === variant.id ? 'border-amber-600 text-slate-900' : 'border-slate-800 text-slate-500'"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div v-if="!product?.variants?.length" class="text-xs text-amber-400 italic">
                        Brak wariantów. Dodaj pierwszy wariant poniżej.
                    </div>
                </div>

                <!-- FORMULARZ DODAWANIA NOWEGO WARIANTU -->
                <div v-if="isAddingVariant || !product?.variants?.length" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 space-y-2">
                    <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider block">+ Nowy wariant rozmiarowy</span>
                    <form @submit.prevent="handleAddVariant" class="flex gap-2">
                        <input 
                            v-model="variantForm.size_name" 
                            type="text" 
                            placeholder="Rozmiar (np. Gigant 50cm)" 
                            class="flex-1 bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-xl px-3 py-1.5 text-xs text-white"
                            required 
                        />
                        <input 
                            v-model.number="variantForm.price" 
                            type="number" 
                            step="0.01" 
                            placeholder="Cena (zł)" 
                            class="w-28 bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-xl px-3 py-1.5 text-xs text-white font-mono"
                            required 
                        />
                        <button 
                            type="submit" 
                            :disabled="variantForm.processing"
                            class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-1.5 rounded-xl text-xs uppercase cursor-pointer shrink-0 flex items-center space-x-1"
                        >
                            <Loader2 v-if="variantForm.processing" class="w-3.5 h-3.5 animate-spin" />
                            <span v-else>Zapisz wariant</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABELA SKŁADNIKÓW BOM -->
            <div v-if="selectedVariant" class="flex-1 overflow-y-auto space-y-3 pr-1">
                <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Składniki Receptury BOM dla {{ selectedVariant.size_name }}</span>
                    <button 
                        type="button"
                        @click="addIngredientRow"
                        class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-2.5 py-1 rounded-lg border border-slate-700 font-bold flex items-center space-x-1 cursor-pointer"
                    >
                        <Plus class="w-3.5 h-3.5 text-emerald-400" />
                        <span>Dodaj Składnik</span>
                    </button>
                </div>

                <div class="space-y-2">
                    <div 
                        v-for="(row, idx) in recipeForm.ingredients" 
                        :key="idx"
                        class="flex items-center gap-2 bg-[#0B0F19] p-2 rounded-xl border border-slate-800"
                    >
                        <select 
                            :value="row.id"
                            @change="e => handleIngredientSelect(idx, e.target.value)"
                            class="flex-1 bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-lg px-2.5 py-1.5 text-xs text-white uppercase"
                        >
                            <option value="">-- Wybierz surowiec --</option>
                            <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                                {{ ing.name }} ({{ ing.unit }})
                            </option>
                        </select>

                        <div class="flex items-center space-x-1 w-32">
                            <input 
                                v-model="row.amount_needed"
                                type="number" 
                                step="0.001" 
                                placeholder="Ilość" 
                                class="w-full bg-slate-900 border border-slate-800 focus:border-amber-500 rounded-lg px-2.5 py-1.5 text-xs text-white font-mono text-right"
                            />
                            <span class="text-[10px] text-slate-400 w-8 font-bold">{{ row.unit || 'jedn.' }}</span>
                        </div>

                        <button 
                            type="button"
                            @click="removeIngredientRow(idx)"
                            class="p-1.5 text-slate-500 hover:text-red-400 cursor-pointer"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>

                    <div v-if="!recipeForm.ingredients.length" class="text-center py-6 text-xs text-slate-500 italic">
                        Brak surowców w recepturze BOM dla tego wariantu. Kliknij "+ Dodaj Składnik".
                    </div>
                </div>
            </div>

            <!-- STOPKA MODALU -->
            <div class="flex justify-between items-center pt-4 border-t border-slate-800 mt-4">
                <button 
                    type="button"
                    @click="handleClose" 
                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold px-4 py-2 rounded-xl text-xs uppercase cursor-pointer"
                >
                    Zamknij
                </button>

                <button 
                    v-if="selectedVariant"
                    type="button"
                    @click="handleSaveRecipe"
                    :disabled="recipeForm.processing"
                    class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-5 py-2 rounded-xl text-xs uppercase flex items-center space-x-1.5 cursor-pointer shadow-lg disabled:opacity-50"
                >
                    <Save class="w-4 h-4" />
                    <span>Zapisz Recepturę BOM</span>
                </button>
            </div>

        </div>
    </div>
</template>