<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ShieldCheck, Save } from 'lucide-vue-next';

const props = defineProps({
    availablePermissions: Object,
    rolePermissions: Object
});

// Kompletnie zmapowana lista dostępnych modułów (używana jako fallback, gdy props jest pusty)
const fallbackPermissions = {
    'pos.access': 'Kasa POS (Kelner)',
    'kds.access': 'Ekran Kuchenny KDS',
    'orders.view': 'Lista Zamówień',
    'dashboard.financial': 'Dashboard Finansowy',
    'products.manage': 'Karty Dań i Receptury BOM',
    'inventory.manage': 'Gospodarka Magazynowa Surowców',
    'loyalty.manage': 'Program Lojalnościowy',
    'promotions.manage': 'Promocje i Gratisy',
    'users.manage': 'Zarządzanie Zespołem (Pracownicy)',
    'rcp.view': 'Ewidencja Czasu Pracy (RCP)',
    'reconciliation.view': 'Rozliczenia Kurierów',
    'delivery_zones.manage': 'Strefy Dostaw',
    'settings.general': 'Ustawienia Globalne'
};

const permissionsList = computed(() => {
    return (props.availablePermissions && Object.keys(props.availablePermissions).length > 0)
        ? props.availablePermissions
        : fallbackPermissions;
});

const permissionsMatrix = ref({
    manager: props.rolePermissions?.manager || [
        'products.manage',
        'inventory.manage',
        'orders.view',
        'dashboard.financial',
        'reconciliation.view',
        'delivery_zones.manage'
    ],
    staff: props.rolePermissions?.staff || ['pos.access'],
    chef: props.rolePermissions?.chef || ['kds.access'],
    driver: props.rolePermissions?.driver || ['orders.view']
});

const togglePermission = (role, permKey) => {
    if (!permissionsMatrix.value[role]) permissionsMatrix.value[role] = [];
    const index = permissionsMatrix.value[role].indexOf(permKey);
    if (index > -1) {
        permissionsMatrix.value[role].splice(index, 1);
    } else {
        permissionsMatrix.value[role].push(permKey);
    }
};

const savePermissions = () => {
    router.post(route('admin.permissions.update'), {
        matrix: permissionsMatrix.value
    }, {
        preserveScroll: true,
        onSuccess: () => alert('Macierz uprawnień ról została pomyślnie zapisana!')
    });
};
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-xl text-xs">
        <div class="border-b border-slate-800 pb-3">
            <h2 class="text-xs font-bold uppercase tracking-widest text-slate-300 flex items-center space-x-2">
                <ShieldCheck class="w-4 h-4 text-amber-500" />
                <span>Zarządzanie Dostępem do Modułów Systemu</span>
            </h2>
            <p class="text-[11px] text-slate-400 mt-1">Zaznacz, do których funkcji mają dostęp poszczególne role. Administrator zawsze posiada pełen dostęp.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#0B0F19] text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-slate-800">
                        <th class="p-3">Moduł / Funkcja Systemu</th>
                        <th class="p-3 text-center">Menedżer (Manager)</th>
                        <th class="p-3 text-center">Kelner / POS (Staff)</th>
                        <th class="p-3 text-center">Kucharz (Chef)</th>
                        <th class="p-3 text-center">Kurier (Driver)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <tr v-for="(label, key) in permissionsList" :key="key" class="hover:bg-slate-800/30 transition">
                        <td class="p-3 font-bold text-slate-200">
                            {{ label }}
                            <span class="block text-[9px] font-mono font-normal text-slate-500">{{ key }}</span>
                        </td>
                        <td v-for="role in ['manager', 'staff', 'chef', 'driver']" :key="role" class="p-3 text-center">
                            <input 
                                type="checkbox" 
                                :checked="permissionsMatrix[role]?.includes(key)"
                                @change="togglePermission(role, key)"
                                class="rounded bg-[#0B0F19] border-slate-800 text-red-600 focus:ring-0 h-4 w-4 cursor-pointer"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end pt-4 border-t border-slate-800">
            <button @click="savePermissions" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-8 py-3 rounded-xl uppercase tracking-wider text-xs transition shadow-lg flex items-center space-x-2 cursor-pointer">
                <Save class="w-4 h-4" />
                <span>Zapisz Dostęp do Modułów</span>
            </button>
        </div>
    </div>
</template>