<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { 
    Users, LayoutGrid, Settings, CheckCircle2, XCircle, Clock, 
    TrendingUp, Plus, Edit2, ShieldAlert, Building2, Mail, Send
} from 'lucide-vue-next';

const props = defineProps({
    tenants: Array,
    modules: Array,
    emailTemplates: Array, // 👈 Nowy props z szablonami
    stats: Object,
});

const activeTab = ref('tenants');

// ==========================
// ZARZĄDZANIE TENANTAMI
// ==========================
const isCreateTenantModalOpen = ref(false);
const createTenantForm = useForm({
    restaurant_name: '',
    subdomain: '',
    admin_name: '',
    email: '',
    password: '',
    enabled_features: ['pos', 'shop', 'kds'],
    subscription_ends_at: '',
});

const isEditTenantModalOpen = ref(false);
const editTenantForm = useForm({
    id: '',
    subscription_status: 'active',
    subscription_ends_at: '',
    enabled_features: [],
});

const openEditTenant = (tenant) => {
    editTenantForm.id = tenant.id;
    editTenantForm.subscription_status = tenant.subscription_status;
    editTenantForm.subscription_ends_at = tenant.subscription_ends_at === 'Bezterminowo' ? '' : tenant.subscription_ends_at;
    let features = [...tenant.enabled_features];
    if (!features.includes('pos')) features.push('pos');
    editTenantForm.enabled_features = features;
    isEditTenantModalOpen.value = true;
};

const submitCreateTenant = () => {
    if (!createTenantForm.enabled_features.includes('pos')) {
        createTenantForm.enabled_features.push('pos');
    }
    createTenantForm.post(route('central.admin.tenants.store'), {
        onSuccess: () => {
            isCreateTenantModalOpen.value = false;
            createTenantForm.reset();
        }
    });
};

const submitEditTenant = () => {
    if (!editTenantForm.enabled_features.includes('pos')) {
        editTenantForm.enabled_features.push('pos');
    }
    editTenantForm.put(route('central.admin.tenants.subscription.update', editTenantForm.id), {
        onSuccess: () => isEditTenantModalOpen.value = false,
    });
};

// ==========================
// ZARZĄDZANIE MODUŁAMI
// ==========================
const isEditModuleModalOpen = ref(false);
const editModuleForm = useForm({
    id: '',
    name: '',
    description: '',
    price_monthly: 0,
    is_active: true,
});

const openEditModule = (mod) => {
    editModuleForm.id = mod.id;
    editModuleForm.name = mod.name;
    editModuleForm.description = mod.description || '';
    editModuleForm.price_monthly = mod.price_monthly;
    editModuleForm.is_active = !!mod.is_active;
    isEditModuleModalOpen.value = true;
};

const submitEditModule = () => {
    editModuleForm.put(route('central.admin.modules.update', editModuleForm.id), {
        onSuccess: () => isEditModuleModalOpen.value = false,
    });
};

// ==========================
// ZARZĄDZANIE SZABLONAMI E-MAIL
// ==========================
const isEditEmailModalOpen = ref(false);
const editEmailForm = useForm({
    id: '',
    subject: '',
    body: '',
    is_active: true,
    available_variables: {},
    name: ''
});

const testEmailForm = useForm({
    test_email: 'kontakt@ingastro.pl'
});

const openEditEmail = (template) => {
    editEmailForm.id = template.id;
    editEmailForm.name = template.name;
    editEmailForm.subject = template.subject;
    editEmailForm.body = template.body;
    editEmailForm.is_active = !!template.is_active;
    editEmailForm.available_variables = template.available_variables || {};
    isEditEmailModalOpen.value = true;
};

const submitEditEmail = () => {
    editEmailForm.put(route('central.admin.email-templates.update', editEmailForm.id), {
        onSuccess: () => isEditEmailModalOpen.value = false,
    });
};

const sendTestEmail = () => {
    testEmailForm.post(route('central.admin.email-templates.test', editEmailForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            alert(`Wysłano test na ${testEmailForm.test_email}!`);
        }
    });
};

// ==========================
// HELPERY
// ==========================
const getTenantMrr = (features) => {
    return features.reduce((sum, key) => {
        const mod = props.modules.find(m => m.key === key);
        return sum + (mod ? Number(mod.price_monthly) : 0);
    }, 0);
};

const getStatusBadge = (status) => {
    const maps = {
        'active': { color: 'bg-emerald-100 text-emerald-800', label: 'Aktywny' },
        'trialing': { color: 'bg-blue-100 text-blue-800', label: 'Trial' },
        'expired': { color: 'bg-red-100 text-red-800', label: 'Wygasł' },
        'cancelled': { color: 'bg-gray-100 text-gray-800', label: 'Anulowany' },
    };
    return maps[status] || { color: 'bg-slate-100 text-slate-800', label: status };
};
</script>

<template>
    <Head title="Super Admin HQ - InGastro" />

    <div class="min-h-screen bg-slate-50 text-slate-800 font-sans p-6 lg:p-10">
        
        <!-- HEADER & STATS -->
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-amber-500 rounded-xl text-slate-950 shadow-lg shadow-amber-500/20">
                        <ShieldAlert class="w-6 h-6" />
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Super Admin <span class="text-amber-500">HQ</span></h1>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Statystyki... -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
                    <div class="p-4 bg-blue-50 text-blue-600 rounded-xl"><Building2 class="w-6 h-6" /></div>
                    <div>
                        <div class="text-sm font-bold text-slate-500 uppercase tracking-wider">Lokale / Tenanci</div>
                        <div class="text-3xl font-black">{{ stats.total_tenants }}</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
                    <div class="p-4 bg-emerald-50 text-emerald-600 rounded-xl"><CheckCircle2 class="w-6 h-6" /></div>
                    <div>
                        <div class="text-sm font-bold text-slate-500 uppercase tracking-wider">Aktywne Subskrypcje</div>
                        <div class="text-3xl font-black">{{ stats.active_subscriptions }}</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
                    <div class="p-4 bg-amber-50 text-amber-600 rounded-xl"><TrendingUp class="w-6 h-6" /></div>
                    <div>
                        <div class="text-sm font-bold text-slate-500 uppercase tracking-wider">Miesięczny MRR</div>
                        <div class="text-3xl font-black font-mono text-amber-600">{{ stats.monthly_mrr }} <span class="text-base text-slate-500">zł</span></div>
                    </div>
                </div>
            </div>

            <!-- TABY NAWIGACYJNE -->
            <div class="flex items-center space-x-2 border-b border-slate-200 pt-4">
                <button @click="activeTab = 'tenants'" :class="['px-6 py-3 font-bold text-sm tracking-wider uppercase border-b-2 transition-all', activeTab === 'tenants' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-800']">
                    Lokale (Tenanci)
                </button>
                <button @click="activeTab = 'modules'" :class="['px-6 py-3 font-bold text-sm tracking-wider uppercase border-b-2 transition-all', activeTab === 'modules' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-800']">
                    Moduły i Cennik
                </button>
                <!-- 👇 Nowa zakładka e-maili -->
                <button @click="activeTab = 'emails'" :class="['px-6 py-3 font-bold text-sm tracking-wider uppercase border-b-2 transition-all', activeTab === 'emails' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-800']">
                    Powiadomienia E-mail
                </button>
            </div>

            <!-- ========================== TAB: TENANCI ========================== -->
            <div v-if="activeTab === 'tenants'" class="space-y-6 pt-4">
                <div class="flex justify-end">
                    <button @click="isCreateTenantModalOpen = true" class="flex items-center space-x-2 bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition">
                        <Plus class="w-4 h-4" /> <span>Dodaj Nowy Lokal</span>
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200 uppercase text-xs font-bold tracking-wider">
                            <tr>
                                <th class="px-6 py-4">ID / Domena</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Wygasa</th>
                                <th class="px-6 py-4">Wykupione Moduły</th>
                                <th class="px-6 py-4">MRR (Netto)</th>
                                <th class="px-6 py-4 text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-900">{{ tenant.id }}<br><span class="text-xs text-slate-500 font-sans">{{ tenant.domain }}</span></td>
                                <td class="px-6 py-4">
                                    <span :class="['px-3 py-1 rounded-full text-xs font-bold uppercase', getStatusBadge(tenant.subscription_status).color]">
                                        {{ getStatusBadge(tenant.subscription_status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ tenant.subscription_ends_at }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1 w-48">
                                        <span v-for="feat in tenant.enabled_features" :key="feat" class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wider">
                                            {{ modules.find(m => m.key === feat)?.name || feat }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-amber-600">{{ getTenantMrr(tenant.enabled_features) }} zł</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openEditTenant(tenant)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"><Edit2 class="w-4 h-4" /></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================== TAB: MODUŁY ========================== -->
            <div v-if="activeTab === 'modules'" class="space-y-6 pt-4">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200 uppercase text-xs font-bold tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Klucz / Kod</th>
                                <th class="px-6 py-4">Nazwa Modułu</th>
                                <th class="px-6 py-4">Cena Mies. (Netto)</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="mod in modules" :key="mod.id" class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono font-bold text-slate-600">{{ mod.key }}</td>
                                <td class="px-6 py-4 font-bold text-slate-900">{{ mod.name }}</td>
                                <td class="px-6 py-4 font-mono font-bold text-amber-600">{{ mod.price_monthly }} zł</td>
                                <td class="px-6 py-4">
                                    <span v-if="mod.is_active" class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold uppercase">Aktywny</span>
                                    <span v-else class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold uppercase">Wyłączony</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="openEditModule(mod)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"><Edit2 class="w-4 h-4" /></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================== TAB: EMAILE ========================== -->
            <div v-if="activeTab === 'emails'" class="space-y-6 pt-4">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200 uppercase text-xs font-bold tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Zdarzenie / Powiadomienie</th>
                                <th class="px-6 py-4">Temat Wiadomości</th>
                                <th class="px-6 py-4">Stan wysyłki</th>
                                <th class="px-6 py-4 text-right">Edycja / Test</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tpl in emailTemplates" :key="tpl.id" class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900">{{ tpl.name }}</div>
                                    <div class="text-xs font-mono text-slate-500 mt-0.5">{{ tpl.event_key }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-600 truncate max-w-xs">{{ tpl.subject }}</td>
                                <td class="px-6 py-4">
                                    <span v-if="tpl.is_active" class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold uppercase">Włączone</span>
                                    <span v-else class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase">Wstrzymane</span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="openEditEmail(tpl)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edytuj szablon">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================== MODAL: EDYCJA E-MAILA ========================== -->
    <div v-if="isEditEmailModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-white z-10 shrink-0">
                <h3 class="font-black text-lg text-slate-900">Edycja: {{ editEmailForm.name }}</h3>
                <button @click="isEditEmailModalOpen = false"><XCircle class="w-5 h-5 text-slate-400" /></button>
            </div>
            
            <div class="flex flex-col md:flex-row overflow-hidden flex-1">
                <!-- Lewa kolumna (Formularz edycji) -->
                <form @submit.prevent="submitEditEmail" class="flex-1 p-6 space-y-4 overflow-y-auto border-r border-slate-100">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Temat Wiadomości</label>
                        <input type="text" v-model="editEmailForm.subject" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500" />
                    </div>

                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <label class="block text-xs font-bold uppercase text-slate-500">Treść (wspiera HTML)</label>
                        </div>
                        <textarea v-model="editEmailForm.body" rows="10" required class="w-full border-slate-200 rounded-xl text-sm font-mono focus:ring-amber-500 focus:border-amber-500"></textarea>
                    </div>

                    <label class="flex items-center space-x-2 cursor-pointer pt-2">
                        <input type="checkbox" v-model="editEmailForm.is_active" class="w-4 h-4 text-amber-500 border-slate-300 rounded focus:ring-amber-500">
                        <span class="text-sm font-bold text-slate-900">Wysyłka automatyczna aktywna</span>
                    </label>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="submit" class="px-5 py-2 text-sm font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl" :disabled="editEmailForm.processing">Zapisz Szablon</button>
                    </div>
                </form>

                <!-- Prawa kolumna (Zmienne i Testowanie) -->
                <div class="w-full md:w-72 bg-slate-50 p-6 overflow-y-auto space-y-6">
                    <div>
                        <h4 class="text-xs font-bold uppercase text-slate-500 mb-3 border-b border-slate-200 pb-2">Dostępne zmienne</h4>
                        <p class="text-xs text-slate-500 mb-3 leading-relaxed">Wklej te kody w temat lub treść maila, a system podmieni je automatycznie przed wysyłką.</p>
                        <ul class="space-y-2">
                            <li v-for="(desc, key) in editEmailForm.available_variables" :key="key" class="text-sm">
                                <code class="bg-amber-100 text-amber-900 px-1.5 py-0.5 rounded font-mono text-xs">{<span v-text="key"></span>}</code>
                                <span class="block text-xs text-slate-500 mt-0.5">{{ desc }}</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase text-slate-500 mb-3 border-b border-slate-200 pb-2 flex items-center"><Send class="w-4 h-4 mr-2"/> Wyślij Test</h4>
                        <div class="space-y-3">
                            <input type="email" v-model="testEmailForm.test_email" placeholder="Wpisz adres email" required class="w-full border-slate-200 rounded-lg text-sm" />
                            <button @click="sendTestEmail" :disabled="testEmailForm.processing" class="w-full bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2 rounded-lg transition flex items-center justify-center">
                                Wyślij test na ten adres
                            </button>
                            <p class="text-[10px] text-slate-400 text-center">System wygeneruje przykładowe dane w miejscach zmiennych.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- (Pozostałe modale zostały zminimalizowane w kodzie powyżej, pozostaw je bez zmian, tak jak w poprzednich krokach) -->
    <div v-if="isEditTenantModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-black text-lg text-slate-900">Zarządzaj Modułami Lokalu</h3>
                <button @click="isEditTenantModalOpen = false"><XCircle class="w-5 h-5 text-slate-400" /></button>
            </div>
            <form @submit.prevent="submitEditTenant" class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Status Subskrypcji</label>
                    <select v-model="editTenantForm.subscription_status" class="w-full border-slate-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500">
                        <option value="active">Aktywna</option>
                        <option value="trialing">Trial</option>
                        <option value="expired">Wygasła</option>
                        <option value="cancelled">Anulowana</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Wygasa (puste = Bezterminowo)</label>
                    <input type="date" v-model="editTenantForm.subscription_ends_at" class="w-full border-slate-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500" />
                </div>
                
                <div class="border-t border-slate-100 pt-4">
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-3">Aktywne Moduły A la Carte</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
                        <label v-for="mod in modules" :key="mod.id" 
                            :class="[
                                'flex items-center p-3 border rounded-xl transition',
                                mod.key === 'pos' ? 'border-amber-300 bg-amber-50 cursor-not-allowed' : 'border-slate-200 cursor-pointer hover:bg-slate-50'
                            ]"
                        >
                            <input 
                                type="checkbox" 
                                :value="mod.key" 
                                v-model="editTenantForm.enabled_features" 
                                :disabled="mod.key === 'pos'"
                                class="w-4 h-4 text-amber-500 border-slate-300 rounded focus:ring-amber-500 disabled:opacity-50"
                            >
                            <div class="ml-3 flex-1">
                                <span class="flex items-center text-sm font-bold text-slate-900">
                                    {{ mod.name }}
                                    <span v-if="mod.key === 'pos'" class="ml-2 text-[9px] bg-amber-200 text-amber-900 px-1.5 py-0.5 rounded uppercase tracking-wider">
                                        Moduł Podstawowy
                                    </span>
                                </span>
                                <span class="block text-xs text-slate-500">{{ mod.price_monthly }} zł / mies.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" @click="isEditTenantModalOpen = false" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Anuluj</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl" :disabled="editTenantForm.processing">Zapisz Zmiany</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- (Modal Modułów) -->
    <div v-if="isEditModuleModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-black text-lg text-slate-900">Edycja Modułu Systemu</h3>
                <button @click="isEditModuleModalOpen = false"><XCircle class="w-5 h-5 text-slate-400" /></button>
            </div>
            <form @submit.prevent="submitEditModule" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nazwa Modułu</label>
                    <input type="text" v-model="editModuleForm.name" class="w-full border-slate-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Cena Miesięczna (Netto PLN)</label>
                    <input type="number" step="0.01" v-model="editModuleForm.price_monthly" class="w-full border-slate-200 rounded-xl text-sm font-mono focus:ring-amber-500 focus:border-amber-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Opis dla Klienta</label>
                    <textarea v-model="editModuleForm.description" rows="3" class="w-full border-slate-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" v-model="editModuleForm.is_active" class="w-4 h-4 text-amber-500 border-slate-300 rounded focus:ring-amber-500">
                    <span class="text-sm font-bold text-slate-900">Moduł dostępny w ofercie</span>
                </label>
                <div class="pt-4 flex justify-end space-x-3">
                    <button type="button" @click="isEditModuleModalOpen = false" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Anuluj</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl" :disabled="editModuleForm.processing">Zapisz Cennik</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- (Modal Nowego Lokalu) -->
    <div v-if="isCreateTenantModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-white z-10 shrink-0">
                <h3 class="font-black text-lg text-slate-900">Dodaj Nowy Lokal Gastronomiczny</h3>
                <button @click="isCreateTenantModalOpen = false"><XCircle class="w-5 h-5 text-slate-400" /></button>
            </div>
            
            <form @submit.prevent="submitCreateTenant" class="flex flex-col overflow-hidden">
                <div class="p-6 space-y-6 overflow-y-auto pr-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nazwa Pizzerii/Lokalu</label>
                            <input type="text" v-model="createTenantForm.restaurant_name" required class="w-full border-slate-200 rounded-xl text-sm focus:ring-amber-500 focus:border-amber-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Subdomena (ID lokalu)</label>
                            <div class="flex">
                                <input type="text" v-model="createTenantForm.subdomain" required class="w-full border-slate-200 rounded-l-xl text-sm focus:ring-amber-500 focus:border-amber-500" />
                                <span class="bg-slate-100 border border-l-0 border-slate-200 text-slate-500 px-3 py-2 rounded-r-xl text-sm flex items-center">.ingastro.pl</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Imię Admina</label>
                            <input type="text" v-model="createTenantForm.admin_name" required class="w-full border-slate-200 rounded-xl text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Email</label>
                            <input type="email" v-model="createTenantForm.email" required class="w-full border-slate-200 rounded-xl text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Hasło</label>
                            <input type="password" v-model="createTenantForm.password" required minlength="8" class="w-full border-slate-200 rounded-xl text-sm" />
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-3">Wybierz Moduły Startowe</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <label v-for="mod in modules" :key="mod.id" 
                                :class="[
                                    'flex items-center p-3 border rounded-xl transition',
                                    mod.key === 'pos' ? 'border-amber-300 bg-amber-50 cursor-not-allowed' : 'border-slate-200 cursor-pointer hover:bg-slate-50'
                                ]"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="mod.key" 
                                    v-model="createTenantForm.enabled_features" 
                                    :disabled="mod.key === 'pos'"
                                    class="w-4 h-4 text-amber-500 border-slate-300 rounded focus:ring-amber-500 disabled:opacity-50"
                                >
                                <div class="ml-3 flex-1">
                                    <span class="flex items-center text-sm font-bold text-slate-900">
                                        {{ mod.name }}
                                        <span v-if="mod.key === 'pos'" class="ml-2 text-[9px] bg-amber-200 text-amber-900 px-1.5 py-0.5 rounded uppercase tracking-wider">
                                            Moduł Podstawowy
                                        </span>
                                    </span>
                                    <span class="block text-xs text-slate-500">{{ mod.price_monthly }} zł / mies.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-3 shrink-0">
                    <button type="button" @click="isCreateTenantModalOpen = false" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-200 rounded-xl transition">Anuluj</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl transition shadow-lg shadow-amber-500/20" :disabled="createTenantForm.processing">
                        Utwórz Pizzerię i Skonfiguruj
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>