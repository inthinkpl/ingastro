<script setup>
import { Pencil, Trash2, ShieldCheck, UserCheck, Utensils, ShoppingBag, Truck } from 'lucide-vue-next';

defineProps({
    users: Array
});

defineEmits(['open-edit', 'delete']);

const getRoleConfig = (role) => {
    switch (role) {
        case 'admin':
            return { label: 'Admin (Pełny dostęp)', class: 'bg-red-950/80 text-red-400 border-red-900', icon: ShieldCheck };
        case 'manager':
            return { label: 'Manager (Magazyn & BI)', class: 'bg-purple-950/80 text-purple-400 border-purple-900', icon: UserCheck };
        case 'chef':
            return { label: 'Chef (Kuchnia / KDS)', class: 'bg-blue-950/80 text-blue-400 border-blue-900', icon: Utensils };
        case 'waiter':
            return { label: 'Waiter (Kelner / Kasa POS)', class: 'bg-amber-950/80 text-amber-400 border-amber-900', icon: ShoppingBag };
        case 'driver':
            return { label: 'Dostawca (Kierowca)', class: 'bg-emerald-950/80 text-emerald-400 border-emerald-900', icon: Truck };
        default:
            return { label: role, class: 'bg-slate-800 text-slate-400 border-slate-700', icon: UserCheck };
    }
};
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#0B0F19] text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800">
                        <th class="p-4">Imię i Nazwisko</th>
                        <th class="p-4">Adres E-mail (Login)</th>
                        <th class="p-4">Rola / Stanowisko</th>
                        <th class="p-4 text-right">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-slate-800/40 transition">
                        
                        <td class="p-4 font-bold text-white text-sm">
                            {{ user.name }}
                        </td>

                        <td class="p-4 text-slate-400 font-mono">
                            {{ user.email }}
                        </td>

                        <td class="p-4">
                            <span 
                                :class="getRoleConfig(user.role).class" 
                                class="text-[10px] px-2.5 py-1 rounded-full border font-bold uppercase tracking-wider inline-flex items-center space-x-1.5"
                            >
                                <component :is="getRoleConfig(user.role).icon" class="w-3.5 h-3.5" />
                                <span>{{ getRoleConfig(user.role).label }}</span>
                            </span>
                        </td>

                        <td class="p-4 text-right">
                            <div class="flex justify-end items-center space-x-1.5">
                                <button 
                                    @click="$emit('open-edit', user)" 
                                    class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Pencil class="w-3.5 h-3.5 text-amber-500" />
                                    <span>Edytuj</span>
                                </button>

                                <button 
                                    @click="$emit('delete', user)" 
                                    class="bg-[#0B0F19] hover:bg-red-950 text-red-400 border border-slate-800 hover:border-red-900 px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                    <span>Usuń</span>
                                </button>
                            </div>
                        </td>

                    </tr>

                    <tr v-if="users.length === 0">
                        <td colspan="4" class="p-8 text-center italic text-slate-500">
                            Brak zarejestrowanych pracowników w systemie.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>