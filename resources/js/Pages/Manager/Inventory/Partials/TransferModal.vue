<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ArrowRightLeft, X, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    ingredients: { type: Array, default: () => [] }
});

const emit = defineEmits(['close']);

const form = useForm({
    ingredient_id: '',
    quantity: '',
    notes: ''
});

const selectedIngredient = ref(null);

const onIngredientChange = () => {
    selectedIngredient.value = props.ingredients.find(i => i.id === form.ingredient_id) || null;
};

const handleClose = () => {
    form.reset();
    selectedIngredient.value = null;
    emit('close');
};

const submitTransfer = () => {
    form.post(route('manager.warehouse.transfer'), {
        preserveScroll: true,
        onSuccess: () => {
            handleClose();
        }
    });
};

watch(() => props.isOpen, (newVal) => {
    if (!newVal) form.reset();
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-5">
            
            <!-- NAGŁÓWEK -->
            <div class="flex justify-between items-start border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-xl">
                        <ArrowRightLeft class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Przesunięcie MM</h3>
                        <p class="text-[11px] text-slate-400">Przekazanie z Magazynu Głównego do Lokalu</p>
                    </div>
                </div>
                <button @click="handleClose" class="text-slate-500 hover:text-white transition cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- FORMULARZ -->
            <form @submit.prevent="submitTransfer" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Wybierz Surowiec:</label>
                    <select 
                        v-model="form.ingredient_id" 
                        @change="onIngredientChange"
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white cursor-pointer"
                    >
                        <option value="" disabled>-- Wybierz surowiec --</option>
                        <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                            {{ ing.name }} (Dostępne w głównym: {{ Number(ing.stock_main || ing.stock || 0).toFixed(2) }} {{ ing.unit }})
                        </option>
                    </select>
                </div>

                <!-- PODGLĄD STANÓW -->
                <div v-if="selectedIngredient" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 text-xs space-y-1">
                    <div class="flex justify-between text-slate-400">
                        <span>Magazyn Główny:</span>
                        <strong class="text-amber-400 font-mono">{{ Number(selectedIngredient.stock_main || selectedIngredient.stock || 0).toFixed(3) }} {{ selectedIngredient.unit }}</strong>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Magazyn Lokalny:</span>
                        <strong class="text-emerald-400 font-mono">{{ Number(selectedIngredient.stock_local || 0).toFixed(3) }} {{ selectedIngredient.unit }}</strong>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">
                        Ilość do przekazania {{ selectedIngredient ? `(${selectedIngredient.unit})` : '' }}:
                    </label>
                    <input 
                        v-model="form.quantity" 
                        type="number" 
                        step="0.001" 
                        min="0.001"
                        placeholder="np. 5.500" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white font-mono"
                    />
                    <span v-if="form.errors.quantity" class="text-red-400 text-[10px] font-bold mt-1 block">
                        {{ form.errors.quantity }}
                    </span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Notatka / Uwagi (Opcjonalnie):</label>
                    <input 
                        v-model="form.notes" 
                        type="text" 
                        placeholder="np. Wydanie na zmianę poranną" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white"
                    />
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="handleClose" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 py-2.5 rounded-xl text-xs font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button 
                        type="submit" 
                        :disabled="form.processing || !form.ingredient_id || !form.quantity"
                        class="w-2/3 bg-amber-500 hover:bg-amber-600 disabled:bg-slate-800 disabled:text-slate-600 text-slate-950 font-extrabold py-2.5 rounded-xl text-xs uppercase tracking-wider transition cursor-pointer shadow-lg flex justify-center items-center space-x-1.5"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ form.processing ? 'Wysyłanie...' : 'Zatwierdź MM' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>