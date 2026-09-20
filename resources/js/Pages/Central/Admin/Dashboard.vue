<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tenants: Array,
    plans: Array,
    stats: Object,
});

// Modal edycji subskrypcji
const selectedTenant = ref(null);
const subscriptionForm = useForm({
    plan_id: '',
    subscription_status: 'active',
    subscription_ends_at: '',
});

const openEditModal = (tenant) => {
    subscriptionForm.clearErrors();
    selectedTenant.value = tenant;
    subscriptionForm.plan_id = tenant.plan_id || props.plans[0]?.id;
    subscriptionForm.subscription_status = tenant.subscription_status || 'active';
    
    // Pobranie daty w formacie YYYY-MM-DD dla pola type="date"
    if (tenant.subscription_ends_at && tenant.subscription_ends_at !== 'Bezterminowo') {
        subscriptionForm.subscription_ends_at = tenant.subscription_ends_at.substring(0, 10);
    } else {
        subscriptionForm.subscription_ends_at = '';
    }
};

const saveSubscription = () => {
    if (!selectedTenant.value) return;

    // Generujemy relatywną ścieżkę (np. /super-admin/tenants/napoli/subscription), 
    // aby zapytanie leciało na ten sam host/origin (unikamy błędów CORS)
    const fullUrl = route('central.admin.tenants.subscription.update', { tenant: selectedTenant.value.id });
    const relativeUrl = fullUrl.replace(/^https?:\/\/[^\/]+/, '');

    subscriptionForm.transform((data) => ({
        ...data,
        _method: 'PUT',
        subscription_ends_at: data.subscription_ends_at || null,
    })).post(relativeUrl, {
        preserveScroll: true,
        onSuccess: () => {
            selectedTenant.value = null;
        },
        onError: (errors) => {
            console.error("Błędy podczas zapisu subskrypcji:", errors);
        }
    });
};

// Modal ręcznego tworzenia Tenanta
const showCreateModal = ref(false);
const createForm = useForm({
    restaurant_name: '',
    subdomain: '',
    admin_name: '',
    email: '',
    password: '',
    plan_id: '',
    subscription_ends_at: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    if (props.plans.length > 0) {
        createForm.plan_id = props.plans[0].id;
    }
    showCreateModal.value = true;
};

const createTenant = () => {
    const fullUrl = route('central.admin.tenants.store');
    const relativeUrl = fullUrl.replace(/^https?:\/\/[^\/]+/, '');

    createForm.transform((data) => ({
        ...data,
        subscription_ends_at: data.subscription_ends_at || null,
    })).post(relativeUrl, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Super Admin HQ - Zarządzanie Klientami SaaS" />

    <div class="min-h-screen bg-slate-950 text-slate-100 p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <!-- Nagłówek -->
            <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-6 gap-4">
                <div>
                    <h1 class="text-3xl font-black text-white">Central HQ Super Admin</h1>
                    <p class="text-slate-400 text-sm mt-1">Zarządzaj pizzeriami, subskrypcjami oraz planami abonamentowymi.</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="px-4 py-2 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-400 font-bold text-sm">
                        MRR: {{ stats.monthly_mrr }} zł / mies.
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-sm transition shadow-lg shadow-amber-500/20 flex items-center space-x-2 cursor-pointer"
                    >
                        <span>+ Dodaj Nowy Lokal</span>
                    </button>
                </div>
            </div>

            <!-- Karty Statystyk -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl">
                    <div class="text-slate-400 text-xs uppercase font-bold mb-1">Wszystkie Lokale</div>
                    <div class="text-3xl font-black text-white">{{ stats.total_tenants }}</div>
                </div>
                <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl">
                    <div class="text-slate-400 text-xs uppercase font-bold mb-1">Aktywne Subskrypcje</div>
                    <div class="text-3xl font-black text-emerald-400">{{ stats.active_subscriptions }}</div>
                </div>
                <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl">
                    <div class="text-slate-400 text-xs uppercase font-bold mb-1">Szacowane Przychody</div>
                    <div class="text-3xl font-black text-amber-400">{{ stats.monthly_mrr }} zł</div>
                </div>
            </div>

            <!-- Tabela Klientów -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-6 border-b border-slate-800 font-bold text-lg text-white">Lista Pizzerii (Tenanty)</div>
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950 text-slate-400 font-bold uppercase text-xs border-b border-slate-800">
                        <tr>
                            <th class="p-4">Subdomena / ID</th>
                            <th class="p-4">Domena</th>
                            <th class="p-4">Wykupiony Plan</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Ważność Do</th>
                            <th class="p-4 text-right">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-slate-800/50 transition">
                            <td class="p-4 font-bold text-white">{{ tenant.id }}</td>
                            <td class="p-4 font-mono text-amber-400">
                                <a :href="'http://' + tenant.domain" target="_blank" class="hover:underline flex items-center space-x-1">
                                    <span>{{ tenant.domain }}</span>
                                </a>
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 border border-slate-700 text-white">
                                    {{ tenant.plan_name }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span :class="[
                                    'px-2.5 py-1 rounded-md text-xs font-bold uppercase border',
                                    tenant.subscription_status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'
                                ]">
                                    {{ tenant.subscription_status }}
                                </span>
                            </td>
                            <td class="p-4 font-mono text-slate-400">{{ tenant.subscription_ends_at }}</td>
                            <td class="p-4 text-right">
                                <button @click="openEditModal(tenant)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-amber-400 border border-amber-500/30 font-bold rounded-lg text-xs transition cursor-pointer">
                                    Zarządzaj Planem
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- MODAL: RĘCZNE TWORZENIE TENANTA -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-lg w-full space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto">
                    <h3 class="text-xl font-black text-white">Dodaj Nowy Lokal (Tenanta)</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nazwa Pizzerii</label>
                            <input type="text" v-model="createForm.restaurant_name" placeholder="np. Pizzeria Napoli" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                            <span v-if="createForm.errors.restaurant_name" class="text-red-400 text-xs mt-1 block">{{ createForm.errors.restaurant_name }}</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Subdomena (Identyfikator)</label>
                            <input type="text" v-model="createForm.subdomain" placeholder="np. napoli" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm font-mono">
                            <span v-if="createForm.errors.subdomain" class="text-red-400 text-xs mt-1 block">{{ createForm.errors.subdomain }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Imię Admina</label>
                                <input type="text" v-model="createForm.admin_name" placeholder="np. Jan Kowalski" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                                <span v-if="createForm.errors.admin_name" class="text-red-400 text-xs mt-1 block">{{ createForm.errors.admin_name }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email Admina</label>
                                <input type="email" v-model="createForm.email" placeholder="jan@napoli.pl" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                                <span v-if="createForm.errors.email" class="text-red-400 text-xs mt-1 block">{{ createForm.errors.email }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Hasło Konta</label>
                            <input type="password" v-model="createForm.password" placeholder="Min. 8 znaków" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                            <span v-if="createForm.errors.password" class="text-red-400 text-xs mt-1 block">{{ createForm.errors.password }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Wybierz Plan</label>
                                <select v-model="createForm.plan_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                        {{ plan.name }} ({{ plan.price_monthly }} zł)
                                    </option>
                                </select>
                                <span v-if="createForm.errors.plan_id" class="text-red-400 text-xs mt-1 block">{{ createForm.errors.plan_id }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Ważność Do (Opcjonalnie)</label>
                                <input type="date" v-model="createForm.subscription_ends_at" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                                <span v-if="createForm.errors.subscription_ends_at" class="text-red-400 text-xs mt-1 block">{{ createForm.errors.subscription_ends_at }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-slate-800">
                        <button @click="showCreateModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl text-sm cursor-pointer">Anuluj</button>
                        <button @click="createTenant" :disabled="createForm.processing" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-xl text-sm transition disabled:opacity-50 cursor-pointer">
                            {{ createForm.processing ? 'Tworzenie...' : 'Utwórz Lokal' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL: EDYCJA SUBSKRYPCJI -->
            <div v-if="selectedTenant" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full space-y-4 shadow-2xl">
                    <h3 class="text-xl font-bold text-white">Edycja Planu: {{ selectedTenant.id }}</h3>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Wybierz Plan</label>
                        <select v-model="subscriptionForm.plan_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }} ({{ plan.price_monthly }} zł/mc)
                            </option>
                        </select>
                        <span v-if="subscriptionForm.errors.plan_id" class="text-red-400 text-xs mt-1 block">{{ subscriptionForm.errors.plan_id }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Status Subskrypcji</label>
                        <select v-model="subscriptionForm.subscription_status" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                            <option value="active">Aktywna</option>
                            <option value="expired">Wygasła</option>
                            <option value="cancelled">Anulowana</option>
                        </select>
                        <span v-if="subscriptionForm.errors.subscription_status" class="text-red-400 text-xs mt-1 block">{{ subscriptionForm.errors.subscription_status }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Data Wygaśnięcia (Pozostaw puste dla bezterminowej)</label>
                        <input type="date" v-model="subscriptionForm.subscription_ends_at" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-sm">
                        <span v-if="subscriptionForm.errors.subscription_ends_at" class="text-red-400 text-xs mt-1 block">{{ subscriptionForm.errors.subscription_ends_at }}</span>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-slate-800">
                        <button @click="selectedTenant = null" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl text-sm cursor-pointer">Anuluj</button>
                        <button @click="saveSubscription" :disabled="subscriptionForm.processing" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl text-sm transition disabled:opacity-50 cursor-pointer">
                            {{ subscriptionForm.processing ? 'Zapisywanie...' : 'Zapisz Zmiany' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>