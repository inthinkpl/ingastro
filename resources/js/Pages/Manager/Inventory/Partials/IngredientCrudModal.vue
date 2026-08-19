<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { PackagePlus, Edit3, X, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    isEditMode: { type: Boolean, default: false },
    ingredient: { type: Object, default: null }
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    stock_main: 0,
    min_stock_local: 0,
    unit: 'kg',
    purchase_price: 0
});

// Podczas zmiany edytowanego surowca przepisujemy dane do formularza
watch(() => props.ingredient, (newVal) => {
    if (newVal) {
        form.name = newVal.name || '';
        form.stock_main = newVal.stock_main ?? newVal.stock_quantity ?? newVal.stock ?? 0;
        // Wczytujemy min_stock_local lub fallback min_limit
        form.min_stock_local = newVal.min_stock_local ?? newVal.min_limit ?? 0;
        form.unit = newVal.unit || 'kg';
        form.purchase_price = newVal.purchase_price ?? newVal.price ?? newVal.cost ?? 0;
    } else {
        form.reset();
    }
}, { immediate: true });

const handleClose = () => {
    form.reset();
    emit('close');
};

const submitForm = () => {
    if (props.isEditMode && props.ingredient) {
        form.put(route('manager.inventory.update', props.ingredient.id), {
            preserveScroll: true,
            onSuccess: () => handleClose()
        });
    } else {
        form.post(route('manager.inventory.store'), {
            preserveScroll: true,
            onSuccess: () => handleClose()
        });
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-5">
            
            <!-- NAGŁÓWEK -->
            <div class="flex justify-between items-start border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-red-500/10 border border-red-500/20 text-red-400 rounded-xl">
                        <Edit3 v-if="isEditMode" class="w-5 h-5" />
                        <PackagePlus v-else class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                            {{ isEditMode ? 'Edycja Surowca' : 'Nowy Surowiec' }}
                        </h3>
                        <p class="text-[11px] text-slate-400">Parametry kartoteki magazynowej ERP</p>
                    </div>
                </div>
                <button @click="handleClose" class="text-slate-500 hover:text-white transition cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- FORMULARZ -->
            <form @submit.prevent="submitForm" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nazwa surowca:</label>
                    <input 
                        v-model="form.name" 
                        type="text" 
                        placeholder="np. Mąka Pszenna Typ 500" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white"
                        required
                    />
                    <span v-if="form.errors.name" class="text-red-400 text-[10px] font-bold mt-1 block">{{ form.errors.name }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Jednostka:</label>
                        <select 
                            v-model="form.unit" 
                            class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white cursor-pointer"
                        >
                            <option value="kg">Kilogram (kg)</option>
                            <option value="g">Gram (g)</option>
                            <option value="l">Litr (l)</option>
                            <option value="ml">Mililitr (ml)</option>
                            <option value="szt">Sztuka (szt)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Cena zakupu / Jedn. (zł):</label>
                        <input 
                            v-model="form.purchase_price" 
                            type="number" 
                            step="0.01" 
                            min="0"
                            placeholder="0.00" 
                            class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white font-mono"
                            required
                        />
                    </div>
                </div>

                <!-- STAN POCZĄTKOWY DLA NOWEGO SUROWCA -->
                <div v-if="!isEditMode">
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Początkowy Stan (Magazyn Główny):</label>
                    <input 
                        v-model="form.stock_main" 
                        type="number" 
                        step="0.001" 
                        min="0"
                        placeholder="0.000" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white font-mono"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Minimum Logistyczne (Lokalne):</label>
                    <input 
                        v-model="form.min_stock_local" 
                        type="number" 
                        step="0.001" 
                        min="0"
                        placeholder="np. 2.000" 
                        class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white font-mono"
                    />
                    <p class="text-[10px] text-slate-500 mt-1">Gdy stan w lokalu spadnie poniżej tej wartości, pojawi się ostrzeżenie o braku surowca.</p>
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="handleClose" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 py-2.5 rounded-xl text-xs font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-2/3 bg-red-600 hover:bg-red-700 disabled:bg-slate-800 text-white font-bold py-2.5 rounded-xl text-xs uppercase tracking-wider transition cursor-pointer shadow-lg flex justify-center items-center space-x-1.5"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ isEditMode ? 'Zapisz Zmiany' : 'Dodaj Surowiec' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>