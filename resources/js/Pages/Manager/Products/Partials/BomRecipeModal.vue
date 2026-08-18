<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Layers, X, Plus, Save, AlertTriangle, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    product: Object,
    ingredients: Array
});

const emit = defineEmits(['close']);

const selectedVariantId = ref(null);

const recipeForm = useForm({
    ingredients: []
});

const activeProductVariants = computed(() => {
    return props.product?.variants || [];
});

const changeActiveVariant = (variantId) => {
    selectedVariantId.value = variantId;
    const variant = activeProductVariants.value.find(v => v.id === variantId);

    if (variant && variant.ingredients) {
        recipeForm.ingredients = variant.ingredients.map(ing => ({
            id: ing.id,
            amount_needed: ing.pivot?.amount_needed || 0.10
        }));
    } else {
        recipeForm.ingredients = [];
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal && props.product) {
        recipeForm.reset();
        if (props.product.variants && props.product.variants.length > 0) {
            changeActiveVariant(props.product.variants[0].id);
        } else {
            selectedVariantId.value = null;
            recipeForm.ingredients = [];
        }
    }
});

const addIngredientRow = () => {
    const defaultId = props.ingredients.length > 0 ? props.ingredients[0].id : '';
    recipeForm.ingredients.push({
        id: defaultId,
        amount_needed: 0.10
    });
};

const removeIngredientRow = (index) => {
    recipeForm.ingredients.splice(index, 1);
};

const submitRecipe = () => {
    if (!selectedVariantId.value) return;

    recipeForm.post(route('manager.products.save_recipe', selectedVariantId.value), {
        preserveScroll: true,
        onSuccess: () => {
            alert('Receptura BOM została pomyślnie zsynchronizowana z magazynem!');
            emit('close');
        }
    });
};

const getIngredientUnit = (id) => {
    const ing = props.ingredients.find(i => i.id === id);
    return ing ? ing.unit : 'kg';
};
</script>

<template>
    <div v-if="isOpen && product" class="fixed inset-0 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl max-h-[90vh] flex flex-col justify-between space-y-4">
            <div>
                <div class="border-b border-slate-800 pb-3 mb-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-base font-bold text-amber-500 uppercase flex items-center space-x-2">
                            <Layers class="w-4 h-4" />
                            <span>Konfiguracja BOM: {{ product.name }}</span>
                        </h3>
                        <p class="text-xs text-slate-400">Gospodarka magazynowa i zużycie surowców przy wydaniu zamówienia.</p>
                    </div>
                    <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- WYBÓR ROZMIARU / WARIANTU DANIA -->
                <div class="mb-5" v-if="activeProductVariants.length > 0">
                    <label class="block font-bold text-slate-400 uppercase text-[10px] tracking-wider mb-2">Wybierz wariant / rozmiar do edycji przepisu:</label>
                    <div class="flex flex-wrap gap-2">
                        <button 
                            v-for="variant in activeProductVariants" 
                            :key="variant.id"
                            type="button"
                            @click="changeActiveVariant(variant.id)"
                            :class="selectedVariantId === variant.id ? 'bg-emerald-600 text-white font-bold border-emerald-500 shadow-md' : 'bg-[#0B0F19] border-slate-800 text-slate-400 hover:text-white'"
                            class="text-xs px-3.5 py-2 border rounded-xl transition cursor-pointer"
                        >
                            {{ variant.size_name || variant.size || variant.name }} ({{ variant.price }} zł)
                        </button>
                    </div>
                </div>

                <div v-else class="mb-5 bg-amber-950/40 border border-amber-900/60 text-amber-400 p-3 rounded-xl text-xs font-medium flex items-center space-x-2">
                    <AlertTriangle class="w-4 h-4 shrink-0" />
                    <span>Ta pozycja nie posiada jeszcze zdefiniowanych wariantów (rozmiarów) w bazie danych.</span>
                </div>

                <!-- LISTA SKŁADNIKÓW RECEPTURY -->
                <div v-if="selectedVariantId" class="space-y-3 overflow-y-auto max-h-[42vh] pr-2">
                    <div class="flex justify-between items-center text-[10px] uppercase font-bold text-slate-500 tracking-wider px-2">
                        <span class="w-7/12">Surowiec z magazynu</span>
                        <span class="w-4/12 text-center">Ilość zużywana przy wydaniu</span>
                        <span class="w-1/12"></span>
                    </div>

                    <div v-for="(row, index) in recipeForm.ingredients" :key="index" class="flex items-center space-x-3 bg-[#0B0F19] p-2.5 rounded-2xl border border-slate-800">
                        <div class="w-7/12">
                            <select v-model="row.id" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2 text-xs text-white focus:border-red-500">
                                <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                                    {{ ing.name }} (w magazynie)
                                </option>
                            </select>
                        </div>

                        <div class="w-4/12 flex items-center space-x-2 bg-slate-900 border border-slate-800 rounded-xl px-2">
                            <input 
                                v-model.number="row.amount_needed" 
                                type="number" 
                                step="0.001" 
                                min="0.001"
                                class="w-full bg-transparent border-none text-xs text-white focus:ring-0 p-2 text-center font-mono font-bold" 
                                required
                            />
                            <span class="text-[10px] text-slate-500 font-bold uppercase pr-1 font-mono shrink-0">
                                {{ getIngredientUnit(row.id) }}
                            </span>
                        </div>

                        <div class="w-1/12 text-center">
                            <button 
                                type="button" 
                                @click="removeIngredientRow(index)" 
                                class="text-slate-500 hover:text-red-400 p-1 transition cursor-pointer"
                                title="Usuń surowiec z receptury"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="recipeForm.ingredients.length === 0" class="text-center py-8 text-xs text-slate-500 italic border border-dashed border-slate-800 rounded-2xl">
                        Receptura pusta. Dodaj składniki takie jak mąka, sos pomidorowy, ser czy serwatka.
                    </div>

                    <button 
                        type="button" 
                        @click="addIngredientRow" 
                        class="w-full border border-dashed border-slate-800 hover:border-emerald-900 hover:bg-emerald-950/20 text-slate-400 hover:text-emerald-400 font-bold text-xs p-3 rounded-2xl transition flex items-center justify-center space-x-2 cursor-pointer"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Dodaj kolejny składnik do przepisu</span>
                    </button>
                </div>
            </div>

            <!-- STOPKA MODALU -->
            <div class="flex space-x-3 pt-4 border-t border-slate-800">
                <button type="button" @click="$emit('close')" class="w-1/3 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase text-xs transition cursor-pointer">
                    Zamknij
                </button>
                <button 
                    type="button" 
                    @click="submitRecipe" 
                    :disabled="recipeForm.processing || !selectedVariantId" 
                    class="w-2/3 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 font-bold text-white py-3 rounded-xl uppercase text-xs tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer"
                >
                    <Save class="w-4 h-4" />
                    <span>{{ recipeForm.processing ? 'Synchronizacja...' : 'Zapisz recepturę BOM' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>