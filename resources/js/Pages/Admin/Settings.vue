<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    MapPin, Ticket, Bell, CreditCard, ShieldCheck, Tag, Sparkles, 
    CheckCircle2, Lock, ArrowUpRight, Loader2, FileText, Check, Star,
    ShoppingCart, Pizza, ChefHat, Truck, PackageCheck, Gift, Clock, Calculator
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
    
    // 💳 Dane subskrypcji oraz pobrana lista modułów z cennika w bazie centralnej
    subscription: { type: Object, default: () => null },
    allModules: { type: Array, default: () => [] }
});

const activeTab = ref('general');
const isProcessingPayment = ref(false);

// Mapowanie ikonek dla modułów
const moduleIcons = {
    pos: ShoppingCart,
    shop: Pizza,
    kds: ChefHat,
    delivery: Truck,
    inventory_bom: PackageCheck,
    loyalty: Gift,
    rcp: Clock,
};

// Aktualnie wybrane klucze modułów w kalkulatorze (domyślnie obecne moduły tenanta lub pusta tablica)
const selectedModuleKeys = ref(props.subscription?.features || ['pos', 'shop', 'kds']);

// Włączanie/wyłączanie modułu w kalkulatorze
const toggleModule = (key) => {
    if (selectedModuleKeys.value.includes(key)) {
        if (selectedModuleKeys.value.length === 1) {
            alert('Musisz wybrać przynajmniej jeden moduł.');
            return;
        }
        selectedModuleKeys.value = selectedModuleKeys.value.filter(k => k !== key);
    } else {
        selectedModuleKeys.value.push(key);
    }
};

// Obliczanie łącznej kwoty netto miesięcznie na żywo
const calculatedTotalPrice = computed(() => {
    if (!props.allModules || props.allModules.length === 0) return 0;
    return props.allModules
        .filter(m => selectedModuleKeys.value.includes(m.key))
        .reduce((sum, m) => sum + Number(m.price_monthly || 0), 0);
});

const hasPermission = (permKey) => {
    if (props.authRole === 'admin') return true;
    return props.rolePermissions?.[props.authRole]?.includes(permKey) || false;
};

// 💳 INICJALIZACJA STRIPE CHECKOUT DLA SKOMPONOWANEGO ZESTAWU MODUŁÓW
const handleCheckoutModules = async () => {
    if (selectedModuleKeys.value.length === 0) {
        alert('Wybierz co najmniej jeden moduł, aby przejść do płatności.');
        return;
    }

    if (isProcessingPayment.value) return;
    isProcessingPayment.value = true;

    try {
        const response = await axios.post('/subscription/checkout', { 
            modules: selectedModuleKeys.value 
        });

        if (response.data?.url) {
            window.location.assign(response.data.url);
        } else {
            alert('Nie udało się wygenerować sesji płatności Stripe.');
        }
    } catch (error) {
        console.error('Błąd Stripe Checkout:', error);
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

                <!-- ZAKŁADKA SUBSKRYPCJI -->
                <button 
                    v-if="authRole === 'admin' && subscription"
                    @click="activeTab = 'subscription'"
                    :class="activeTab === 'subscription' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <Sparkles class="w-4 h-4 text-amber-500" />
                    <span>Subskrypcja & Moduły</span>
                </button>

                <button 
                    v-if="hasPermission('settings.discounts')"
                    @click="activeTab = 'discounts'"
                    :class="activeTab === 'discounts' ? 'bg-red-600 text-white font-bold border-red-500 shadow-lg shadow-red-600/30' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2 cursor-pointer"
                >
                    <Ticket class="w-4 h-4 text-amber-500" />
                    <span>Kody Rabatowe</span>
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

            <!-- DYNAMICZNIE ŁADOWANE SEKCJE -->
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

                <!-- 💳 SEKCJA SUBSKRYPCJI Z KALKULATOREM MODUŁÓW (A LA CARTE) -->
                <div v-if="activeTab === 'subscription' && subscription" class="space-y-6">
                    
                    <!-- KARTA STATUSU KONTROLNEGO -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h2 class="text-lg font-black text-white">Status Abonamentu</h2>
                                    <span :class="[
                                        'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border',
                                        subscription.status === 'active' 
                                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                                            : 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                                    ]">
                                        {{ subscription.status === 'active' ? 'Aktywny' : subscription.status === 'trialing' ? 'Okres Próbny' : 'Wygaśnięta' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Ważność do: <span class="text-amber-400 font-mono font-bold">{{ subscription.ends_at }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- KALKULATOR MODUŁÓW -->
                        <div class="space-y-4 pt-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wider flex items-center space-x-2">
                                        <Calculator class="w-4 h-4 text-amber-500" />
                                        <span>Skomponuj własny zestaw modułów:</span>
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">Klikaj w moduły poniżej, aby je dodać lub usunąć z miesięcznej subskrypcji.</p>
                                </div>
                            </div>

                            <!-- SIATKA MODUŁÓW CENNIKA -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div 
                                    v-for="mod in allModules" 
                                    :key="mod.id"
                                    @click="toggleModule(mod.key)"
                                    :class="[
                                        'p-4 rounded-xl border transition-all cursor-pointer flex flex-col justify-between space-y-3 relative',
                                        selectedModuleKeys.includes(mod.key)
                                            ? 'bg-amber-500/10 border-amber-500 text-white shadow-lg shadow-amber-500/5'
                                            : 'bg-slate-950/60 border-slate-800 text-slate-500 opacity-60 hover:opacity-100'
                                    ]"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <div class="p-2 bg-slate-900 rounded-lg text-amber-400 border border-slate-800">
                                                <component :is="moduleIcons[mod.key] || Sparkles" class="w-4 h-4" />
                                            </div>
                                            <span class="text-xs font-bold text-white">{{ mod.name }}</span>
                                        </div>

                                        <span class="text-xs font-mono font-bold text-amber-400">
                                            +{{ Number(mod.price_monthly).toFixed(2) }} zł
                                        </span>
                                    </div>

                                    <p class="text-[11px] text-slate-400 leading-relaxed">{{ mod.description }}</p>

                                    <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
                                        <span class="text-[10px] font-bold uppercase tracking-wider" :class="selectedModuleKeys.includes(mod.key) ? 'text-emerald-400' : 'text-slate-500'">
                                            {{ selectedModuleKeys.includes(mod.key) ? 'Wybrany' : 'Kliknij aby dodać' }}
                                        </span>
                                        <div class="w-4 h-4 rounded-md flex items-center justify-center border" :class="selectedModuleKeys.includes(mod.key) ? 'bg-amber-500 border-amber-500 text-slate-950' : 'border-slate-700 bg-slate-900'">
                                            <Check v-if="selectedModuleKeys.includes(mod.key)" class="w-3 h-3 stroke-[3]" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PODSUMOWANIE MIESIĘCZNE I PRZYCISK PŁATNOŚCI STRIPE -->
                            <div class="p-5 bg-slate-950 rounded-2xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 mt-6">
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Miesięczny koszt Twojego zestawu:</div>
                                    <div class="text-2xl font-black font-mono text-amber-400">
                                        {{ calculatedTotalPrice.toFixed(2) }} <span class="text-xs text-slate-400 font-sans font-normal">zł / mies. netto</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Liczba wybranych modułów: <strong class="text-white">{{ selectedModuleKeys.length }}</strong></div>
                                </div>

                                <button 
                                    @click="handleCheckoutModules"
                                    :disabled="isProcessingPayment || selectedModuleKeys.length === 0"
                                    class="w-full sm:w-auto px-6 py-3.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider transition shadow-lg shadow-amber-500/20 disabled:opacity-50 flex items-center justify-center space-x-2 cursor-pointer"
                                >
                                    <Loader2 v-if="isProcessingPayment" class="w-4 h-4 animate-spin" />
                                    <ArrowUpRight v-else class="w-4 h-4" />
                                    <span>Opłać Zestaw Modułów</span>
                                </button>
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
                                        <th class="p-3">Wykupiony Plan / Zestaw</th>
                                        <th class="p-3">Data Opłacenia</th>
                                        <th class="p-3">Kwota Brutto</th>
                                        <th class="p-3">Status</th>
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
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="p-6 bg-slate-950/60 rounded-xl border border-slate-800/80 text-center space-y-3">
                            <p class="text-xs text-slate-400">Brak zarejestrowanych historycznych faktur w bazie systemowej.</p>
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