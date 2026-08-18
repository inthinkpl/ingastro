<script setup>
import { ref, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    product: { type: Object, default: null },
    variant: { type: Object, default: null }
});

const emit = defineEmits(['close', 'add-to-cart']);

const selectedModifiers = ref([]);

watch(() => props.isOpen, (val) => {
    if (val) selectedModifiers.value = [];
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
        selectedModifiers.value.push({ ingredient_id: ingredient.id, name: ingredient.name, action });
    }
};

const getModifierAction = (ingredientId) => {
    const found = selectedModifiers.value.find(m => m.ingredient_id === ingredientId);
    return found ? found.action : null;
};

const handleConfirm = () => {
    if (!props.product || !props.variant) return;

    emit('add-to-cart', {
        variantId: props.variant.id,
        name: props.product.name,
        size: props.variant.size_name,
        price: parseFloat(props.variant.price),
        quantity: 1,
        modifiers: [...selectedModifiers.value]
    });

    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl flex flex-col max-h-[85vh]">
            <div class="border-b border-slate-800 pb-3 mb-4 flex justify-between items-start">
                <div>
                    <h3 class="text-base font-bold text-amber-400 uppercase tracking-wide">Komponujesz własną pizzę</h3>
                    <p class="text-xs text-slate-400 font-medium">{{ product?.name }} ({{ variant?.size_name }})</p>
                </div>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white transition cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="overflow-y-auto space-y-2 pr-1 flex-1">
                <div v-for="ing in variant?.ingredients" :key="ing.id" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 flex justify-between items-center text-xs">
                    <span class="font-bold text-slate-200 uppercase tracking-wide text-[11px]">{{ ing.name }}</span>
                    <div class="flex space-x-2">
                        <button 
                            @click="toggleModifier(ing, 'REMOVE')"
                            :class="getModifierAction(ing.id) === 'REMOVE' ? 'bg-red-600 text-white border-red-500' : 'bg-slate-900 text-red-400 border-slate-800 hover:border-red-500'"
                            class="px-3 py-1.5 text-[10px] font-bold rounded-lg border uppercase transition cursor-pointer"
                        >
                            Bez
                        </button>
                        <button 
                            @click="toggleModifier(ing, 'ADD')"
                            :class="getModifierAction(ing.id) === 'ADD' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-900 text-emerald-400 border-slate-800 hover:border-emerald-500'"
                            class="px-3 py-1.5 text-[10px] font-bold rounded-lg border uppercase transition cursor-pointer"
                        >
                            + Dodaj Extra
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 pt-4 border-t border-slate-800 mt-4">
                <button @click="$emit('close')" class="w-1/3 bg-slate-800 hover:bg-slate-700 py-3 rounded-xl text-xs font-bold uppercase tracking-wider text-slate-300 transition cursor-pointer">Anuluj</button>
                <button @click="handleConfirm" class="w-2/3 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider transition shadow-md cursor-pointer">
                    Zatwierdź składniki
                </button>
            </div>
        </div>
    </div>
</template>