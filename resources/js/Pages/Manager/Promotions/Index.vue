<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    Tag, Plus, Trash2, ToggleLeft, ToggleRight, Filter, Sparkles, AlertCircle 
} from 'lucide-vue-next';

const props = defineProps({
    promotions: { type: Array, default: () => [] },
    productVariants: { type: Array, default: () => [] }
});

const isModalOpen = ref(false);

const form = useForm({
    name: '',
    type: 'percent_discount',
    discount_target: 'cart_total',
    value: 10,
    min_order_amount: 0,
    min_quantity: 2,
    required_category: 'pizza',
    required_size_name: 'Dowolny',
    reward_variant_id: null,
    is_active: true
});

// 1. DYNAMICZNA LISTA KATEGORII ZE ZDEFINIOWANYCH PRODUKTÓW
const availableCategories = computed(() => {
    const categories = new Set();
    props.productVariants.forEach(variant => {
        const cat = variant.product?.category;
        if (cat) categories.add(cat);
    });
    return Array.from(categories);
});

// 2. DYNAMICZNA LISTA ROZMIARÓW ZALEŻNA OD WYBRANEJ KATEGORII
const availableSizes = computed(() => {
    if (!form.required_category || form.required_category === 'all') {
        const allSizes = new Set();
        props.productVariants.forEach(v => {
            if (v.size_name) allSizes.add(v.size_name);
        });
        return Array.from(allSizes);
    }

    const matchedSizes = new Set();
    props.productVariants.forEach(v => {
        const catName = (v.product?.category || '').toLowerCase();
        const selectedCat = form.required_category.toLowerCase();

        if (catName.includes(selectedCat)) {
            if (v.size_name) matchedSizes.add(v.size_name);
        }
    });

    return Array.from(matchedSizes);
});

// Automatyczny reset rozmiaru na "Dowolny" po zmianie kategorii
watch(() => form.required_category, () => {
    form.required_size_name = 'Dowolny';
});

const openModal = () => {
    form.reset();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submit = () => {
    form.post(route('manager.promotions.store'), {
        onSuccess: () => closeModal()
    });
};

const togglePromotion = (id) => {
    form.patch(route('manager.promotions.toggle', id), { preserveScroll: true });
};

const deletePromotion = (id) => {
    if (confirm('Czy na pewno chcesz usunąć tę promocję?')) {
        form.delete(route('manager.promotions.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Zarządzanie Promocjami - Savona ERP" />

    <AuthenticatedLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6 font-sans bg-[#0B0F19] text-slate-300 min-h-screen">
            
            <header class="border-b border-slate-800 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-amber-500 uppercase tracking-wider flex items-center space-x-2">
                        <Tag class="w-6 h-6 text-amber-500" />
                        <span>Kreator Promocji i Gratisów</span>
                    </h1>
                    <p class="text-xs text-slate-400 mt-0.5">Automatyczne reguły promocyjne oparte na kategoriach i rozmiarach dań.</p>
                </div>

                <button 
                    @click="openModal"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer"
                >
                    <Plus class="w-4 h-4" />
                    <span>Dodaj Promocję</span>
                </button>
            </header>

            <!-- LISTA PROMOCJI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div 
                    v-for="promo in promotions" 
                    :key="promo.id"
                    class="bg-slate-900 border rounded-2xl p-5 space-y-4 shadow-xl transition"
                    :class="promo.is_active ? 'border-slate-800' : 'border-slate-800/40 opacity-60'"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">
                                Reguła Promocyjna #{{ promo.id }}
                            </span>
                            <h3 class="text-base font-black text-white uppercase">{{ promo.name }}</h3>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button 
                                @click="togglePromotion(promo.id)"
                                class="text-slate-400 hover:text-white transition cursor-pointer"
                            >
                                <ToggleRight v-if="promo.is_active" class="w-7 h-7 text-emerald-400" />
                                <ToggleLeft v-else class="w-7 h-7 text-slate-600" />
                            </button>

                            <button 
                                @click="deletePromotion(promo.id)"
                                class="text-slate-500 hover:text-red-400 p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-[#0B0F19] p-3.5 rounded-xl border border-slate-800 space-y-2 text-xs">
                        <div class="flex justify-between items-center font-bold">
                            <span class="text-slate-400">Warunek ilościowy:</span>
                            <span class="text-amber-400 uppercase">
                                {{ promo.min_quantity || 1 }}x {{ promo.required_category || 'Danie' }} (Rozmiar: {{ promo.required_size_name || 'Dowolny' }})
                            </span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Nagroda / Rabat:</span>
                            <span class="font-bold text-emerald-400">
                                <template v-if="promo.type === 'free_product'">
                                    GRATIS: {{ promo.reward_variant?.product?.name }} ({{ promo.reward_variant?.size_name }})
                                </template>
                                <template v-else>
                                    {{ promo.value }}{{ promo.type === 'percent_discount' ? '%' : ' zł' }} 
                                    ({{ promo.discount_target === 'cheapest_item' ? 'Najtańsze danie' : 'Całość' }})
                                </template>
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="promotions.length === 0" class="col-span-full bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-slate-500 italic space-y-3">
                    <AlertCircle class="w-8 h-8 mx-auto text-slate-600" />
                    <p>Brak zdefiniowanych promocji. Kliknij przycisk powyżej, aby utworzyć pierwszą regułę.</p>
                </div>
            </div>

            <!-- MODAL FORMULARZA -->
            <div v-if="isModalOpen" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-xl w-full space-y-4 shadow-2xl">
                    <div class="border-b border-slate-800 pb-3 flex justify-between items-center">
                        <h3 class="text-base font-bold text-white uppercase">Konfigurator Reguły Promocji</h3>
                        <button @click="closeModal" class="text-slate-500 hover:text-white">✕</button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nazwa Promocji:</label>
                            <input v-model="form.name" type="text" placeholder="np. 2x Duża Pizza + Napój Gratis" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white" required />
                        </div>

                        <!-- SEKCJA WARUNKÓW KWALIFIKACJI -->
                        <div class="bg-[#0B0F19] p-3.5 rounded-xl border border-amber-500/30 space-y-3">
                            <span class="text-[11px] font-bold text-amber-400 uppercase flex items-center space-x-1">
                                <Filter class="w-3.5 h-3.5" />
                                <span>1. Wymagania w koszyku (Warunki)</span>
                            </span>

                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Kategoria:</label>
                                    <select v-model="form.required_category" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white uppercase">
                                        <option value="all">Wszystkie</option>
                                        <option v-for="cat in availableCategories" :key="cat" :value="cat">
                                            {{ cat }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Dynamiczny Rozmiar:</label>
                                    <select v-model="form.required_size_name" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white uppercase">
                                        <option value="Dowolny">Dowolny</option>
                                        <option v-for="size in availableSizes" :key="size" :value="size">
                                            {{ size }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Min. Ilość (szt.):</label>
                                    <input v-model.number="form.min_quantity" type="number" min="1" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white font-mono" />
                                </div>
                            </div>
                        </div>

                        <!-- SEKCJA NAGRODY -->
                        <div class="bg-[#0B0F19] p-3.5 rounded-xl border border-emerald-500/30 space-y-3">
                            <span class="text-[11px] font-bold text-emerald-400 uppercase flex items-center space-x-1">
                                <Sparkles class="w-3.5 h-3.5" />
                                <span>2. Przyznawana Nagroda / Rabat</span>
                            </span>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Typ Nagrody:</label>
                                    <select v-model="form.type" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                        <option value="free_product">Darmowy Produkt (Gratis)</option>
                                        <option value="percent_discount">Rabat procentowy (%)</option>
                                        <option value="amount_discount">Rabat kwotowy (PLN)</option>
                                    </select>
                                </div>

                                <div v-if="form.type !== 'free_product'">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Cel Rabatu:</label>
                                    <select v-model="form.discount_target" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                        <option value="cart_total">Wartość całego koszyka</option>
                                        <option value="cheapest_item">Najtańsze spełniające danie</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="form.type === 'free_product'">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Wybierz produkt gratis:</label>
                                <select v-model="form.reward_variant_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                    <option :value="null">-- Wybierz produkt --</option>
                                    <option v-for="variant in productVariants" :key="variant.id" :value="variant.id">
                                        {{ variant.product?.name }} ({{ variant.size_name }})
                                    </option>
                                </select>
                            </div>

                            <div v-else>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Wartość Rabatu:</label>
                                <input v-model.number="form.value" type="number" step="0.01" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white font-mono" />
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 pt-2 border-t border-slate-800">
                            <button type="button" @click="closeModal" class="bg-slate-800 text-slate-300 px-4 py-2 rounded-xl text-xs uppercase font-bold">Anuluj</button>
                            <button type="submit" :disabled="form.processing" class="bg-red-600 text-white px-5 py-2 rounded-xl text-xs uppercase font-bold shadow-lg">Zapisz Regułę</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>