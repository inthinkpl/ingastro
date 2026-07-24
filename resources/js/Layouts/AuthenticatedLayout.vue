<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// Pobieramy dane zalogowanego użytkownika oraz jego uprawnienia przekazane przez Inertia
const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => page.props.auth?.role || user.value?.role);
const userPermissions = computed(() => page.props.auth?.permissions || []);

// 🛡️ Funkcja pomocnicza do sprawdzania konkretnego uprawnienia
const can = (permission) => {
    if (userRole.value === 'admin' || userPermissions.value.includes('*')) {
        return true; // Admin zawsze widzi wszystko
    }
    return userPermissions.value.includes(permission);
};

// 🛡️ Funkcja pomocnicza sprawdzająca, czy użytkownik ma JAKIEKOLWIEK z podanych uprawnień
const canAny = (permissionsArray) => {
    return permissionsArray.some(p => can(p));
};

// Lista wszystkich modułów w systemie powiązana z kluczami uprawnień RBAC
const navigationItems = [
    { 
        name: 'Kasa POS (Kelner)', 
        route: 'order.pos', 
        icon: '🛒', 
        condition: () => ['staff', 'waiter', 'manager', 'admin'].includes(userRole.value) 
    },
    { 
        name: 'Ekran Kuchenny KDS', 
        route: 'kds.index', 
        icon: '👨‍🍳', 
        condition: () => ['chef', 'manager', 'admin'].includes(userRole.value) 
    },
    { 
        name: 'Dashboard Finansowy', 
        route: 'manager.dashboard', 
        icon: '📈', 
        condition: () => ['manager', 'admin'].includes(userRole.value) 
    },
    { 
        name: 'Kreator Menu i BOM', 
        route: 'manager.products.index', 
        icon: '🍕', 
        permission: 'products.manage' 
    },
    { 
        name: 'Magazyn Surowców', 
        route: 'manager.inventory', 
        icon: '📦', 
        permission: 'inventory.manage' 
    },
    { 
        name: 'Zarządzanie Zespołem', 
        route: 'admin.users.index', 
        icon: '👥', 
        permission: 'users.manage' 
    },
    { 
        name: 'Rozliczenia Kurierów', 
        route: 'manager.reconciliation.index', 
        icon: '💰', 
        permission: 'reconciliation.view' 
    },
    { 
        name: 'Strefy Dostaw', 
        route: 'manager.delivery_zones.index', 
        icon: '🗺️', 
        permission: 'delivery_zones.manage' 
    },
    { 
        name: 'Ustawienia Globalne', 
        route: 'admin.settings.edit', 
        icon: '⚙️', 
        permissions: ['settings.general', 'settings.discounts', 'settings.payments'] 
    },
];

// DYNAMICZNE FILTROWANIE LINKÓW NAWIGACJI
const filteredNavigation = computed(() => {
    return navigationItems.filter(item => {
        // 1. Dostęp na podstawie roli bazowej (np. POS/KDS)
        if (item.condition) {
            return item.condition();
        }
        // 2. Dostęp na podstawie pojedynczego uprawnienia z macierzy
        if (item.permission) {
            return can(item.permission);
        }
        // 3. Dostęp na podstawie dowolnego uprawnienia z listy (np. Ustawienia)
        if (item.permissions) {
            return canAny(item.permissions);
        }
        return false;
    });
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-white flex flex-col md:flex-row">
        <!-- BOCZNY PANEL NAWIGACYJNY (SIDEBAR) -->
        <aside class="w-full md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col justify-between p-4 shrink-0">
            <div>
                <!-- LOGO LOKALU -->
                <div class="mb-8 px-2 py-4 border-b border-slate-800">
                    <div class="text-lg font-black tracking-widest text-orange-500">SAVONA ERP</div>
                    <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">System Zarządzania Gastronomią</div>
                </div>

                <!-- PROFIL PRACOWNIKA -->
                <div class="mb-6 p-3 bg-slate-950 rounded-xl border border-slate-850 flex items-center space-x-3">
                    <div class="h-8 w-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-sm text-orange-400">
                        {{ user?.name?.charAt(0) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-xs font-bold text-slate-200 truncate">{{ user?.name }}</div>
                        <span :class="{
                            'bg-red-950 text-red-400 border-red-900': userRole === 'admin',
                            'bg-purple-950 text-purple-400 border-purple-900': userRole === 'manager',
                            'bg-blue-950 text-blue-400 border-blue-900': userRole === 'chef',
                            'bg-orange-950 text-orange-400 border-orange-900': ['waiter', 'staff'].includes(userRole),
                            'bg-emerald-950 text-emerald-400 border-emerald-900': userRole === 'driver'
                        }" class="text-[9px] px-1.5 py-0.5 rounded border font-black uppercase tracking-wider block w-max mt-0.5">
                            {{ userRole }}
                        </span>
                    </div>
                </div>

                <!-- DYNAMICZNE LINKI MIGRACYJNE -->
                <nav class="space-y-1">
                    <Link 
                        v-for="item in filteredNavigation" 
                        :key="item.route" 
                        :href="route(item.route)"
                        :class="route().current(item.route) ? 'bg-orange-600 text-white font-bold' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200'"
                        class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all"
                    >
                        <span>{{ item.icon }}</span>
                        <span>{{ item.name }}</span>
                    </Link>
                </nav>
            </div>

            <!-- WYLOGOWANIE Z SYSTEMU -->
            <div class="pt-4 border-t border-slate-800 mt-6">
                <Link 
                    :href="route('logout')" 
                    method="post" 
                    as="button" 
                    class="w-full bg-slate-950 hover:bg-red-950/40 text-red-400 hover:text-red-300 border border-slate-850 hover:border-red-900 text-xs font-bold py-2.5 rounded-xl uppercase tracking-wider transition-all"
                >
                    🚪 Wyloguj pracownika
                </Link>
            </div>
        </aside>

        <!-- CENTRALNY KONTENER DLA PODSTRON (DYNAMICZNY CONTENT) -->
        <main class="flex-1 overflow-y-auto max-h-screen">
            <slot />
        </main>
    </div>
</template>