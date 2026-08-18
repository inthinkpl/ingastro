<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { UserPlus, Pencil, X, Save } from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    isEditMode: Boolean,
    user: Object
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    email: '',
    role: 'waiter',
    password: ''
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (props.isEditMode && props.user) {
            form.name = props.user.name;
            form.email = props.user.email;
            form.role = props.user.role;
            form.password = '';
        } else {
            form.reset();
            form.role = 'waiter';
        }
    }
});

const submitUserForm = () => {
    if (props.isEditMode && props.user) {
        form.put(route('admin.users.update', props.user.id), {
            preserveScroll: true,
            onSuccess: () => emit('close')
        });
    } else {
        form.post(route('admin.users.store'), {
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
                <h3 class="text-xs font-bold text-red-500 uppercase tracking-widest flex items-center space-x-2">
                    <UserPlus v-if="!isEditMode" class="w-4 h-4" />
                    <Pencil v-else class="w-4 h-4" />
                    <span>{{ isEditMode ? 'Modyfikacja Profilu Pracownika' : 'Rejestracja Nowego Pracownika' }}</span>
                </h3>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white p-1 cursor-pointer">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <form @submit.prevent="submitUserForm" class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Pełne Imię i Nazwisko</label>
                    <input 
                        v-model="form.name" 
                        type="text" 
                        required 
                        placeholder="np. Jan Kowalski"
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500" 
                    />
                    <span v-if="form.errors.name" class="text-red-400 block mt-1">{{ form.errors.name }}</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Firmowy Adres E-mail (Login)</label>
                    <input 
                        v-model="form.email" 
                        type="email" 
                        required 
                        placeholder="pracownik@savona.pl"
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" 
                    />
                    <span v-if="form.errors.email" class="text-red-400 block mt-1">{{ form.errors.email }}</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">Uprawnienia / Przydział roli</label>
                    <select v-model="form.role" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500 cursor-pointer">
                        <option value="admin">Admin (Pełna władza w systemie)</option>
                        <option value="manager">Manager (Magazyn, Analizy, Menu)</option>
                        <option value="chef">Chef (Kuchnia / Monitor KDS)</option>
                        <option value="waiter">Waiter (Kelner / Kasa POS)</option>
                        <option value="driver">Kierowca / Dostawca (Aplikacja mobilna)</option>
                    </select>
                    <span v-if="form.errors.role" class="text-red-400 block mt-1">{{ form.errors.role }}</span>
                </div>

                <div>
                    <label class="block font-bold text-slate-400 uppercase mb-1">
                        {{ isEditMode ? 'Nowe Hasło (Zostaw puste, aby nie zmieniać)' : 'Hasło dostępowe' }}
                    </label>
                    <input 
                        v-model="form.password" 
                        type="password" 
                        :required="!isEditMode" 
                        placeholder="••••••••"
                        class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white focus:border-red-500" 
                    />
                    <span v-if="form.errors.password" class="text-red-400 block mt-1">{{ form.errors.password }}</span>
                </div>

                <div class="flex space-x-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="$emit('close')" class="w-1/2 bg-[#0B0F19] hover:bg-slate-800 border border-slate-800 text-slate-400 py-3 rounded-xl font-bold uppercase transition cursor-pointer">
                        Anuluj
                    </button>
                    <button type="submit" :disabled="form.processing" class="w-1/2 bg-red-600 hover:bg-red-500 font-bold text-white py-3 rounded-xl uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-1 cursor-pointer disabled:opacity-50">
                        <Save class="w-4 h-4" />
                        <span>{{ form.processing ? '...' : 'Zatwierdź' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>