<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Array
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);
const errorMessage = computed(() => page.props.errors?.error);

const isModalOpen = ref(false);
const isEditMode = ref(false);
const editingUserId = ref(null);

const form = useForm({
    name: '',
    email: '',
    role: 'waiter',
    password: ''
});

const openCreateModal = () => {
    isEditMode.value = false;
    editingUserId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    editingUserId.value = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = ''; // czyścimy pole hasła, wypełniane tylko przy zmianie
    isModalOpen.value = true;
};

const submitUserForm = () => {
    if (isEditMode.value) {
        form.put(route('admin.users.update', editingUserId.value), {
            onSuccess: () => isModalOpen.value = false
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => isModalOpen.value = false
        });
    }
};

const deleteUser = (user) => {
    if (confirm(`Czy na pewno chcesz bezpowrotnie zwolnić pracownika: ${user.name}? Zgubi dostęp do systemu.`)) {
        form.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
    
    <div class="min-h-screen bg-slate-950 text-white p-6">
        <header class="mb-8 border-b border-slate-800 pb-4 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">👥</span>
                <h1 class="text-2xl font-black tracking-wider text-red-500">PANEL ADMINISTRATORA: ZARZĄDZANIE ZESPOŁEM</h1>
            </div>
            <button @click="openCreateModal" class="bg-red-600 hover:bg-red-500 text-white font-bold text-xs py-2.5 px-4 rounded-xl transition-all uppercase tracking-wider">
                + Dodaj pracownika
            </button>
        </header>

        <div v-if="successMessage" class="mb-6 p-4 bg-emerald-600 text-white rounded-lg font-bold shadow-lg">
            ✔ {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="mb-6 p-4 bg-red-600 text-white rounded-lg font-bold shadow-lg">
            ⚠️ {{ errorMessage }}
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-950 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <th class="p-4">Imię i Nazwisko</th>
                        <th class="p-4">Adres E-mail (Login)</th>
                        <th class="p-4">Rola / Stanowisko</th>
                        <th class="p-4 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-850 text-sm">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-slate-850/50 transition-colors">
                        <td class="p-4 font-bold text-slate-200">{{ user.name }}</td>
                        <td class="p-4 text-slate-400 font-mono">{{ user.email }}</td>
                        <td class="p-4">
                            <span :class="{
                                'bg-red-950 text-red-400 border-red-900': user.role === 'admin',
                                'bg-purple-950 text-purple-400 border-purple-900': user.role === 'manager',
                                'bg-blue-950 text-blue-400 border-blue-900': user.role === 'chef',
                                'bg-orange-950 text-orange-400 border-orange-900': user.role === 'waiter'
                            }" class="text-[10px] px-2 py-1 rounded-md border font-black uppercase tracking-wider">
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            <button @click="openEditModal(user)" class="bg-slate-800 hover:bg-slate-700 text-xs font-bold py-1 px-3 rounded border border-slate-700 text-slate-300">Moduł Edycji</button>
                            <button @click="deleteUser(user)" class="bg-slate-800 hover:bg-red-900 text-xs font-bold py-1 px-3 rounded border border-slate-700 text-red-400 hover:text-white">Usuń</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="isModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-black text-red-500 mb-4 uppercase">
                    {{ isEditMode ? 'Modyfikacja profilu pracownika' : 'Rejestracja nowej karty pracownika' }}
                </h3>

                <form @submit.prevent="submitUserForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Pełne Imię i Nazwisko</label>
                        <input v-model="form.name" type="text" required class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-sm text-white" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Firmowy Adres E-mail</label>
                        <input v-model="form.email" type="email" required class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-sm text-white font-mono" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Uprawnienia / Przydział roli</label>
                        <select v-model="form.role" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-sm text-white cursor-pointer">
                            <option value="admin">Admin (Pełna władza)</option>
                            <option value="manager">Manager (Magazyn, Analizy, Menu)</option>
                            <option value="chef">Chef (Kuchnia / Monitor KDS)</option>
                            <option value="waiter">Waiter (Kelner / Panel Kasy POS)</option>
                            <option value="driver">Kierowca / Dostawca (Aplikacja kierowcy)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">
                            {{ isEditMode ? 'Nowe Hasło (Zostaw puste, aby nie zmieniać)' : 'Hasło dostępowe' }}
                        </label>
                        <input v-model="form.password" type="password" :required="!isEditMode" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-sm text-white" />
                    </div>

                    <div class="flex space-x-3 pt-4 border-t border-slate-800">
                        <button type="button" @click="isModalOpen = false" class="w-1/2 bg-slate-800 py-3 rounded-xl text-xs font-bold uppercase">Anuluj</button>
                        <button type="submit" :disabled="form.processing" class="w-1/2 bg-red-600 hover:bg-red-500 text-white font-bold py-3 rounded-xl text-xs uppercase tracking-wider">
                            {{ form.processing ? 'Przetwarzanie...' : 'Zatwierdź' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    
    </AuthenticatedLayout>
</template>