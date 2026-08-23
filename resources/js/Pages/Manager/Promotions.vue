<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Tag, Plus, Trash2, Power, Gift, Percent, DollarSign } from 'lucide-vue-next';

const props = defineProps({
    promotions: Array,
    variants: Array
});

const isModalOpen = ref(false);

const form = useForm({
    name: '',
    type: 'free_product',
    min_pizza_count: 2,
    min_order_amount: 0,
    reward_product_variant_id: null,
    discount_value: 0,
    apply_to_cheapest: false,
    is_active: true
});

const submit = () => {
    form.post(route('manager.promotions.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
        }
    });
};

const togglePromotion = (id) => {
    form.patch(route('manager.promotions.toggle', id));
};

const deletePromotion = (id) => {
    if (confirm('Czy na pewno chcesz usunąć tę promocję?')) {
        form.delete(route('manager.promotions.destroy', id));
    }
};
</script>

<template>
    <Head title="Promocje Automatyczne - Savona ERP" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <header class="flex justify-between items-center border-b border-slate-800 pb-4">
                <div>
                    <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider flex items-center space-x-2">
                        <Tag class="w-6 h-6" />
                        <span>Automatyczne Promocje i Gratisy</span>
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">Zarządzaj regułami rabatowymi naliczanymi automatycznie w koszyku.</p>
                </div>

                <button 
                    @click="isModalOpen = true"
                    class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider flex items-center space-x-2 transition cursor-pointer shadow-lg"
                >
                    <Plus class="w-4 h-4" />
                    <span>Nowa Reguła Promocyjna</span>
                </button>
            </header>

            <!-- LISTA PROMOCJI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="promo in promotions" :key="promo.id" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-xl">
                    <div class="flex justify-between items-start">
                        <div>
                            <span :class="promo.is_active ? 'bg-emerald-950 text-emerald-400 border-emerald-900' : 'bg-slate-800 text-slate-500 border-slate-700'" class="text-[9px] font-black uppercase px-2 py-0.5 rounded border">
                                {{ promo.is_active ? 'Aktywna' : 'Wyłączona' }}
                            </span>
                            <h3 class="text-sm font-bold text-white uppercase mt-2">{{ promo.name }}</h3>
                        </div>

                        <div class="flex items-center space-x-1">
                            <button @click="togglePromotion(promo.id)" class="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl transition cursor-pointer">
                                <Power class="w-4 h-4" :class="promo.is_active ? 'text-emerald-400' : 'text-slate-500'" />
                            </button>
                            <button @click="deletePromotion(promo.id)" class="p-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl transition cursor-pointer">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 text-xs space-y-1.5 font-mono">
                        <div class="text-slate-400">Warunek: <strong class="text-amber-400">{{ promo.min_pizza_count > 0 ? promo.min_pizza_count + 'x Pizza' : 'Wartość od ' + promo.min_order_amount + ' zł' }}</strong></div>
                        <div class="text-slate-400">Nagroda: 
                            <strong class="text-emerald-400" v-if="promo.type === 'free_product'">Gratis: {{ promo.reward_variant?.product?.name }}</strong>
                            <strong class="text-emerald-400" v-else-if="promo.type === 'discount_percent'">Rabat {{ promo.discount_value }}% {{ promo.apply_to_cheapest ? '(na najtańszą pizzę)' : '' }}</strong>
                            <strong class="text-emerald-400" v-else>Rabat {{ promo.discount_value }} zł</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL NOWEJ PROMOCJI -->
            <div v-if="isModalOpen" class="fixed inset-0 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm z-50">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">Nowa Reguła Promocyjna</h3>

                    <form @submit.prevent="submit" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Nazwa Promocji:</label>
                            <input v-model="form.name" type="text" placeholder="np. 3 Pizzę za 50% ceny" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white" required />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Typ Promocji:</label>
                            <select v-model="form.type" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white cursor-pointer">
                                <option value="free_product">Gratisowy Produkt (np. Cola)</option>
                                <option value="discount_percent">Rabat Procentowy (%)</option>
                                <option value="discount_fixed">Rabat Kwotowy (zł)</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-bold text-slate-300 mb-1">Min. Liczba Pizz:</label>
                                <input v-model.number="form.min_pizza_count" type="number" min="0" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-300 mb-1">Min. Kwota Koszyka (zł):</label>
                                <input v-model.number="form.min_order_amount" type="number" min="0" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono" />
                            </div>
                        </div>

                        <div v-if="form.type === 'free_product'">
                            <label class="block font-bold text-slate-300 mb-1">Gratisowy Wariant Produktu:</label>
                            <select v-model="form.reward_product_variant_id" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white cursor-pointer">
                                <option v-for="v in variants" :key="v.id" :value="v.id">{{ v.name }}</option>
                            </select>
                        </div>

                        <div v-else class="space-y-2">
                            <div>
                                <label class="block font-bold text-slate-300 mb-1">Wartość Rabatu:</label>
                                <input v-model.number="form.discount_value" type="number" min="0" class="w-full bg-[#0B0F19] border border-slate-800 rounded-xl p-2.5 text-white font-mono" />
                            </div>
                            <label v-if="form.type === 'discount_percent'" class="flex items-center space-x-2 text-slate-300 cursor-pointer pt-1">
                                <input v-model="form.apply_to_cheapest" type="checkbox" class="rounded bg-slate-800 border-slate-700" />
                                <span>Nalicz tylko na najtańszą pizzę w koszyku</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-2 pt-3 border-t border-slate-800">
                            <button type="button" @click="isModalOpen = false" class="bg-slate-800 text-slate-300 px-4 py-2 rounded-xl">Anuluj</button>
                            <button type="submit" :disabled="form.processing" class="bg-amber-500 text-slate-950 font-bold px-4 py-2 rounded-xl">Zapisz Promocję</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>