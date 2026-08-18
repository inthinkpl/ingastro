<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { Ticket, Plus, Trash2, CheckCircle2, XCircle } from 'lucide-vue-next';

const props = defineProps({
    discountCodes: Array
});

const discountForm = useForm({
    code: '',
    type: 'percent',
    value: 10,
    min_order_amount: 0,
    expires_at: ''
});

const createDiscountCode = () => {
    discountForm.post(route('admin.discount-codes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            discountForm.reset();
            alert('Nowy kod rabatowy został utworzony!');
        }
    });
};

const toggleDiscountCode = (id) => {
    router.patch(route('admin.discount-codes.toggle', id), {}, { preserveScroll: true });
};

const deleteDiscountCode = (id) => {
    if (confirm('Czy na pewno chcesz usunąć ten kod rabatowy?')) {
        router.delete(route('admin.discount-codes.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 space-y-6 shadow-xl text-xs">
        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-300 border-b border-slate-800 pb-2 flex items-center space-x-2">
            <Ticket class="w-4 h-4 text-amber-500" />
            <span>Kody Rabatowe i Promocje</span>
        </h2>

        <!-- FORMULARZ DODAWANIA KODU -->
        <form @submit.prevent="createDiscountCode" class="bg-[#0B0F19] p-4 rounded-2xl border border-slate-800 space-y-4">
            <h3 class="font-bold text-amber-400 uppercase tracking-wider">Utwórz nowy kod:</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kod (np. SAVONA20)</label>
                    <input v-model="discountForm.code" type="text" placeholder="KOD" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white uppercase font-mono font-bold focus:border-red-500" required />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Typ Zniżki</label>
                    <select v-model="discountForm.type" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-medium focus:border-red-500">
                        <option value="percent">Procentowa (%)</option>
                        <option value="fixed">Kwotowa (zł)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Wartość</label>
                    <input v-model="discountForm.value" type="number" step="0.01" min="0.01" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-emerald-400 font-mono font-bold focus:border-red-500" required />
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Min. wartość koszyka</label>
                    <input v-model="discountForm.min_order_amount" type="number" step="0.50" min="0" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-white font-mono focus:border-red-500" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-end gap-3 pt-2">
                <div class="w-full sm:w-1/2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Data wygaśnięcia (opcjonalnie)</label>
                    <input v-model="discountForm.expires_at" type="datetime-local" class="w-full bg-slate-900 border border-slate-800 rounded-xl p-2.5 text-slate-300 focus:border-red-500" />
                </div>
                <button type="submit" :disabled="discountForm.processing" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-2.5 rounded-xl uppercase tracking-wider text-xs transition inline-flex items-center space-x-1 justify-center cursor-pointer">
                    <Plus class="w-4 h-4" />
                    <span>{{ discountForm.processing ? '...' : 'Dodaj Kod' }}</span>
                </button>
            </div>
        </form>

        <!-- TABELA KODÓW -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#0B0F19] text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-slate-800">
                        <th class="p-3">Kod</th>
                        <th class="p-3">Zniżka</th>
                        <th class="p-3">Min. Koszyk</th>
                        <th class="p-3">Wygasa</th>
                        <th class="p-3 text-center">Użycia</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Akcja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <tr v-for="code in discountCodes" :key="code.id" class="hover:bg-slate-800/30 transition">
                        <td class="p-3 font-mono font-bold text-amber-400 uppercase">{{ code.code }}</td>
                        <td class="p-3 font-mono font-bold text-emerald-400">
                            {{ code.type === 'percent' ? code.value + '%' : code.value + ' zł' }}
                        </td>
                        <td class="p-3 font-mono text-slate-300">{{ Number(code.min_order_amount).toFixed(2) }} zł</td>
                        <td class="p-3 text-slate-400 text-[11px]">
                            {{ code.expires_at ? new Date(code.expires_at).toLocaleString('pl-PL') : 'Bezterminowy' }}
                        </td>
                        <td class="p-3 text-center font-mono font-bold text-slate-300">{{ code.times_used }}x</td>
                        <td class="p-3 text-center">
                            <button @click="toggleDiscountCode(code.id)" :class="code.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-red-950 text-red-400 border-red-900'" class="px-2.5 py-1 rounded-lg border text-[9px] font-bold uppercase transition cursor-pointer">
                                {{ code.is_active ? 'Aktywny' : 'Wyłączony' }}
                            </button>
                        </td>
                        <td class="p-3 text-right">
                            <button @click="deleteDiscountCode(code.id)" class="text-slate-500 hover:text-red-400 font-bold transition cursor-pointer">
                                <Trash2 class="w-4 h-4 inline" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>