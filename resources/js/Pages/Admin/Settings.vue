<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    MapPin, Ticket, Bell, CreditCard, ShieldCheck, Tag, Sparkles, 
    CheckCircle2, Lock, ArrowUpRight, Loader2, FileText 
} from 'lucide-vue-next';

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
    isEcommerceActive: { type: Boolean, default: true },
    freeDeliveryEnabled: { type: Boolean, default: false },
    freeDeliveryMinAmount: { type: [Number, String], default: 60.00 },
    upsellEnabled: { type: Boolean, default: true },
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
    authRole: { type: String, default: 'admin' },
    
    // 💳 Dane o planie subskrypcji oraz historii faktur
    subscription: { type: Object, default: () => null }
});

const activeTab = ref('general');
const isProcessingPayment = ref(false);

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

// Pomocnicze etykiety funkcji w czytelnym języku polskim
const featureLabels = {
    shop: 'Sklep E-Commerce & Zamówienia Online',
    pos: 'System POS do przyjmowania zamówień w lokalu',
    kds: 'Ekran Kuchenny (KDS)',
    delivery: 'Moduł i Aplikacja dla Kurierów',
    inventory_bom: 'Magazyn & Receptury BOM',
    loyalty: 'Program Lojalnościowy i Kody Rabatowe',
    rcp: 'Rejestracja Czasu Pracy (RCP)',
    multi_location: 'Wsparcie dla wielu lokalizacji',
    custom_domain: 'Własna domena (np. mojapizzeria.pl)',
};

const allPossibleFeatures = [
    'shop', 'pos', 'kds', 'delivery', 'inventory_bom', 'loyalty', 'rcp', 'multi_location', 'custom_domain'
];

// Obsługa inicjalizacji sesji Stripe Checkout
const handleCheckout = async (planId) => {
    isProcessingPayment.value = true;
    try {
        const response = await axios.post(route('tenant.subscription.checkout'), { plan_id: planId });
        if (response.data?.url) {
            window.location.href = response.data.url;
        }
    } catch (error) {
        alert(error.response?.data?.message || 'Błąd inicjalizacji płatności. Skontaktuj się z obsługą.');
    } finally {
        isProcessingPayment.value = false;
    }
};
</script>

<template>
    <Head title="Ustawienia Systemu - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="border-b border-slate-800 pb-4">
                <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Ustawienia Globalne Systemu</h1>
                <p class="text-xs text-slate-400 mt-0.5">Centrum konfiguracji parametrów pizzerii, e-commerce, promocji, powiadomień, płatności i uprawnień.</p>
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

                <!-- ZAKŁADKA SUBSKRYPCJI (Dostępna dla admina) -->
                <button 
                    v-if="authRole === 'admin' && subscription"
                    @click="activeTab = 'subscription'"
                    :class="activeTab === 'subscription' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <Sparkles class="w-4 h-4 text-amber-500" />
                    <span>Subskrypcja & Plan</span>
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

                <!-- BEZPOŚREDNI ODNOŚNIK DO MODUŁU PROMOCJI I GRATISÓW -->
                <Link 
                    v-if="hasPermission('settings.discounts')"
                    :href="route('manager.promotions.index')"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:border-amber-500/50"
                >
                    <Tag class="w-4 h-4 text-amber-500" />
                    <span>Promocje & Gratisy</span>
                </Link>

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
                    :is-ecommerce-active="isEcommerceActive"
                    :free-delivery-enabled="freeDeliveryEnabled"
                    :free-delivery-min-amount="freeDeliveryMinAmount"
                    :upsell-enabled="upsellEnabled"
                    :current-gateway="currentGateway"
                    :payu-env="payuEnv"
                    :payu-pos-id="payuPosId"
                    :payu-client-id="payuClientId"
                    :payu-client-secret="payuClientSecret"
                    :payu-second-key="payuSecondKey"
                />

                <!-- SEKCJA SUBSKRYPCJI TENANTA -->
                <div v-if="activeTab === 'subscription' && subscription" class="space-y-6">
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
                        
                        <!-- NAGŁÓWEK SUBSKRYPCJI -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-6 gap-4">
                            <div>
                                <div class="flex items-center space-x-3 mb-1">
                                    <h2 class="text-2xl font-black text-white">Plan: {{ subscription.plan_name }}</h2>
                                    <span :class="[
                                        'px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border',
                                        subscription.status === 'active' 
                                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                                            : 'bg-red-500/10 text-red-400 border-red-500/20'
                                    ]">
                                        {{ subscription.status === 'active' ? 'Aktywny' : 'Wygaśnięta' }}
                                    </span>
                                </div>
                                <p class="text-slate-400 text-sm">
                                    Miesięczny koszt opłaty abonamentowej: <span class="text-amber-400 font-bold font-mono">{{ subscription.price_monthly }} zł / mies.</span>
                                </p>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-left md:text-right">
                                    <div class="text-[10px] text-slate-500 uppercase font-bold mb-0.5">Ważność subskrypcji</div>
                                    <div class="text-sm font-mono font-bold text-amber-500">
                                        {{ subscription.ends_at }}
                                    </div>
                                </div>

                                <!-- 📄 PRZYCISK POBIERANIA BIEŻĄCEJ FAKTURY PDF -->
                                <a 
                                    :href="route('admin.subscription.invoice.download', { invoice: 'latest' })"
                                    target="_blank"
                                    class="px-4 py-3.5 bg-slate-800 hover:bg-slate-700 text-amber-400 border border-amber-500/30 font-bold rounded-xl text-xs uppercase tracking-wider transition flex items-center space-x-2 cursor-pointer shrink-0"
                                    title="Pobierz fakturę za subskrypcję w PDF"
                                >
                                    <FileText class="w-4 h-4 text-amber-400" />
                                    <span>Faktura PDF</span>
                                </a>

                                <button 
                                    @click="handleCheckout(subscription.plan_id || 1)"
                                    :disabled="isProcessingPayment"
                                    class="px-5 py-3.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider transition shadow-lg shadow-amber-500/10 flex items-center space-x-2 disabled:opacity-50 cursor-pointer"
                                >
                                    <Loader2 v-if="isProcessingPayment" class="w-4 h-4 animate-spin" />
                                    <ArrowUpRight v-else class="w-4 h-4" />
                                    <span>{{ subscription.status === 'active' ? 'Odnów Plan' : 'Opłać Subskrypcję' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- MODUŁY W PLANIE -->
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Moduły i funkcje przydzielone do konta:</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div 
                                    v-for="featKey in allPossibleFeatures" 
                                    :key="featKey"
                                    :class="[
                                        'p-4 rounded-xl border flex items-center justify-between text-xs font-bold transition',
                                        subscription.features?.includes(featKey)
                                            ? 'bg-slate-950 border-slate-800 text-white'
                                            : 'bg-slate-950/40 border-slate-900 text-slate-600 opacity-60'
                                    ]"
                                >
                                    <span>{{ featureLabels[featKey] || featKey }}</span>
                                    <span v-if="subscription.features?.includes(featKey)" class="flex items-center text-emerald-400 font-bold">
                                        <CheckCircle2 class="w-4 h-4 mr-1 text-emerald-400" />
                                        Dostępny
                                    </span>
                                    <span v-else class="flex items-center text-slate-600 font-semibold">
                                        <Lock class="w-3.5 h-3.5 mr-1 text-slate-600" />
                                        Brak w planie
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- SEKCJA HISTORII FAKTUR VAT -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center space-x-2">
                                    <FileText class="w-5 h-5 text-amber-500" />
                                    <span>Historia Faktur Subskrypcyjnych</span>
                                </h3>
                                <p class="text-xs text-slate-400 mt-0.5">Pobierz archiwalne faktury VAT za opłacone okresy rozliczeniowe.</p>
                            </div>
                        </div>

                        <!-- TABELA FAKTUR -->
                        <div v-if="subscription.invoices && subscription.invoices.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-950 text-slate-400 font-bold uppercase border-b border-slate-800">
                                    <tr>
                                        <th class="p-3">Numer Faktury</th>
                                        <th class="p-3">Wykupiony Plan</th>
                                        <th class="p-3">Data Opłacenia</th>
                                        <th class="p-3">Kwota Brutto</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3 text-right">Dokument</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800">
                                    <tr v-for="invoice in subscription.invoices" :key="invoice.id" class="hover:bg-slate-800/50 transition">
                                        <td class="p-3 font-mono font-bold text-white">{{ invoice.number }}</td>
                                        <td class="p-3 text-amber-400 font-semibold">{{ invoice.plan_name }}</td>
                                        <td class="p-3 font-mono text-slate-400">{{ invoice.paid_at }}</td>
                                        <td class="p-3 font-mono font-bold text-white">{{ Number(invoice.amount_gross).toFixed(2) }} zł</td>
                                        <td class="p-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                Opłacona
                                            </span>
                                        </td>
                                        <td class="p-3 text-right">
                                            <a 
                                                :href="route('admin.subscription.invoice.download', { invoice: invoice.id })"
                                                target="_blank"
                                                class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-400 border border-amber-500/30 font-bold rounded-lg text-xs transition inline-flex items-center space-x-1.5 cursor-pointer"
                                            >
                                                <FileText class="w-3.5 h-3.5" />
                                                <span>Pobierz PDF</span>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- KOMUNIKAT BRAKU FAKTUR -->
                        <div v-else class="p-6 bg-slate-950/60 rounded-xl border border-slate-800/80 text-center space-y-3">
                            <p class="text-xs text-slate-400">Brak zarejestrowanych historycznych faktur w bazie systemowej.</p>
                            <div>
                                <a 
                                    :href="route('admin.subscription.invoice.download', { invoice: 'latest' })"
                                    target="_blank"
                                    class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-xs transition inline-flex items-center space-x-2 cursor-pointer shadow-lg shadow-amber-500/10"
                                >
                                    <FileText class="w-4 h-4" />
                                    <span>Pobierz Bieżącą Fakturę VAT (PDF)</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

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