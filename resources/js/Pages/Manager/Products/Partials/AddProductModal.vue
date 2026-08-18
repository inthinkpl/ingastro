<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, X, Upload, Trash2, Wand2 } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    categories: Array
});

const emit = defineEmits(['close']);

const isCustomCategory = ref(false);

// Szablony wariantów dla pizzerii i gastronomii
const VARIANT_PRESETS = {
    'Pizza': [
        { size_name: 'Mała (32cm)', price: 28.00 },
        { size_name: 'Duża (42cm)', price: 38.00 }
    ],
    'Sałatki': [
        { size_name: 'Porcja Standard (300g)', price: 24.00 },
        { size_name: 'Porcja Maxi (500g)', price: 32.00 }
    ],
    'Napoje': [
        { size_name: 'Puszka 0.33l', price: 7.00 },
        { size_name: 'Butelka 0.5l', price: 9.00 },
        { size_name: 'Butelka 1l', price: 14.00 }
    ],
    'Sosy': [
        { size_name: 'Pojemnik 50ml', price: 4.00 }
    ]
};

const addForm = useForm({
    name: '',
    category: 'Pizza',
    description: '',
    image: null,
    is_active: true,
    variants: [
        { size_name: 'Mała (32cm)', price: 28.00 },
        { size_name: 'Duża (42cm)', price: 38.00 }
    ]
});

const toggleCategoryMode = () => {
    isCustomCategory.value = !isCustomCategory.value;
    if (isCustomCategory.value) {
        addForm.category = '';
    } else if (props.categories && props.categories.length > 0) {
        addForm.category = props.categories[0];
    }
};

const applyPreset = () => {
    const preset = VARIANT_PRESETS[addForm.category];
    if (preset) {
        addForm.variants = preset.map(v => ({ size_name: v.size_name, price: v.price }));
    }
};

const addVariantRow = () => {
    addForm.variants.push({ size_name: '', price: 0.00 });
};

const removeVariantRow = (index) => {
    if (addForm.variants.length > 1) {
        addForm.variants.splice(index, 1);
    }
};

const handleClose = () => {
    isCustomCategory.value = false;
    emit('close');
};

const submitAdd = () => {
    addForm.post(route('manager.products.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCustomCategory.value = false;
            addForm.reset();
            emit('close');
        }
    });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            
            <div class="border-b border-slate-800 pb-3 flex justify-between items-center sticky top-0 bg-slate-900 z-10">
                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center space-x-2">
                    <Plus class="w-4 h-4" />
                    <span>Dodaj Nową Pozycję w Menu</span>
                </h3>
                <button type="button" @click="handleClose" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <form @submit.prevent="submitAdd" class="space-y-4 text-xs">
                <!-- NAZWA POTRAWY -->
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa potrawy</label>
                    <input v-model="addForm.name" type="text" placeholder="np. Pizza Capricciosa" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" required />
                    <span v-if="addForm.errors.name" class="text-red-400 block mt-1">{{ addForm.errors.name }}</span>
                </div>

                <!-- KATEGORIA -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block font-bold text-slate-400 uppercase">Kategoria</label>
                        <button 
                            type="button" 
                            @click="toggleCategoryMode" 
                            class="text-[11px] text-amber-500 hover:text-amber-400 font-bold uppercase transition cursor-pointer"
                        >
                            {{ isCustomCategory ? '← Wybierz z listy' : '+ Wpisz nową' }}
                        </button>
                    </div>

                    <select 
                        v-if="!isCustomCategory" 
                        v-model="addForm.category" 
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500 cursor-pointer" 
                        required
                    >
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>

                    <input 
                        v-else 
                        v-model="addForm.category" 
                        type="text" 
                        placeholder="np. Desery, Burger..." 
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" 
                        required 
                    />
                </div>

                <!-- WARIANTY ROZMIARÓW I CEN -->
                <div class="space-y-2 border-t border-b border-slate-800 py-3">
                    <div class="flex justify-between items-center">
                        <label class="block font-bold text-amber-500 uppercase">Warianty i Ceny (np. Mała / Duża)</label>
                        <button 
                            type="button" 
                            @click="applyPreset" 
                            class="text-[10px] text-slate-400 hover:text-amber-400 font-bold uppercase flex items-center space-x-1 cursor-pointer"
                        >
                            <Wand2 class="w-3 h-3 text-amber-500" />
                            <span>Załaduj warianty dla: {{ addForm.category }}</span>
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div v-for="(v, idx) in addForm.variants" :key="idx" class="flex items-center space-x-2 bg-[#0B0F19] p-2 rounded-xl border border-slate-800">
                            <input 
                                v-model="v.size_name" 
                                type="text" 
                                placeholder="Rozmiar (np. 32cm, 0.5l)" 
                                class="w-2/3 bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-white" 
                                required 
                            />
                            
                            <div class="w-1/3 relative">
                                <input 
                                    v-model.number="v.price" 
                                    type="number" 
                                    step="0.01" 
                                    placeholder="Cena" 
                                    class="w-full bg-slate-900 border border-slate-800 rounded-lg p-2 text-xs text-amber-400 font-mono font-bold pr-6 text-right" 
                                    required 
                                />
                                <span class="absolute right-2 top-2 text-[10px] text-slate-500 font-bold">zł</span>
                            </div>

                            <button 
                                type="button" 
                                @click="removeVariantRow(idx)" 
                                :disabled="addForm.variants.length === 1"
                                class="text-slate-600 hover:text-red-400 disabled:opacity-20 p-1 cursor-pointer"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <button 
                        type="button" 
                        @click="addVariantRow" 
                        class="w-full border border-dashed border-slate-800 hover:border-slate-700 text-slate-400 py-2 rounded-xl text-xs font-bold uppercase transition flex items-center justify-center space-x-1 cursor-pointer"
                    >
                        <Plus class="w-3.5 h-3.5 text-amber-500" />
                        <span>Dodaj kolejny rozmiar</span>
                    </button>
                </div>

                <!-- OPIS DANIA -->
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Opis dania</label>
                    <textarea v-model="addForm.description" rows="2" placeholder="Składniki, opis na stronę..." class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500"></textarea>
                </div>

                <!-- ZDJĘCIE -->
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1 flex items-center space-x-1">
                        <Upload class="w-3.5 h-3.5 text-amber-500" />
                        <span>Zdjęcie potrawy</span>
                    </label>
                    <input type="file" @input="addForm.image = $event.target.files[0]" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2 text-white font-medium focus:border-red-500" accept="image/*" />
                </div>

                <!-- AKTYWNY -->
                <div class="flex items-center space-x-2 py-1">
                    <input type="checkbox" v-model="addForm.is_active" id="add_active" class="rounded border-slate-800 bg-[#0B0F19] text-red-600 focus:ring-0 h-4 w-4 cursor-pointer" />
                    <label for="add_active" class="font-bold text-slate-300 uppercase select-none cursor-pointer">Pozycja aktywna w menu</label>
                </div>

                <!-- PRZYCISKI -->
                <div class="flex space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="handleClose" class="w-1/3 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button type="submit" :disabled="addForm.processing" class="w-2/3 bg-gradient-to-r from-red-600 to-amber-500 hover:from-red-700 hover:to-amber-600 font-bold text-white py-3 rounded-xl uppercase tracking-wider transition shadow-lg cursor-pointer disabled:opacity-50">
                        {{ addForm.processing ? 'Zapisywanie...' : 'Zapisz w Menu' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>