<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Odbieramy aktualne wartości z bazy danych przekazane przez kontroler jako props
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
    availablePermissions: { type: Object, default: () => ({}) },
    rolePermissions: { type: Object, default: () => ({}) },
    authRole: { type: String, default: 'admin' }
});

// STAN POD-MENU / ZAKŁADEK
const activeTab = ref('general');

// Pomocnicza funkcja sprawdzająca uprawnienia zalogowanego użytkownika
const hasPermission = (permKey) => {
    if (props.authRole === 'admin') return true;
    return props.rolePermissions?.[props.authRole]?.includes(permKey) || false;
};

// Automatyczne ustawienie aktywnej zakładki przy starcie, jeśli użytkownik nie ma dostępu do 'general'
onMounted(() => {
    if (!hasPermission('settings.general')) {
        if (hasPermission('settings.discounts')) {
            activeTab.value = 'discounts';
        } else if (hasPermission('settings.payments')) {
            activeTab.value = 'payments';
        }
    }
});

// Formularz ogólny ustawień ERP (wizytówka + zasady + płatności)
const form = useForm({
    restaurant_name: props.restaurantName,
    restaurant_phone: props.restaurantPhone,
    restaurant_address: props.restaurantAddress,
    min_order_amount: props.minOrderAmount,
    payment_gateway: props.currentGateway,
    payu_env: props.payuEnv,
    payu_pos_id: props.payuPosId,
    payu_client_id: props.payuClientId,
    payu_client_secret: props.payuClientSecret,
    payu_second_key: props.payuSecondKey
});

// Formularz dodawania nowego kodu rabatowego
const discountForm = useForm({
    code: '',
    type: 'percent',
    value: 10,
    min_order_amount: 0,
    expires_at: ''
});

// Inicjalizacja macierzy uprawnień dla poszczególnych ról
const permissionsMatrix = ref({
    manager: props.rolePermissions?.manager || ['settings.general', 'settings.discounts', 'products.manage', 'inventory.manage', 'reconciliation.view', 'delivery_zones.manage'],
    staff: props.rolePermissions?.staff || [],
    chef: props.rolePermissions?.chef || [],
    driver: props.rolePermissions?.driver || []
});

// Przełączanie uprawnienia w macierzy (checkbox)
const togglePermission = (role, permKey) => {
    if (!permissionsMatrix.value[role]) {
        permissionsMatrix.value[role] = [];
    }
    const index = permissionsMatrix.value[role].indexOf(permKey);
    if (index > -1) {
        permissionsMatrix.value[role].splice(index, 1);
    } else {
        permissionsMatrix.value[role].push(permKey);
    }
};

// Zapis macierzy uprawnień do bazy
const savePermissions = () => {
    router.post(route('admin.permissions.update'), {
        matrix: permissionsMatrix.value
    }, {
        onSuccess: () => alert('Macierz uprawnień ról została pomyślnie zapisana!')
    });
};

// Zapis ustawień globalnych
const saveSettings = () => {
    form.post(route('admin.settings.save'), {
        onSuccess: () => {
            alert('Ustawienia zostały pomyślnie zaktualizowane!');
        }
    });
};

// Dodanie nowego kodu rabatowego
const createDiscountCode = () => {
    discountForm.post(route('admin.discount-codes.store'), {
        onSuccess: () => {
            discountForm.reset();
            alert('Nowy kod rabatowy został utworzony!');
        }
    });
};

// Przełączenie statusu aktywacji kodu
const toggleDiscountCode = (id) => {
    router.patch(route('admin.discount-codes.toggle', id), {}, {
        preserveScroll: true
    });
};

// Usuwanie kodu rabatowego
const deleteDiscountCode = (id) => {
    if (confirm('Czy na pewno chcesz usunąć ten kod rabatowy?')) {
        router.delete(route('admin.discount-codes.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-5xl mx-auto space-y-6 font-sans">
            
            <!-- NAGŁÓWEK PANELU ADMINA -->
            <header class="border-b border-slate-800 pb-4">
                <h1 class="text-xl font-black text-orange-400 uppercase tracking-wider">Ustawienia Globalne Systemu</h1>
                <p class="text-xs text-slate-500 mt-0.5">Centrum konfiguracji parametrów pizzerii, promocji, bramek płatności i uprawnień.</p>
            </header>

            <!-- 🎛️ POD-MENU / POD-NAWIGACJA (TABS) Z WARUNKOWYM DOSTĘPEM -->
            <div class="flex flex-wrap gap-2 border-b border-slate-850 pb-3">
                
                <!-- Zakładka 1: Wizytówka i Zasady Zamówień -->
                <button 
                    v-if="hasPermission('settings.general')"
                    @click="activeTab = 'general'"
                    :class="activeTab === 'general' 
                        ? 'bg-orange-600 text-white font-black border-orange-500 shadow-lg shadow-orange-950/40' 
                        : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:bg-slate-850'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2"
                >
                    <span>📍</span> <span>Wizytówka i Zasady Zamówień</span>
                </button>

                <!-- Zakładka 2: Kody Rabatowe -->
                <button 
                    v-if="hasPermission('settings.discounts')"
                    @click="activeTab = 'discounts'"
                    :class="activeTab === 'discounts' 
                        ? 'bg-orange-600 text-white font-black border-orange-500 shadow-lg shadow-orange-950/40' 
                        : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:bg-slate-850'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2"
                >
                    <span>🎟️</span> <span>Kody Rabatowe</span>
                    <span v-if="discountCodes.length > 0" class="bg-slate-950 text-orange-400 px-2 py-0.5 rounded-md text-[10px] font-mono">
                        {{ discountCodes.length }}
                    </span>
                </button>

                <!-- Zakładka 3: Konfiguracja Płatności -->
                <button 
                    v-if="hasPermission('settings.payments')"
                    @click="activeTab = 'payments'"
                    :class="activeTab === 'payments' 
                        ? 'bg-orange-600 text-white font-black border-orange-500 shadow-lg shadow-orange-950/40' 
                        : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:bg-slate-850'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2"
                >
                    <span>💳</span> <span>Konfiguracja Płatności</span>
                </button>

                <!-- Zakładka 4: Macierz Uprawnień Ról (Dostępna tylko dla Admina) -->
                <button 
                    v-if="authRole === 'admin'"
                    @click="activeTab = 'permissions'"
                    :class="activeTab === 'permissions' 
                        ? 'bg-orange-600 text-white font-black border-orange-500 shadow-lg shadow-orange-950/40' 
                        : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:bg-slate-850'"
                    class="px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider font-bold border transition-all flex items-center space-x-2"
                >
                    <span>🔐</span> <span>Macierz Uprawnień Ról</span>
                </button>
            </div>

            <!-- ========================================================================= -->
            <!-- 📍 WIDOK 1: KONFIGURACJA WIZYTÓWKI I ZASAD ZAMÓWIENIOWYCH -->
            <!-- ========================================================================= -->
            <div v-if="activeTab === 'general' && hasPermission('settings.general')" class="space-y-6">
                <form @submit.prevent="saveSettings" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6 shadow-xl text-xs">
                    
                    <div class="space-y-4">
                        <h2 class="text-xs font-black uppercase tracking-widest text-slate-300 border-b border-slate-950 pb-2 flex items-center space-x-2">
                            <span>📍</span> <span>Dane Teleadresowe Lokalu</span>
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-400 uppercase mb-1">Nazwa Pizzerii</label>
                                <input v-model="form.restaurant_name" type="text" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white font-medium focus:border-orange-500 focus:ring-0" required />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-400 uppercase mb-1">Telefon Kontaktowy (Rezerwacje)</label>
                                <input v-model="form.restaurant_phone" type="text" placeholder="np. +48 500 600 700" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white font-mono focus:border-orange-500 focus:ring-0" />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">Adres Fizyczny Restauracji</label>
                            <input v-model="form.restaurant_address" type="text" placeholder="Ulica, numer domu, kod pocztowy, miasto" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white font-medium focus:border-orange-500 focus:ring-0" />
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-slate-850">
                        <h2 class="text-xs font-black uppercase tracking-widest text-slate-300 border-b border-slate-950 pb-2 flex items-center space-x-2">
                            <span>🛵</span> <span>Zasady Dostaw i Koszyka</span>
                        </h2>

                        <div>
                            <label class="block font-bold text-slate-400 uppercase mb-1">
                                Minimalna kwota zamówienia w dostawie (zł)
                            </label>
                            <div class="relative max-w-xs">
                                <input 
                                    v-model="form.min_order_amount" 
                                    type="number" 
                                    step="0.50" 
                                    min="0" 
                                    required 
                                    class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-emerald-400 font-mono font-bold text-sm focus:border-orange-500 focus:ring-0" 
                                />
                                <span class="absolute right-3 top-3 text-xs font-bold text-slate-500">PLN</span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1.5">
                                Zamówienia z opcją "Dostawa kurierem" w sklepie WWW wymagają osiągnięcia co najmniej tej wartości koszyka.
                            </p>
                        </div>
                    </div>

                    <!-- STOPKA Z ZAPISEM -->
                    <div class="flex justify-end pt-4 border-t border-slate-850">
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 text-white font-black px-8 py-3.5 rounded-xl uppercase tracking-wider transition-all shadow-md hover:scale-102 active:scale-98 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Zapisywanie danych...' : '💾 Zapisz Wizytówkę i Zasady' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================================= -->
            <!-- 🎟️ WIDOK 2: KODY RABATOWE -->
            <!-- ========================================================================= -->
            <div v-if="activeTab === 'discounts' && hasPermission('settings.discounts')" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6 shadow-xl text-xs">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-300 border-b border-slate-950 pb-2 flex items-center space-x-2">
                    <span>🎟️</span> <span>Generowanie i Zarządzanie KODAMI RABATOWYMI</span>
                </h2>

                <!-- FORMULARZ TWORZENIA KODU -->
                <form @submit.prevent="createDiscountCode" class="bg-slate-950 p-4 rounded-xl border border-slate-850 space-y-4">
                    <h3 class="font-bold text-orange-400 uppercase tracking-wider">Utwórz nowy kod promocyjny:</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kod (np. SAVONA20)</label>
                            <input v-model="discountForm.code" type="text" placeholder="KOD" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white uppercase font-mono font-bold focus:border-orange-500 focus:ring-0" required />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Typ Zniżki</label>
                            <select v-model="discountForm.type" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-orange-500 focus:ring-0">
                                <option value="percent">Procentowa (%)</option>
                                <option value="fixed">Kwotowa (zł)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Wartość (np. 10 lub 15.00)</label>
                            <input v-model="discountForm.value" type="number" step="0.01" min="0.01" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-emerald-400 font-mono font-bold focus:border-orange-500 focus:ring-0" required />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Min. wartość zamówienia (zł)</label>
                            <input v-model="discountForm.min_order_amount" type="number" step="0.50" min="0" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-orange-500 focus:ring-0" />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-end gap-3 pt-2">
                        <div class="w-full sm:w-1/2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Data wygaśnięcia (opcjonalnie)</label>
                            <input v-model="discountForm.expires_at" type="datetime-local" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-300 focus:border-orange-500 focus:ring-0" />
                        </div>

                        <button 
                            type="submit" 
                            :disabled="discountForm.processing"
                            class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-black px-6 py-2.5 rounded-xl uppercase tracking-wider text-xs transition-all shadow-md active:scale-95"
                        >
                            {{ discountForm.processing ? '...' : '+ Dodaj Kod Promocyjny' }}
                        </button>
                    </div>
                </form>

                <!-- TABELA ISTNIEJĄCYCH KODÓW -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950 text-slate-400 uppercase font-black text-[10px] tracking-wider border-b border-slate-850">
                                <th class="p-3">Kod</th>
                                <th class="p-3">Zniżka</th>
                                <th class="p-3">Min. Koszyk</th>
                                <th class="p-3">Wygasa</th>
                                <th class="p-3 text-center">Użycia</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3 text-right">Akcja</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/60">
                            <tr v-for="code in discountCodes" :key="code.id" class="hover:bg-slate-950/40 transition-colors">
                                <td class="p-3 font-mono font-black text-orange-400 uppercase">{{ code.code }}</td>
                                <td class="p-3 font-mono font-bold text-emerald-400">
                                    {{ code.type === 'percent' ? code.value + '%' : code.value + ' zł' }}
                                </td>
                                <td class="p-3 font-mono text-slate-300">{{ Number(code.min_order_amount).toFixed(2) }} zł</td>
                                <td class="p-3 text-slate-400 text-[11px]">
                                    {{ code.expires_at ? new Date(code.expires_at).toLocaleString() : 'Bezterminowy' }}
                                </td>
                                <td class="p-3 text-center font-mono font-bold text-slate-300">{{ code.times_used }}x</td>
                                <td class="p-3 text-center">
                                    <button 
                                        @click="toggleDiscountCode(code.id)"
                                        :class="code.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-red-950 text-red-400 border-red-900'"
                                        class="px-2.5 py-1 rounded-lg border text-[9px] font-black uppercase transition-all"
                                    >
                                        {{ code.is_active ? 'Aktywny' : 'Wyłączony' }}
                                    </button>
                                </td>
                                <td class="p-3 text-right">
                                    <button @click="deleteDiscountCode(code.id)" class="text-slate-600 hover:text-red-400 font-bold transition-colors">
                                        🗑️ Usuń
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="discountCodes.length === 0">
                                <td colspan="7" class="p-6 text-center text-slate-600 italic">
                                    Brak zdefiniowanych kodów rabatowych. Użyj formularza powyżej, aby dodać pierwszy kod!
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- 💳 WIDOK 3: KONFIGURACJA PŁATNOŚCI -->
            <!-- ========================================================================= -->
            <div v-if="activeTab === 'payments' && hasPermission('settings.payments')" class="space-y-6">
                <form @submit.prevent="saveSettings" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6 shadow-xl text-xs">
                    
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-300 border-b border-slate-950 pb-2 flex items-center space-x-2">
                        <span>💳</span> <span>Aktywny Sterownik Płatności Online</span>
                    </h2>
                    
                    <!-- Szybki wybór bramki -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label :class="form.payment_gateway === 'simulation' ? 'border-orange-500 bg-orange-950/20 shadow-md' : 'border-slate-800 bg-slate-950'" class="p-4 rounded-xl border flex items-center space-x-3 cursor-pointer transition-all select-none">
                            <input type="radio" v-model="form.payment_gateway" value="simulation" class="text-orange-600 focus:ring-0 bg-slate-950 border-slate-800" />
                            <div>
                                <div class="font-black text-slate-200 uppercase text-[10px] tracking-wide">Symulator BLIK</div>
                                <div class="text-[9px] text-slate-500 mt-0.5">Lokalny ekran testowy</div>
                            </div>
                        </label>

                        <label :class="form.payment_gateway === 'payu' ? 'border-orange-500 bg-orange-950/20 shadow-md' : 'border-slate-800 bg-slate-950'" class="p-4 rounded-xl border flex items-center space-x-3 cursor-pointer transition-all select-none">
                            <input type="radio" v-model="form.payment_gateway" value="payu" class="text-orange-600 focus:ring-0 bg-slate-950 border-slate-800" />
                            <div>
                                <div class="font-black text-slate-200 uppercase text-[10px] tracking-wide">Bramka PayU</div>
                                <div class="text-[9px] text-slate-500 mt-0.5">REST API v2.1 (Polska)</div>
                            </div>
                        </label>

                        <label :class="form.payment_gateway === 'stripe' ? 'border-orange-500 bg-orange-950/20 shadow-md' : 'border-slate-800 bg-slate-950'" class="p-4 rounded-xl border flex items-center space-x-3 cursor-pointer transition-all select-none">
                            <input type="radio" v-model="form.payment_gateway" value="stripe" class="text-orange-600 focus:ring-0 bg-slate-950 border-slate-800" />
                            <div>
                                <div class="font-black text-slate-200 uppercase text-[10px] tracking-wide">Bramka Stripe</div>
                                <div class="text-[9px] text-slate-500 mt-0.5">Karty międzynarodowe</div>
                            </div>
                        </label>
                    </div>

                    <!-- Klucze PayU -->
                    <div v-if="form.payment_gateway === 'payu'" class="space-y-4 pt-4 border-t border-slate-950/80 animate-fadeIn">
                        <div class="flex justify-between items-center">
                            <h3 class="font-black text-orange-400 uppercase tracking-wider text-[10px]">Klucze Autoryzacji PayU</h3>
                            
                            <div class="flex bg-slate-950 p-1 rounded-lg border border-slate-850">
                                <button type="button" @click="form.payu_env = 'sandbox'" :class="form.payu_env === 'sandbox' ? 'bg-orange-600 text-white font-bold' : 'text-slate-500'" class="px-2 py-0.5 rounded-md text-[9px] uppercase transition-all">Sandbox</button>
                                <button type="button" @click="form.payu_env = 'production'" :class="form.payu_env === 'production' ? 'bg-red-600 text-white font-bold' : 'text-slate-500'" class="px-2 py-0.5 rounded-md text-[9px] uppercase transition-all">Production</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-400 uppercase mb-1">Merchant POS ID</label>
                                <input v-model="form.payu_pos_id" type="text" placeholder="np. 300742" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-orange-500 focus:ring-0" required />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-400 uppercase mb-1">OAuth Client ID</label>
                                <input v-model="form.payu_client_id" type="text" placeholder="np. 300742" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-orange-500 focus:ring-0" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-slate-400 uppercase mb-1">OAuth Client Secret</label>
                                <input v-model="form.payu_client_secret" type="password" placeholder="••••••••••••••••••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-orange-500 focus:ring-0" required />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-400 uppercase mb-1">Second Key MD5</label>
                                <input v-model="form.payu_second_key" type="password" placeholder="••••••••••••••••••••••••" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-orange-500 focus:ring-0" required />
                            </div>
                        </div>
                    </div>

                    <!-- STOPKA Z ZAPISEM -->
                    <div class="flex justify-end pt-4 border-t border-slate-850">
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 text-white font-black px-8 py-3.5 rounded-xl uppercase tracking-wider transition-all shadow-md hover:scale-102 active:scale-98 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Zapisywanie danych...' : '💾 Zapisz Konfigurację Płatności' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================================= -->
            <!-- 🔐 WIDOK 4: MACIERZ UPRAWNIEŃ RÓL -->
            <!-- ========================================================================= -->
            <div v-if="activeTab === 'permissions' && authRole === 'admin'" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6 shadow-xl text-xs">
                <div class="border-b border-slate-850 pb-3">
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-300 flex items-center space-x-2">
                        <span>🔐</span> <span>Zarządzanie Dostępem do Modułów Systemu</span>
                    </h2>
                    <p class="text-[11px] text-slate-500 mt-1">Zaznacz, do których funkcji mają dostęp poszczególne role w pizzerii. Administrator zawsze posiada pełny dostęp do wszystkich modułów.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950 text-slate-400 uppercase font-black text-[10px] tracking-wider border-b border-slate-850">
                                <th class="p-3">Moduł / Funkcja Systemu</th>
                                <th class="p-3 text-center">Menedżer (Manager)</th>
                                <th class="p-3 text-center">Kelner / POS (Staff)</th>
                                <th class="p-3 text-center">Kucharz (Chef)</th>
                                <th class="p-3 text-center">Kurier (Driver)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/60">
                            <tr v-for="(label, key) in availablePermissions" :key="key" class="hover:bg-slate-950/40 transition-colors">
                                <td class="p-3 font-bold text-slate-200">{{ label }}</td>
                                
                                <!-- Przełączniki checkbox dla ról -->
                                <td v-for="role in ['manager', 'staff', 'chef', 'driver']" :key="role" class="p-3 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="permissionsMatrix[role]?.includes(key)"
                                        @change="togglePermission(role, key)"
                                        class="rounded bg-slate-950 border-slate-800 text-orange-600 focus:ring-0 h-4 w-4 cursor-pointer"
                                    />
                                </td>
                            </tr>

                            <tr v-if="Object.keys(availablePermissions).length === 0">
                                <td colspan="5" class="p-6 text-center text-slate-600 italic">
                                    Ładowanie listy modułów...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-850">
                    <button 
                        @click="savePermissions"
                        class="bg-emerald-600 hover:bg-emerald-500 text-white font-black px-8 py-3.5 rounded-xl uppercase tracking-wider text-xs shadow-md transition-all hover:scale-102 active:scale-98"
                    >
                        💾 Zapisz Dostęp do Modułów
                    </button>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>