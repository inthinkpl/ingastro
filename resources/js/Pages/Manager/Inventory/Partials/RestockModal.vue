<script setup>
import { useForm } from '@inertiajs/vue3';
import { Truck, X } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    ingredient: Object
});

const emit = defineEmits(['close']);

const restockForm = useForm({
    amount: ''
});

const submitRestock = () => {
    if (!props.ingredient) return;

    restockForm.post(route('manager.restock', props.ingredient.id), {
        preserveScroll: true,
        onSuccess: () => {
            restockForm.reset();
            emit('close');
        }
    });
};
</script>

<template>
    <div v-if="isOpen && ingredient" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            
            <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center space-x-2">
                    <Truck class="w-4 h-4" />
                    <span>Dostawa: {{ ingredient.name }}</span>
                </h3>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitRestock" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-2">Ilość do dodania ({{ ingredient.unit }})</label>
                    <input 
                        v-model="restockForm.amount" 
                        type="number" 
                        step="0.01" 
                        min="0.01" 
                        placeholder="np. 10.5" 
                        required 
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-3 text-sm text-white font-mono font-bold focus:border-red-500" 
                    />
                </div>

                <div class="flex space-x-3 pt-2 border-t border-slate-800">
                    <button type="button" @click="$emit('close')" class="w-1/2 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button type="submit" :disabled="restockForm.processing" class="w-1/2 bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 text-white font-bold py-3 rounded-xl uppercase tracking-wider transition shadow-lg cursor-pointer disabled:opacity-50">
                        {{ restockForm.processing ? '...' : 'Zatwierdź' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>