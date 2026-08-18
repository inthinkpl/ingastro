<script setup>
import { ref, watch } from 'vue';
import { Sliders, X, Plus, Minus } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    product: Object,
    variant: Object,
    ingredients: Array
});

const emit = defineEmits(['close', 'add-to-cart']);

const selectedModifiers = ref([]);

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        selectedModifiers.value = [];
    }
});

const toggleModifier = (ingredient, action) => {
    const existingIdx = selectedModifiers.value.findIndex(m => m.ingredient_id === ingredient.id);

    if (existingIdx > -1) {
        if (selectedModifiers.value[existingIdx].action === action) {
            selectedModifiers.value.splice(existingIdx, 1);
            return;
        }
        selectedModifiers.value[existingIdx].action = action;
    } else {
        selectedModifiers.value.push({
            ingredient_id: ingredient.id,
            name: ingredient.name,
            action: action
        });
    }
};

const getModifierAction = (ingredientId) => {
    const found = selectedModifiers.value.find(m => m.ingredient_id === ingredientId);
    return found ? found.action : null;
};

const handleAddToCart = () => {
    emit('add-to-cart', {
        variantId: props.variant.id,
        name: props.product.name,
        size: props.variant.size_name,
        price: parseFloat(props.variant.price),
        quantity: 1,
        modifiers: [...selectedModifiers.value]
    });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl flex flex-col max-h-[90vh]">
            
            <div class="border-b border-slate-800 pb-3 mb-4 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-bold text-amber-500 uppercase flex items-center space-x-2">
                        <Sliders class="w-4 h-4" />
                        <span>Personalizacja Pozycji</span>
                    </h3>
                    <p class="text-xs text-slate-400">{{ product?.name }} — {{ variant?.size_name }}</p>
                </div>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- LISTA SUROWCÓW -->
            <div class="overflow-y-auto space-y-2 pr-1 flex-1">
                <div 
                    v-for="ing in ingredients" 
                    :key="ing.id" 
                    class="bg-[#0B0F19] p-3 rounded-2xl border border-slate-800 flex justify-between items-center text-xs"
                >
                    <span class="font-bold text-slate-200">{{ ing.name }}</span>
                    
                    <div class="flex space-x-2">
                        <button 
                            @click="toggleModifier(ing, 'REMOVE')"
                            :class="getModifierAction(ing.id) === 'REMOVE' ? 'bg-red-600 text-white border-red-500' : 'bg-slate-900 text-red-400 border-slate-800'"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl border uppercase transition flex items-center space-x-1 cursor-pointer"
                        >
                            <Minus class="w-3 h-3" />
                            <span>Bez</span>
                        </button>

                        <button 
                            @click="toggleModifier(ing, 'ADD')"
                            :class="getModifierAction(ing.id) === 'ADD' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-900 text-emerald-400 border-slate-800'"
                            class="px-3 py-1.5 text-xs font-bold rounded-xl border uppercase transition flex items-center space-x-1 cursor-pointer"
                        >
                            <Plus class="w-3 h-3" />
                            <span>Extra</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-4">
                <button type="button" @click="$emit('close')" class="w-1/3 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl text-xs font-bold uppercase cursor-pointer transition">
                    Anuluj
                </button>
                <button type="button" @click="handleAddToCart" class="w-2/3 bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider cursor-pointer shadow-lg transition">
                    Dodaj do rachunku
                </button>
            </div>

        </div>
    </div>
</template>