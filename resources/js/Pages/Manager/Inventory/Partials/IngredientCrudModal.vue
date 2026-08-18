<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Package, X, Save } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    isEditMode: Boolean,
    ingredient: Object
});

const emit = defineEmits(['close']);

const crudForm = useForm({
    name: '',
    stock_quantity: 0,
    min_limit: 0,
    unit: 'kg',
    purchase_price: 0
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.isEditMode && props.ingredient) {
            crudForm.name = props.ingredient.name;
            crudForm.stock_quantity = props.ingredient.stock_quantity;
            crudForm.min_limit = props.ingredient.min_limit;
            crudForm.unit = props.ingredient.unit;
            crudForm.purchase_price = props.ingredient.purchase_price;
        } else {
            crudForm.reset();
        }
    }
});

const submitCrudForm = () => {
    if (props.isEditMode && props.ingredient) {
        crudForm.put(route('manager.inventory.update', props.ingredient.id), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    } else {
        crudForm.post(route('manager.inventory.store'), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            
            <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center space-x-2">
                    <Package class="w-4 h-4" />
                    <span>{{ isEditMode ? 'Edycja Parametrów Surowca' : 'Dodawanie Nowego Surowca' }}</span>
                </h3>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitCrudForm" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa składnika</label>
                    <input v-model="crudForm.name" type="text" required placeholder="np. Kukurydza konserwowa" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-400 uppercase mb-1">Jednostka miary</label>
                        <select v-model="crudForm.unit" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500">
                            <option value="kg">kilogram (kg)</option>
                            <option value="l">litr (l)</option>
                            <option value="szt">sztuka (szt)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-400 uppercase mb-1">Cena zakupu j.</label>
                        <input v-model="crudForm.purchase_price" type="number" step="0.01" min="0" required class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-emerald-400 font-mono font-bold focus:border-red-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div v-if="!isEditMode">
                        <label class="block font-bold text-slate-400 uppercase mb-1">Stan początkowy</label>
                        <input v-model="crudForm.stock_quantity" type="number" step="0.01" min="0" required class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono font-bold focus:border-red-500" />
                    </div>

                    <div :class="isEditMode ? 'col-span-2' : ''">
                        <label class="block font-bold text-slate-400 uppercase mb-1">Minimum logistyczne</label>
                        <input v-model="crudForm.min_limit" type="number" step="0.01" min="0" required class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-amber-400 font-mono font-bold focus:border-red-500" />
                    </div>
                </div>

                <div class="flex space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="$emit('close')" class="w-1/2 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button type="submit" :disabled="crudForm.processing" class="w-1/2 bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 font-bold text-white py-3 rounded-xl uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-1 cursor-pointer disabled:opacity-50">
                        <Save class="w-4 h-4" />
                        <span>{{ isEditMode ? 'Zapisz' : 'Utwórz' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>