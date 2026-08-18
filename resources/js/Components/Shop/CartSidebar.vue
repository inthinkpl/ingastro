<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    ShoppingBag, Truck, Car, Store, AlertTriangle, 
    Send, Loader2, Sparkles, Plus, X 
} from 'lucide-vue-next';

const props = defineProps({
    cart: { type: Array, required: true },
    products: { type: Array, default: () => [] },
    minOrderAmount: { type: Number, default: 40.00 },
    freeDeliverySettings: { type: Object, default: () => ({ enabled: true, minAmount: 60.00 }) },
    upsellSettings: { type: Object, default: () => ({ enabled: true }) }
});

const emit = defineEmits(['remove-item', 'add-to-cart', 'clear-cart']);

// Dynamiczny pasek postępu darmowej dostawy
const subtotal = computed(() => {
    return props.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const freeDeliveryRemaining = computed(() => {
    if (!props.freeDeliverySettings?.enabled) return 0;
    const target = props.freeDeliverySettings.minAmount;
    return Math.max(0, target - subtotal.value);
});

const freeDeliveryProgress = computed(() => {
    if (!props.freeDeliverySettings?.enabled) return 0;
    const target = props.freeDeliverySettings.minAmount;
    if (target <= 0) return 100;
    return Math.min(100, Math.round((subtotal.value / target) * 100));
});

// Produkty sugerowane do Upsellingu
const upsellProducts = computed(() => {
    if (!props.upsellSettings?.enabled) return [];
    const categoriesToSuggest = ['sos', 'nap', 'doda', 'deser', 'sałat', 'pasta', 'drink'];
    
    let matched = props.products.filter(p => {
        const catName = p.category?.toLowerCase() || '';
        return categoriesToSuggest.some(c => catName.includes(c)) && p.variants?.length > 0;
    });

    if (matched.length === 0) {
        matched = props.products.filter(p => p.category?.toLowerCase() !== 'pizza' && p.variants?.length > 0);
    }

    return matched.slice(0, 6);
});

const addUpsellItem = (product) => {
    const variant = product.variants[0];
    if (!variant) return;

    emit('add-to-cart', {
        variantId: variant.id,
        name: product.name,
        size: variant.size_name,
        price: parseFloat(variant.price),
        quantity: 1,
        modifiers: []
    });
};

// Formularz zamówienia
const form = useForm({
    type: 'dostawa',
    payment_method: 'blik',
    delivery_address: '',
    discount_code: '',
    items: []
});

// Kody rabatowe
const discountCodeInput = ref('');
const appliedDiscount = ref(null);
const discountError = ref(null);
const isValidatingCode = ref(false);

const discountValue = computed(() => {
    if (!appliedDiscount.value) return 0.00;
    if (appliedDiscount.value.type === 'percent') {
        return Math.round((subtotal.value * (appliedDiscount.value.value / 100)) * 100) / 100;
    }
    return Math.min(appliedDiscount.value.value, subtotal.value);
});

const cartTotal = computed(() => {
    return Math.max(0, subtotal.value - discountValue.value);
});

const minOrderWarning = computed(() => {
    if (form.type !== 'dostawa') return null;
    if (cartTotal.value < props.minOrderAmount) {
        const missing = (props.minOrderAmount - cartTotal.value).toFixed(2);
        return `Minimalna wartość zamówienia w dostawie wynosi ${props.minOrderAmount.toFixed(2)} zł. Dołóż do koszyka dania za jeszcze ${missing} zł.`;
    }
    return null;
});

const applyDiscountCode = async () => {
    if (!discountCodeInput.value) return;
    discountError.value = null;
    isValidatingCode.value = true;

    try {
        const response = await axios.post(route('discount.validate'), {
            code: discountCodeInput.value,
            subtotal: subtotal.value
        });
        appliedDiscount.value = response.data;
        form.discount_code = response.data.code;
    } catch (error) {
        appliedDiscount.value = null;
        form.discount_code = '';
        discountError.value = error.response?.data?.message || 'Wystąpił błąd podczas weryfikacji kodu.';
    } finally {
        isValidatingCode.value = false;
    }
};

const removeDiscountCode = () => {
    appliedDiscount.value = null;
    discountCodeInput.value = '';
    form.discount_code = '';
    discountError.value = null;
};

const checkout = () => {
    if (props.cart.length === 0 || minOrderWarning.value) return;

    form.items = props.cart.map(item => ({
        product_variant_id: item.variantId,
        quantity: item.quantity,
        modifiers: item.modifiers.map(m => ({
            ingredient_id: m.ingredient_id,
            action: m.action
        }))
    }));

    form.post(route('order.store'), {
        onSuccess: () => {
            emit('clear-cart');
            form.reset('delivery_address', 'discount_code');
            appliedDiscount.value = null;
            discountCodeInput.value = '';
            alert('Grazie! Twoje zamówienie zostało przekazane bezpośrednio na monitor kuchenny naszej pizzerii.');
        }
    });
};
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sticky top-24 shadow-2xl space-y-4">
        <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3 flex items-center justify-between">
            <span>Twoje Zamówienie</span>
            <ShoppingBag class="w-4 h-4 text-amber-500" />
        </h3>

        <!-- PASEK POSTĘPU DARMOWEJ DOSTAWY -->
        <div v-if="freeDeliverySettings?.enabled && cart.length > 0" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 space-y-2">
            <div class="flex justify-between items-center text-xs">
                <span class="font-bold text-slate-300 flex items-center space-x-1.5">
                    <Truck class="w-4 h-4 text-amber-500" />
                    <span v-if="freeDeliveryRemaining > 0">Darmowa dostawa</span>
                    <span v-else class="text-emerald-400 font-extrabold">Masz DARMOWĄ dostawę! 🎉</span>
                </span>
                <span class="font-mono font-bold text-amber-400 text-[11px]">{{ freeDeliveryProgress }}%</span>
            </div>

            <div class="w-full bg-slate-900 h-2.5 rounded-full overflow-hidden border border-slate-800">
                <div 
                    class="h-full transition-all duration-500 ease-out rounded-full"
                    :class="freeDeliveryRemaining === 0 ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-amber-500 to-red-500'"
                    :style="{ width: freeDeliveryProgress + '%' }"
                ></div>
            </div>

            <p v-if="freeDeliveryRemaining > 0" class="text-[11px] text-slate-400">
                Dołóż jeszcze <strong class="text-amber-400 font-mono">{{ freeDeliveryRemaining.toFixed(2) }} zł</strong>, aby nie płacić za dostawę!
            </p>
        </div>

        <!-- LISTA POZYCJI -->
        <div class="space-y-3 max-h-[35vh] overflow-y-auto pr-1">
            <div v-for="(item, idx) in cart" :key="idx" class="bg-[#0B0F19] p-3 rounded-xl border border-slate-800 text-xs space-y-1.5">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-bold text-white uppercase">{{ item.name }}</div>
                        <div class="text-slate-400 text-[11px]">{{ item.size }} — {{ item.quantity }} szt.</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono font-bold text-emerald-400">{{ (item.price * item.quantity).toFixed(2) }} zł</span>
                        <button @click="$emit('remove-item', idx)" class="text-slate-500 hover:text-red-400 transition cursor-pointer">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div v-if="item.modifiers.length > 0" class="flex flex-wrap gap-1 mt-1 pt-1 border-t border-slate-800/60">
                    <span 
                        v-for="mod in item.modifiers" 
                        :key="mod.ingredient_id"
                        :class="mod.action === 'ADD' ? 'bg-emerald-950/60 text-emerald-400 border-emerald-900' : 'bg-red-950/60 text-red-400 border-red-900'"
                        class="text-[9px] font-bold px-1.5 py-0.5 rounded border uppercase"
                    >
                        {{ mod.action === 'ADD' ? 'Ekstra' : 'Bez' }} {{ mod.name }}
                    </span>
                </div>
            </div>

            <div v-if="cart.length === 0" class="text-center py-8 text-xs text-slate-500 italic">
                Koszyk jest pusty. Wybierz pozycję z menu.
            </div>
        </div>

        <!-- SEKCJA UP-SELLING -->
        <div v-if="upsellSettings?.enabled && cart.length > 0 && upsellProducts.length > 0" class="pt-3 border-t border-slate-800 space-y-2">
            <span class="text-[11px] font-bold text-amber-400 uppercase tracking-wider flex items-center space-x-1">
                <Sparkles class="w-3.5 h-3.5" />
                <span>Często zamawiane razem</span>
            </span>

            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
                <div 
                    v-for="item in upsellProducts" 
                    :key="item.id"
                    class="bg-[#0B0F19] p-2 rounded-xl border border-slate-800 shrink-0 w-32 flex flex-col justify-between text-left space-y-1.5"
                >
                    <div>
                        <span class="text-[11px] font-bold text-white block truncate">{{ item.name }}</span>
                        <span class="text-[10px] text-amber-400 font-mono font-bold block">{{ item.variants[0]?.price }} zł</span>
                    </div>
                    <button 
                        @click="addUpsellItem(item)"
                        class="w-full bg-slate-800 hover:bg-amber-500 hover:text-slate-950 text-slate-200 py-1 rounded-lg text-[10px] font-bold uppercase transition flex items-center justify-center space-x-0.5 cursor-pointer"
                    >
                        <Plus class="w-3 h-3" />
                        <span>Dodaj</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- FORMULARZ ODBIORU / DOSTAWY ORAZ DANE -->
        <div v-if="cart.length > 0" class="space-y-4 pt-3 border-t border-slate-800">
            <div class="grid grid-cols-2 gap-2">
                <button 
                    @click="form.type = 'dostawa'" 
                    :class="form.type === 'dostawa' ? 'bg-red-600 text-white border-red-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-xs font-bold border transition flex items-center justify-center space-x-1.5 cursor-pointer"
                >
                    <Car class="w-3.5 h-3.5" />
                    <span>Dostawa</span>
                </button>
                <button 
                    @click="form.type = 'wynos'" 
                    :class="form.type === 'wynos' ? 'bg-red-600 text-white border-red-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-xs font-bold border transition flex items-center justify-center space-x-1.5 cursor-pointer"
                >
                    <Store class="w-3.5 h-3.5" />
                    <span>Odbiór</span>
                </button>
            </div>

            <div v-if="form.type === 'dostawa'" class="space-y-1">
                <input 
                    v-model="form.delivery_address" 
                    type="text" 
                    placeholder="Adres dostawy (np. ul. Lipowa 10 m. 5)" 
                    class="w-full bg-[#0B0F19] border border-slate-800 focus:border-red-500 rounded-xl p-2.5 text-xs text-white placeholder:text-slate-600"
                    :required="form.type === 'dostawa'"
                />
            </div>

            <p v-if="minOrderWarning" class="text-[10px] text-red-400 bg-red-950/40 p-2.5 rounded-xl border border-red-900/50 font-bold leading-relaxed flex items-center space-x-1">
                <AlertTriangle class="w-3.5 h-3.5 text-red-400 shrink-0" />
                <span>{{ minOrderWarning }}</span>
            </p>

            <!-- PŁATNOŚĆ -->
            <div class="grid grid-cols-3 gap-1.5">
                <button 
                    @click="form.payment_method = 'blik'" 
                    :class="form.payment_method === 'blik' ? 'bg-amber-500/20 text-amber-400 border-amber-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer"
                >BLIK</button>
                <button 
                    @click="form.payment_method = 'payu'" 
                    :class="form.payment_method === 'payu' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer"
                >PayU</button>
                <button 
                    @click="form.payment_method = 'gotówka'" 
                    :class="form.payment_method === 'gotówka' ? 'bg-blue-500/20 text-blue-400 border-blue-500' : 'bg-[#0B0F19] text-slate-400 border-slate-800'"
                    class="py-2 rounded-xl text-[10px] font-bold border transition text-center cursor-pointer"
                >Gotówka</button>
            </div>

            <!-- KOD RABATOWY -->
            <div class="bg-[#0B0F19] p-2.5 rounded-xl border border-slate-800 space-y-2">
                <div v-if="!appliedDiscount" class="flex space-x-2">
                    <input 
                        v-model="discountCodeInput" 
                        type="text" 
                        placeholder="Kod rabatowy" 
                        class="w-2/3 bg-slate-900 border border-slate-800 focus:border-red-500 rounded-xl px-3 py-1.5 text-xs text-white uppercase font-mono"
                    />
                    <button 
                        type="button"
                        @click="applyDiscountCode"
                        :disabled="!discountCodeInput || isValidatingCode"
                        class="w-1/3 bg-slate-800 hover:bg-red-600 disabled:opacity-50 text-white font-bold rounded-xl text-xs uppercase cursor-pointer flex items-center justify-center"
                    >
                        <Loader2 v-if="isValidatingCode" class="w-3.5 h-3.5 animate-spin" />
                        <span v-else>Użyj</span>
                    </button>
                </div>

                <div v-if="appliedDiscount" class="flex justify-between items-center text-xs">
                    <span class="font-mono font-bold text-emerald-400 uppercase">{{ appliedDiscount.code }}</span>
                    <button type="button" @click="removeDiscountCode" class="text-slate-500 hover:text-red-400 font-bold cursor-pointer">✕</button>
                </div>
            </div>

            <!-- PODSUMOWANIE I PRZYCISK ZAMÓWIENIA -->
            <div class="flex justify-between items-center pt-2 border-t border-slate-800">
                <span class="text-xs text-slate-300 uppercase font-bold">Do zapłaty:</span>
                <span class="text-2xl font-black font-mono text-emerald-400">{{ cartTotal.toFixed(2) }} zł</span>
            </div>

            <button 
                @click="checkout"
                :disabled="(form.type === 'dostawa' && (!form.delivery_address || minOrderWarning)) || form.processing"
                class="w-full bg-red-600 hover:bg-red-700 disabled:bg-slate-800 disabled:text-slate-600 text-white font-bold py-3.5 rounded-xl text-xs uppercase tracking-wider transition shadow-lg flex items-center justify-center space-x-2 cursor-pointer"
            >
                <Send class="w-4 h-4" />
                <span>{{ form.processing ? 'Wysyłanie...' : 'Wyślij zamówienie' }}</span>
            </button>
        </div>
    </div>
</template>