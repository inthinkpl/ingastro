<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { MapPin, Ticket, Bell, CreditCard, ShieldCheck } from 'lucide-vue-next';

// Komponenty cząstkowe
import GeneralSettingsForm from './Partials/GeneralSettingsForm.vue';
import DiscountCodesManager from './Partials/DiscountCodesManager.vue';
import PushNotificationsSettings from './Partials/PushNotificationsSettings.vue';
import PaymentGatewaySettings from './Partials/PaymentGatewaySettings.vue';
import RolePermissionsMatrix from './Partials/RolePermissionsMatrix.vue';

const props = defineProps({
    restaurantName: { type: String, default: 'Pizzeria Savona' },
    restaurantPhone: { type: String, default: '' },
    restaurantAddress: { type: String, default: '' },
    minOrderAmount: { type: [Number, String], default: 40.00 },
    currentGateway: { type: String, default: 'simulation' },
    payuEnv: { type: String, default: 'sandbox' },
    payuPosId: { type: String, default: '' },
    payuClientId: { type: String, default: '' },
    payuClientSecret: { type: String, default: '' },
    payuSecondKey: { type: String, default: '' },
    discountCodes: { type: Array, default: () => [] },
    notificationSettings: { type: Array, default: () => [] },
    availablePermissions: { type: Object, default: () => ({}) },
    rolePermissions: { type: Object, default: () => ({}) },
    authRole: { type: String, default: 'admin' }
});

const activeTab = ref('general');

const hasPermission = (permKey) => {
    if (props.authRole === 'admin') return true;
    return props.rolePermissions?.[props.authRole]?.includes(permKey) || false;
};

onMounted(() => {
    if (!hasPermission('settings.general')) {
        if (hasPermission('settings.discounts')) activeTab.value = 'discounts';
        else if (hasPermission('settings.notifications')) activeTab.value = 'notifications';
        else if (hasPermission('settings.payments')) activeTab.value = 'payments';
    }
});
</script>

<template>
    <Head title="Ustawienia Systemu - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="border-b border-slate-800 pb-4">
                <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Ustawienia Globalne Systemu</h1>
                <p class="text-xs text-slate-400 mt-0.5">Centrum konfiguracji parametrów pizzerii, promocji, powiadomień, płatności i uprawnień.</p>
            </header>

            <!-- PASEK ZAKŁADEK -->
            <div class="flex flex-wrap gap-2 border-b border-slate-800 pb-3">
                <button 
                    v-if="hasPermission('settings.general')"
                    @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <MapPin class="w-4 h-4 text-amber-500" />
                    <span>Wizytówka & Zasady</span>
                </button>

                <button 
                    v-if="hasPermission('settings.discounts')"
                    @click="activeTab = 'discounts'"
                    :class="activeTab === 'discounts' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <Ticket class="w-4 h-4 text-amber-500" />
                    <span>Kody Rabatowe</span>
                    <span v-if="discountCodes.length > 0" class="bg-[#0B0F19] text-amber-400 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold">
                        {{ discountCodes.length }}
                    </span>
                </button>

                <button 
                    v-if="hasPermission('settings.notifications')"
                    @click="activeTab = 'notifications'"
                    :class="activeTab === 'notifications' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <Bell class="w-4 h-4 text-amber-500" />
                    <span>Powiadomienia Push</span>
                </button>

                <button 
                    v-if="hasPermission('settings.payments')"
                    @click="activeTab = 'payments'"
                    :class="activeTab === 'payments' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <CreditCard class="w-4 h-4 text-amber-500" />
                    <span>Płatności</span>
                </button>

                <button 
                    v-if="authRole === 'admin'"
                    @click="activeTab = 'permissions'"
                    :class="activeTab === 'permissions' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <ShieldCheck class="w-4 h-4 text-amber-500" />
                    <span>Uprawnienia Ról</span>
                </button>
            </div>

            <!-- DYNAMICZNIE ŁADOWANE SEKCJE (PARTIALS) -->
            <main>
                <GeneralSettingsForm 
                    v-if="activeTab === 'general' && hasPermission('settings.general')"
                    :restaurant-name="restaurantName"
                    :restaurant-phone="restaurantPhone"
                    :restaurant-address="restaurantAddress"
                    :min-order-amount="minOrderAmount"
                />

                <DiscountCodesManager 
                    v-if="activeTab === 'discounts' && hasPermission('settings.discounts')"
                    :discount-codes="discountCodes"
                />

                <PushNotificationsSettings 
                    v-if="activeTab === 'notifications' && hasPermission('settings.notifications')"
                    :notification-settings="notificationSettings"
                />

                <PaymentGatewaySettings 
                    v-if="activeTab === 'payments' && hasPermission('settings.payments')"
                    :current-gateway="currentGateway"
                    :payu-env="payuEnv"
                    :payu-pos-id="payuPosId"
                    :payu-client-id="payuClientId"
                    :payu-client-secret="payuClientSecret"
                    :payu-second-key="payuSecondKey"
                />

                <RolePermissionsMatrix 
                    v-if="activeTab === 'permissions' && authRole === 'admin'"
                    :available-permissions="availablePermissions"
                    :role-permissions="rolePermissions"
                />
            </main>

        </div>
    </AuthenticatedLayout>
</template>