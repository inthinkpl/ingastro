<script setup>
import { useForm } from '@inertiajs/vue3';
import { Plus, X, Upload } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    categories: Array
});

const emit = defineEmits(['close']);

const addForm = useForm({
    name: '',
    category: 'Pizza',
    description: '',
    image: null,
    is_active: true
});

const submitAdd = () => {
    addForm.post(route('manager.products.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            addForm.reset();
        }
    });
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            
            <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                <h3 class="text-xs font-bold text-amber-500 uppercase tracking-widest flex items-center space-x-2">
                    <Plus class="w-4 h-4" />
                    <span>Dodaj Nową Pozycję w Menu</span>
                </h3>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>
            
            <form @submit.prevent="submitAdd" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa potrawy</label>
                    <input v-model="addForm.name" type="text" placeholder="np. Pizza Capricciosa" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" required />
                    <span v-if="addForm.errors.name" class="text-red-400 block mt-1">{{ addForm.errors.name }}</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Kategoria</label>
                    <select v-model="addForm.category" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" required>
                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Opis dania / Receptura marketingowa</label>
                    <textarea v-model="addForm.description" rows="3" placeholder="np. Sos pomidorowy, ser mozzarella, szynka, pieczarki..." class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1 flex items-center space-x-1">
                        <Upload class="w-3.5 h-3.5 text-amber-500" />
                        <span>Zdjęcie potrawy</span>
                    </label>
                    <input type="file" @input="addForm.image = $event.target.files[0]" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2 text-white font-medium focus:border-red-500" accept="image/*" />
                </div>

                <div class="flex items-center space-x-2 py-1">
                    <input type="checkbox" v-model="addForm.is_active" id="add_active" class="rounded border-slate-800 bg-[#0B0F19] text-red-600 focus:ring-0 h-4 w-4 cursor-pointer" />
                    <label for="add_active" class="font-bold text-slate-300 uppercase select-none cursor-pointer">Pozycja aktywna w menu</label>
                </div>

                <div class="flex space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="$emit('close')" class="w-1/3 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">
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