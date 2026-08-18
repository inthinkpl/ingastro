<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Edit3, X, Upload } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    product: Object,
    categories: Array
});

const emit = defineEmits(['close']);

// Stan przełącznika własnej kategorii
const isCustomCategory = ref(false);

const editForm = useForm({
    _method: 'PUT',
    name: '',
    category: '',
    description: '',
    image: null,
    is_active: true
});

// Reagowanie na zmianę wybranego produktu
watch(() => props.product, (newProduct) => {
    if (newProduct) {
        editForm.name = newProduct.name || '';
        editForm.category = newProduct.category || '';
        editForm.description = newProduct.description || '';
        editForm.image = null;
        editForm.is_active = Boolean(newProduct.is_active);

        // Jeśli kategoria dania nie znajduje się na liście domyślnych, przełącz na własny input
        if (newProduct.category && props.categories && !props.categories.includes(newProduct.category)) {
            isCustomCategory.value = true;
        } else {
            isCustomCategory.value = false;
        }
    }
}, { immediate: true });

const toggleCategoryMode = () => {
    isCustomCategory.value = !isCustomCategory.value;
    if (isCustomCategory.value) {
        editForm.category = '';
    } else if (props.categories && props.categories.length > 0) {
        editForm.category = props.categories[0];
    }
};

const handleClose = () => {
    isCustomCategory.value = false;
    emit('close');
};

const submitEdit = () => {
    if (!props.product) return;

    // Przesyłamy formularz metodą POST z polem _method: 'PUT' dla sprawnej obsługi plików multimedialnych w Laravelu
    editForm.post(route('manager.products.update', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            isCustomCategory.value = false;
            emit('close');
        }
    });
};
</script>

<template>
    <div v-if="isOpen && product" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            
            <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center space-x-2">
                    <Edit3 class="w-4 h-4" />
                    <span>Edytuj Pozycję w Menu</span>
                </h3>
                <button @click="handleClose" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa potrawy</label>
                    <input v-model="editForm.name" type="text" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-amber-500" required />
                    <span v-if="editForm.errors.name" class="text-red-400 block mt-1">{{ editForm.errors.name }}</span>
                </div>

                <!-- SEKCJA WYBORU / EDYCJI KATEGORII -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block font-bold text-slate-400 uppercase">Kategoria</label>
                        <button 
                            type="button" 
                            @click="toggleCategoryMode" 
                            class="text-[11px] text-amber-500 hover:text-amber-400 font-bold uppercase transition cursor-pointer"
                        >
                            {{ isCustomCategory ? '← Wybierz z listy' : '+ Wpisz nową kategorię' }}
                        </button>
                    </div>

                    <select 
                        v-if="!isCustomCategory" 
                        v-model="editForm.category" 
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-amber-500 cursor-pointer" 
                        required
                    >
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>

                    <input 
                        v-else 
                        v-model="editForm.category" 
                        type="text" 
                        placeholder="Wpisz nową kategorię..." 
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-amber-500" 
                        required 
                    />
                    <span v-if="editForm.errors.category" class="text-red-400 block mt-1">{{ editForm.errors.category }}</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Opis dania / Receptura marketingowa</label>
                    <textarea v-model="editForm.description" rows="3" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-amber-500"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1 flex items-center space-x-1">
                        <Upload class="w-3.5 h-3.5 text-amber-500" />
                        <span>Zmień zdjęcie potrawy (opcjonalnie)</span>
                    </label>
                    <input type="file" @input="editForm.image = $event.target.files[0]" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2 text-white font-medium focus:border-amber-500" accept="image/*" />
                </div>

                <div class="flex items-center space-x-2 py-1">
                    <input type="checkbox" v-model="editForm.is_active" id="edit_active" class="rounded border-slate-800 bg-[#0B0F19] text-amber-500 focus:ring-0 h-4 w-4 cursor-pointer" />
                    <label for="edit_active" class="font-bold text-slate-300 uppercase select-none cursor-pointer">Pozycja aktywna w menu</label>
                </div>

                <div class="flex space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="handleClose" class="w-1/3 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button type="submit" :disabled="editForm.processing" class="w-2/3 bg-amber-500 hover:bg-amber-600 font-bold text-slate-950 py-3 rounded-xl uppercase tracking-wider transition shadow-lg cursor-pointer disabled:opacity-50">
                        {{ editForm.processing ? 'Zapisywanie...' : 'Zapisz Zmiany' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>