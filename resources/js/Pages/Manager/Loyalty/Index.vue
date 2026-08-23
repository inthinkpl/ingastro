<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Gift, Users, Settings, Plus, Minus, Search, CheckCircle2, ShieldAlert } from 'lucide-vue-next';

const props = defineProps({
    customers: { type: Object, default: () => ({ data: [] }) },
    settings: { type: Object, default: () => ({}) }
});

const searchQuery = ref('');

// Formularz Ustawień Programu
const settingsForm = useForm({
    enabled: props.settings?.enabled ?? true,
    earn_rate: props.settings?.earn_rate ?? 1.00,
    point_value: props.settings?.point_value ?? 0.10,
    min_points_to_redeem: props.settings?.min_points_to_redeem ?? 50.00
});

const submitSettings = () => {
    settingsForm.put(route('manager.loyalty.settings.update'), {
        preserveScroll: true
    });
};

// Formularz Ręcznej Korekty Punktów
const selectedCustomer = ref(null);
const isAdjustModalOpen = ref(false);

const adjustForm = useForm({
    points: '',
    description: ''
});

const openAdjustModal = (customer) => {
    selectedCustomer.value = customer;
    adjustForm.reset();
    isAdjustModalOpen.value = true;
};

const submitAdjust = () => {
    if (!selectedCustomer.value) return;

    adjustForm.post(route('manager.loyalty.adjust', selectedCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isAdjustModalOpen.value = false;
            selectedCustomer.value = null;
        }
    });
};
</script>

<template>
    <Head title="Program Lojalnościowy i CRM - Savona Admin" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <!-- NAGŁÓWEK -->
            <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-800 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                        <Gift class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider">Program Lojalnościowy & CRM</h1>
                        <p class="text-xs text-slate-400">Zarządzaj punktami klientów, progiem wymiany oraz przelicznikami rabatów.</p>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- USTAWIENIA PROGRAMU -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl h-fit">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2 border-b border-slate-800 pb-3">
                        <Settings class="w-4 h-4 text-amber-400" />
                        <span>Reguły i Przeliczniki</span>
                    </h3>

                    <form @submit.prevent="submitSettings" class="space-y-4">
                        <!-- Przełącznik Włącz/Wyłącz -->
                        <div class="flex items-center justify-between p-3 bg-[#0B0F19] border border-slate-800 rounded-xl">
                            <span class="text-xs font-bold text-slate-300 uppercase">Program Aktywny:</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="settingsForm.enabled" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Punkty za 1 PLN wydany:</label>
                            <input 
                                v-model="settingsForm.earn_rate" 
                                type="number" step="0.01" min="0.01" 
                                class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white font-mono"
                            />
                            <p class="text-[10px] text-slate-500 mt-1">np. 1.00 = Klient dostaje 1 pkt za każdą wydaną złotówkę.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Wartość 1 punktu (w PLN):</label>
                            <input 
                                v-model="settingsForm.point_value" 
                                type="number" step="0.01" min="0.001" 
                                class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white font-mono"
                            />
                            <p class="text-[10px] text-slate-500 mt-1">np. 0.10 = 100 punktów daje 10,00 PLN rabatu w koszyku.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Minimum punktów do użycia:</label>
                            <input 
                                v-model="settingsForm.min_points_to_redeem" 
                                type="number" step="1" min="0" 
                                class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white font-mono"
                            />
                            <p class="text-[10px] text-slate-500 mt-1">Minimalny próg punktowy wymagany do aktywacji rabatu.</p>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="settingsForm.processing"
                            class="w-full bg-amber-500 hover:bg-amber-600 disabled:bg-slate-800 text-slate-950 font-black py-3 rounded-xl text-xs uppercase tracking-wider transition cursor-pointer shadow-lg flex justify-center items-center space-x-2"
                        >
                            <CheckCircle2 class="w-4 h-4" />
                            <span>Zapisz Reguły</span>
                        </button>
                    </form>
                </div>

                <!-- BAZA KLIENTÓW CRM -->
                <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center space-x-2">
                            <Users class="w-4 h-4 text-emerald-400" />
                            <span>Baza Klientów ({{ customers.total || customers.data.length }})</span>
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                                    <th class="p-3">Telefon / Imię</th>
                                    <th class="p-3 text-center">Zamówień</th>
                                    <th class="p-3 text-right">Suma Wydań</th>
                                    <th class="p-3 text-right">Saldo Punktów</th>
                                    <th class="p-3 text-center">Akcje</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="c in customers.data" :key="c.id" class="hover:bg-slate-800/30 transition">
                                    <td class="p-3 font-bold text-white">
                                        <div class="font-mono text-amber-400">{{ c.phone }}</div>
                                        <div class="text-[11px] text-slate-400 font-normal">{{ c.name || 'Klient Anonimowy' }}</div>
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold text-slate-300">
                                        {{ c.total_orders }}
                                    </td>
                                    <td class="p-3 text-right font-mono text-slate-300">
                                        {{ Number(c.total_spent).toFixed(2) }} zł
                                    </td>
                                    <td class="p-3 text-right font-mono font-bold text-emerald-400">
                                        {{ Number(c.points_balance).toFixed(0) }} pkt
                                    </td>
                                    <td class="p-3 text-center">
                                        <button 
                                            @click="openAdjustModal(c)"
                                            title="Korekta punktów (dodaj / odejmij)"
                                            class="p-1.5 bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-slate-300 rounded-lg transition cursor-pointer"
                                        >
                                            <Plus class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="customers.data.length === 0">
                                    <td colspan="5" class="text-center py-8 text-slate-500 italic">Brak zarejestrowanych klientów w programie lojalnościowym.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- MODAL RĘCZNEJ KOREKTY PUNKTÓW -->
            <div v-if="isAdjustModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl space-y-4">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Korekta Punktów</h3>
                    <p class="text-xs text-slate-400">Klient: <strong class="text-amber-400 font-mono">{{ selectedCustomer?.phone }}</strong></p>

                    <form @submit.prevent="submitAdjust" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Liczba punktów (+ dodaj / - odejmij):</label>
                            <input 
                                v-model="adjustForm.points" 
                                type="number" 
                                placeholder="np. 50 lub -20" 
                                class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white font-mono"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Powód / Notatka:</label>
                            <input 
                                v-model="adjustForm.description" 
                                type="text" 
                                placeholder="np. Rekompensata za opóźnienie" 
                                class="w-full bg-[#0B0F19] border border-slate-800 focus:border-amber-500 rounded-xl p-2.5 text-xs text-white"
                                required
                            />
                        </div>

                        <div class="flex space-x-3 pt-2">
                            <button type="button" @click="isAdjustModalOpen = false" class="w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 py-2.5 rounded-xl text-xs font-bold uppercase transition cursor-pointer">
                                Anuluj
                            </button>
                            <button type="submit" :disabled="adjustForm.processing" class="w-2/3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black py-2.5 rounded-xl text-xs uppercase tracking-wider transition cursor-pointer">
                                Zatwierdź Korektę
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>