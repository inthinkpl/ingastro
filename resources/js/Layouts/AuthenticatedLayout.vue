<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    ShoppingCart, ChefHat, ShoppingBag, TrendingUp, Pizza, 
    Package, Users, Banknote, Map, Settings, LogOut, Clock,
    Play, Pause, Square, ChevronDown, ChevronRight, UserCheck, Gift, Tag, Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    hideSidebar: {
        type: Boolean,
        default: false
    }
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => page.props.auth?.role || user.value?.role);
const userPermissions = computed(() => page.props.auth?.permissions || []);

// Dynamiczne dane restauracji oraz subskrypcji
const restaurantName = computed(() => page.props.restaurant?.name || 'Pizzeria ERP');
const subscription = computed(() => page.props.restaurant?.subscription || {
    plan_id: 1,
    plan_name: 'Brak Planu',
    ends_at: 'Bezterminowo',
    status: 'expired'
});

// LOGIKA OKRESU PRÓBNEGO (TRIAL)
const isTrialing = computed(() => subscription.value.status === 'trialing');

const daysLeft = computed(() => {
    if (!subscription.value.ends_at || subscription.value.ends_at === 'Bezterminowo') return 0;
    
    const end = new Date(subscription.value.ends_at);
    const now = new Date();
    const diffTime = end - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return diffDays > 0 ? diffDays : 0;
});

// 💳 BEZPIECZNE PRZEKIEROWANIE DO STRIPE CHECKOUT
const redirectToStripe = (event) => {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const planId = subscription.value.plan_id || 1;
    const checkoutUrl = `${window.location.origin}/subscription/checkout/${planId}`;

    console.log('Przekierowanie do Stripe Checkout:', checkoutUrl);

    // Wymuszamy bezpośrednie przejście okna przeglądarki
    window.location.assign(checkoutUrl);
};

// MODUŁY ODBLOKOWANE W PLANIE SUBSKRYPCJI TENANTA
const tenantFeatures = computed(() => page.props.auth?.tenant_features || []);

const hasFeature = (featureName) => {
    return tenantFeatures.value.includes(featureName);
};

// STAN I LOGIKA RCP (REJESTRACJA CZASU PRACY)
const activeShift = computed(() => page.props.auth?.active_shift || null);
const elapsedTime = ref('00:00:00');
let timerInterval = null;

const updateTimer = () => {
    if (!activeShift.value || !activeShift.value.clock_in) {
        elapsedTime.value = '00:00:00';
        return;
    }

    const start = new Date(activeShift.value.clock_in).getTime();
    const now = new Date().getTime();
    const diff = Math.max(0, Math.floor((now - start) / 1000));

    const hours = String(Math.floor(diff / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const seconds = String(diff % 60).padStart(2, '0');

    elapsedTime.value = `${hours}:${minutes}:${seconds}`;
};

onMounted(() => {
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const startShift = () => {
    router.post(route('rcp.clock-in'), {}, { preserveScroll: true });
};

const pauseShift = () => {
    router.post(route('rcp.toggle-pause'), {}, { preserveScroll: true });
};

const stopShift = () => {
    if (confirm('Czy na pewno chcesz zakończyć dzisiejszą zmianę?')) {
        router.post(route('rcp.clock-out'), {}, { preserveScroll: true });
    }
};

const can = (permission) => {
    if (userRole.value === 'admin' || userPermissions.value.includes('*')) return true;
    return userPermissions.value.includes(permission);
};

const canAny = (permissionsArray) => permissionsArray.some(p => can(p));

// STAN ROZSUWANEGO SUBMENU "ZARZĄDZANIE ZESPOŁEM"
const isTeamMenuOpen = ref(
    route().current('admin.users.*') || route().current('admin.rcp.*')
);

const toggleTeamMenu = () => {
    isTeamMenuOpen.value = !isTeamMenuOpen.value;
};
</script>

<template>
    <div class="min-h-screen bg-[#0B0F19] text-slate-300 flex flex-col md:flex-row font-sans antialiased">
        
        <!-- SIDEBAR -->
        <aside v-if="!hideSidebar" class="w-full md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col justify-between p-4 shrink-0 shadow-2xl">
            <div>
                <!-- NAGŁÓWEK SIDEBARU -->
                <div class="mb-6 px-2 py-3 border-b border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-black text-white tracking-wide truncate max-w-[170px]" :title="restaurantName">
                            {{ restaurantName }}
                        </h2>
                        <span class="text-[9px] bg-amber-500 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider shrink-0">
                            ERP
                        </span>
                    </div>

                    <!-- KARTA STATUSU PLANU -->
                    <div class="p-2 bg-slate-950/70 border border-slate-800 rounded-xl flex items-center justify-between text-[10px]">
                        <div class="flex items-center space-x-1.5 overflow-hidden">
                            <span 
                                class="h-2 w-2 rounded-full shrink-0" 
                                :class="
                                    subscription.status === 'active' ? 'bg-emerald-500' :
                                    isTrialing ? 'bg-amber-500 animate-pulse' : 'bg-red-500'
                                "
                            ></span>
                            <span class="font-bold text-amber-400 truncate">
                                {{ isTrialing ? `${subscription.plan_name} (TRIAL)` : subscription.plan_name }}
                            </span>
                        </div>
                        <span class="text-slate-400 font-mono text-[9px] shrink-0">
                            {{ subscription.ends_at === 'Bezterminowo' ? '∞' : 'do ' + subscription.ends_at }}
                        </span>
                    </div>
                </div>

                <!-- DANE ZALOGOWANEGO UŻYTKOWNIKA -->
                <div class="mb-4 p-3 bg-[#0B0F19] rounded-2xl border border-slate-800 flex items-center space-x-3">
                    <div class="h-9 w-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-sm text-amber-500 shrink-0">
                        {{ user?.name?.charAt(0) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-xs font-bold text-white truncate">{{ user?.name }}</div>
                        <span class="text-[9px] px-2 py-0.5 rounded-lg border font-bold uppercase tracking-wider block w-max mt-0.5 bg-amber-950/60 text-amber-400 border-amber-900">
                            {{ userRole }}
                        </span>
                    </div>
                </div>

                <!-- WIDGET REJESTRACJI CZASU PRACY (RCP) IN-SIDEBAR -->
                <div v-if="hasFeature('rcp')" class="mb-6 p-3 bg-[#0B0F19] rounded-2xl border border-slate-800 space-y-2.5">
                    <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
                        <div class="flex items-center space-x-1.5">
                            <Clock class="w-3.5 h-3.5 text-amber-500" />
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Czas Zmiany (RCP)</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <span v-if="activeShift?.status === 'working'" class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span :class="{
                                'text-emerald-400': activeShift?.status === 'working',
                                'text-amber-400': activeShift?.status === 'on_break',
                                'text-slate-500': !activeShift
                            }" class="text-[9px] font-bold uppercase">
                                {{ activeShift?.status === 'working' ? 'Praca' : activeShift?.status === 'on_break' ? 'Pauza' : 'Offline' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black font-mono text-white tracking-wider">{{ elapsedTime }}</span>

                        <div class="flex items-center space-x-1">
                            <button 
                                v-if="!activeShift" 
                                @click="startShift" 
                                class="bg-emerald-600 hover:bg-emerald-500 text-white px-2.5 py-1 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                            >
                                <Play class="w-3 h-3 fill-current" />
                                <span>Start</span>
                            </button>

                            <template v-else>
                                <button 
                                    @click="pauseShift" 
                                    :class="activeShift.status === 'on_break' ? 'bg-amber-500 text-black' : 'bg-slate-800 text-amber-400 hover:bg-slate-700'"
                                    class="p-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                                    :title="activeShift.status === 'on_break' ? 'Wznów pracę' : 'Rozpocznij przerwę'"
                                >
                                    <Pause class="w-3 h-3 fill-current" />
                                </button>

                                <button 
                                    @click="stopShift" 
                                    class="bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/30 px-2 py-1 rounded-xl text-[10px] font-bold uppercase transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Square class="w-3 h-3 fill-current" />
                                    <span>Stop</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- NAWIGACJA -->
                <nav class="space-y-1">
                    <!-- KASA POS (KELNER) -->
                    <Link 
                        v-if="hasFeature('pos') && (can('pos.access') || userRole === 'waiter')"
                        :href="route('order.pos')" 
                        :class="route().current('order.pos') || route().current('pos.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <ShoppingCart class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Kasa POS (Kelner)</span>
                    </Link>

                    <!-- EKRAN KUCHENNY KDS -->
                    <Link 
                        v-if="hasFeature('kds') && (can('kds.access') || userRole === 'chef')"
                        :href="route('kds.index')" 
                        :class="route().current('kds.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <ChefHat class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Ekran Kuchenny KDS</span>
                    </Link>

                    <!-- LISTA ZAMÓWIEŃ -->
                    <Link 
                        v-if="can('orders.view')"
                        :href="route('admin.orders.index')" 
                        :class="route().current('admin.orders.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <ShoppingBag class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Lista Zamówień</span>
                    </Link>

                    <!-- DASHBOARD FINANSOWY -->
                    <Link 
                        v-if="can('dashboard.financial')"
                        :href="route('manager.dashboard')" 
                        :class="route().current('manager.dashboard') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <TrendingUp class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Dashboard Finansowy</span>
                    </Link>

                    <!-- KREATORY MENU I RECEPTURY BOM -->
                    <Link 
                        v-if="can('products.manage')"
                        :href="route('manager.products.index')" 
                        :class="route().current('manager.products.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <Pizza class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Kreator Menu</span>
                    </Link>

                    <!-- GOSPODARKA MAGAZYNOWA SUROWCÓW -->
                    <Link 
                        v-if="hasFeature('inventory_bom') && can('inventory.manage')"
                        :href="route('manager.inventory')" 
                        :class="route().current('manager.inventory*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <Package class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Magazyn Surowców & BOM</span>
                    </Link>

                    <!-- PROGRAM LOJALNOŚCIOWY -->
                    <Link 
                        v-if="hasFeature('loyalty') && can('loyalty.manage')"
                        :href="route('manager.loyalty.index')" 
                        :class="route().current('manager.loyalty.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <Gift class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Program Lojalnościowy</span>
                    </Link>

                    <!-- PROMOCJE I GRATISY -->
                    <Link 
                        v-if="can('promotions.manage')"
                        :href="route('manager.promotions.index')" 
                        :class="route().current('manager.promotions.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <Tag class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Promocje & Gratisy</span>
                    </Link>

                    <!-- ROZSUWANE SUBMENU: ZARZĄDZANIE ZESPOŁEM -->
                    <div v-if="canAny(['users.manage', 'rcp.view'])" class="space-y-1">
                        <button 
                            @click="toggleTeamMenu"
                            :class="route().current('admin.users.*') || route().current('admin.rcp.*') ? 'text-white font-bold bg-slate-800/80 border-slate-700' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all cursor-pointer"
                        >
                            <div class="flex items-center space-x-3">
                                <Users class="w-4 h-4 text-amber-500 shrink-0" />
                                <span>Zarządzanie Zespołem</span>
                            </div>
                            <component :is="isTeamMenuOpen ? ChevronDown : ChevronRight" class="w-4 h-4 text-slate-500" />
                        </button>

                        <div v-show="isTeamMenuOpen" class="pl-4 space-y-1 pt-1 border-l-2 border-slate-800 ml-3">
                            <Link 
                                v-if="can('users.manage')"
                                :href="route('admin.users.index')"
                                :class="route().current('admin.users.*') ? 'bg-red-600 text-white font-bold border-red-500' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                                class="w-full flex items-center space-x-2 px-3 py-2 rounded-lg text-[11px] uppercase tracking-wider border transition-all"
                            >
                                <UserCheck class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                <span>Lista Pracowników</span>
                            </Link>

                            <Link 
                                v-if="hasFeature('rcp') && can('rcp.view')"
                                :href="route('admin.rcp.index')"
                                :class="route().current('admin.rcp.*') ? 'bg-red-600 text-white font-bold border-red-500' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                                class="w-full flex items-center space-x-2 px-3 py-2 rounded-lg text-[11px] uppercase tracking-wider border transition-all"
                            >
                                <Clock class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                <span>Ewidencja Czasu Pracy (RCP)</span>
                            </Link>
                        </div>
                    </div>

                    <!-- ROZLICZENIA KURIERÓW -->
                    <Link 
                        v-if="hasFeature('delivery') && can('reconciliation.view')"
                        :href="route('manager.reconciliation.index')" 
                        :class="route().current('manager.reconciliation.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <Banknote class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Rozliczenia Kurierów</span>
                    </Link>

                    <!-- STREFY DOSTAW -->
                    <Link 
                        v-if="hasFeature('delivery') && can('delivery_zones.manage')"
                        :href="route('manager.delivery_zones.index')" 
                        :class="route().current('manager.delivery_zones.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <Map class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Strefy Dostaw</span>
                    </Link>

                    <!-- USTAWIENIA GLOBALNE -->
                    <Link 
                        v-if="canAny(['settings.general', 'settings.discounts', 'settings.payments'])"
                        :href="route('admin.settings.edit')" 
                        :class="route().current('admin.settings.*') ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent'"
                        class="w-full flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs uppercase tracking-wider border transition-all"
                    >
                        <Settings class="w-4 h-4 text-amber-500 shrink-0" />
                        <span>Ustawienia Globalne</span>
                    </Link>
                </nav>
            </div>

            <!-- PRZYCISK WYLOGOWANIA -->
            <div class="pt-4 border-t border-slate-800 mt-6">
                <Link :href="route('logout')" method="post" as="button" class="w-full bg-[#0B0F19] hover:bg-red-950/40 text-red-400 border border-slate-800 hover:border-red-900 text-xs font-bold py-2.5 rounded-xl uppercase tracking-wider transition flex items-center justify-center space-x-2 cursor-pointer">
                    <LogOut class="w-4 h-4" />
                    <span>Wyloguj pracownika</span>
                </Link>
            </div>
        </aside>

        <!-- WIDOK GŁÓWNY -->
        <main class="flex-1 overflow-y-auto max-h-screen flex flex-col">
            
            <!-- BANER INFORMACYJNY O 14-DNIOWYM OKRESIE PRÓBNYM (TRIAL) -->
            <div 
                v-if="isTrialing" 
                class="bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent border-b border-amber-500/30 px-6 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs shrink-0 shadow-lg"
            >
                <div class="flex items-center space-x-2.5">
                    <div class="p-1.5 bg-amber-500/20 border border-amber-500/40 rounded-lg shrink-0">
                        <Sparkles class="w-4 h-4 text-amber-400 animate-pulse" />
                    </div>
                    <div>
                        <span class="text-white font-bold">Korzystasz z 14-dniowego okresu próbnego!</span>
                        <span class="text-amber-200/80 ml-1">
                            Pozostało jeszcze <strong>{{ daysLeft }} dni</strong> dostępu do pełnego pakietu <strong>{{ subscription.plan_name }}</strong>.
                        </span>
                    </div>
                </div>

                <!-- PRZYCISK AKTYWACJI Z PODPIĘTĄ METODĄ REDIRECT -->
                <button 
                    type="button"
                    @click="redirectToStripe"
                    class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-black px-4 py-2 rounded-xl transition-all shadow-md shadow-amber-500/20 text-xs tracking-wider uppercase shrink-0 cursor-pointer flex items-center space-x-1"
                >
                    <span>Aktywuj pełną subskrypcję</span>
                </button>
            </div>

            <!-- PASEK GÓRNY GDY SIDEBAR JEST UKRYTY -->
            <header v-if="hideSidebar" class="bg-slate-900 border-b border-slate-800 px-6 py-3 flex items-center justify-between shrink-0 shadow-lg">
                <div class="flex items-center space-x-3">
                    <span class="text-lg font-black text-white tracking-wider">{{ restaurantName }}</span>
                    <span class="text-[9px] bg-amber-500 text-slate-950 px-2 py-0.5 rounded-full font-bold uppercase">erp</span>
                </div>

                <div class="flex items-center space-x-4">
                    <div v-if="hasFeature('rcp')" class="bg-[#0B0F19] border border-slate-800 px-3.5 py-1.5 rounded-2xl flex items-center space-x-3 shadow-inner">
                        <div class="flex items-center space-x-2">
                            <span v-if="activeShift?.status === 'working'" class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <Clock class="w-4 h-4 text-amber-500" />
                            <div class="flex flex-col">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-500">Czas pracy</span>
                                <span class="text-xs font-mono font-black text-white">{{ elapsedTime }}</span>
                            </div>
                        </div>
                        
                        <div class="h-6 w-px bg-slate-800"></div>

                        <div class="flex items-center space-x-1.5">
                            <button 
                                v-if="!activeShift" 
                                @click="startShift" 
                                class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider transition flex items-center space-x-1 cursor-pointer"
                            >
                                <Play class="w-3.5 h-3.5 fill-current" />
                                <span>Start Pracy</span>
                            </button>

                            <template v-else>
                                <button 
                                    @click="pauseShift" 
                                    :class="activeShift.status === 'on_break' ? 'bg-amber-500 text-black' : 'bg-slate-800 text-amber-400 hover:bg-slate-700'"
                                    class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
                                    :title="activeShift.status === 'on_break' ? 'Wznów pracę' : 'Rozpocznij przerwę'"
                                >
                                    <Pause class="w-3.5 h-3.5 fill-current" />
                                </button>

                                <button 
                                    @click="stopShift" 
                                    class="bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white border border-red-500/30 px-2.5 py-1 rounded-xl text-xs font-bold uppercase tracking-wider transition flex items-center space-x-1 cursor-pointer"
                                >
                                    <Square class="w-3.5 h-3.5 fill-current" />
                                    <span>Koniec</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 border-l border-slate-800 pl-4">
                        <span class="text-xs font-bold text-white hidden sm:inline">{{ user?.name }}</span>
                        <Link :href="route('logout')" method="post" as="button" class="text-slate-400 hover:text-red-400 p-1.5 rounded-xl hover:bg-slate-800 transition" title="Wyloguj">
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </header>

            <div class="flex-1">
                <slot />
            </div>
        </main>

    </div>
</template>