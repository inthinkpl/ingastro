<script setup>
import { ref, computed } from 'vue';
import { usePage, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Users, UserPlus, CheckCircle2, AlertTriangle } from 'lucide-vue-next';

// Komponenty cząstkowe
import UserTable from './Users/Partials/UserTable.vue';
import UserFormModal from './Users/Partials/UserFormModal.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);
const errorMessage = computed(() => page.props.errors?.error);

// Stany okien modalnych
const isModalOpen = ref(false);
const isEditMode = ref(false);
const selectedUser = ref(null);

const handleOpenCreate = () => {
    isEditMode.value = false;
    selectedUser.value = null;
    isModalOpen.value = true;
};

const handleOpenEdit = (user) => {
    isEditMode.value = true;
    selectedUser.value = user;
    isModalOpen.value = true;
};

const handleDeleteUser = (user) => {
    if (confirm(`Czy na pewno chcesz bezpowrotnie zwolnić pracownika: ${user.name}? Straci on dostęp do systemu.`)) {
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Zarządzanie Zespołem - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-red-600/10 border border-red-500/20 flex items-center justify-center text-red-500">
                        <Users class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-red-500 uppercase tracking-wider">Panel Administratora: Zarządzanie Zespołem</h1>
                        <p class="text-xs text-slate-400">Rejestracja pracowników, edycja uprawnień ról i dostępów do systemu.</p>
                    </div>
                </div>

                <button 
                    @click="handleOpenCreate"
                    class="bg-red-600 hover:bg-red-500 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 rounded-xl transition shadow-lg flex items-center space-x-2 cursor-pointer shrink-0"
                >
                    <UserPlus class="w-4 h-4" />
                    <span>Dodaj Pracownika</span>
                </button>
            </header>

            <!-- ALERT SUKCESU -->
            <div v-if="successMessage" class="bg-emerald-950/60 border border-emerald-900 text-emerald-400 p-3.5 rounded-2xl text-xs font-bold flex items-center space-x-2">
                <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                <span>{{ successMessage }}</span>
            </div>

            <!-- ALERT BŁĘDU -->
            <div v-if="errorMessage" class="bg-red-950/60 border border-red-900 text-red-400 p-3.5 rounded-2xl text-xs font-bold flex items-center space-x-2">
                <AlertTriangle class="w-4 h-4 text-red-400 shrink-0" />
                <span>{{ errorMessage }}</span>
            </div>

            <!-- TABELA PRACOWNIKÓW -->
            <UserTable 
                :users="users"
                @open-edit="handleOpenEdit"
                @delete="handleDeleteUser"
            />

            <!-- MODAL: FORMULARZ KARTY PRACOWNIKA -->
            <UserFormModal 
                :is-open="isModalOpen"
                :is-edit-mode="isEditMode"
                :user="selectedUser"
                @close="isModalOpen = false"
            />

        </div>
    </AuthenticatedLayout>
</template>