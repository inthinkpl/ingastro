<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ShoppingBag } from 'lucide-vue-next';

// Importowanie wydzielonych kroków
import CartStep1Cart from './Partials/CartStep1Cart.vue';
import CartStep2Delivery from './Partials/CartStep2Delivery.vue';
import CartStep3Payment from './Partials/CartStep3Payment.vue';

const props = defineProps({
    cart: { type: Array, required: true },
    products: { type: Array, default: () => [] },
    minOrderAmount: { type: Number, default: 40.00 },
    freeDeliverySettings: { type: Object, default: () => ({ enabled: true, minAmount: 60.00 }) },
    upsellSettings: { type: Object, default: () => ({ enabled: true }) }
});

const emit = defineEmits(['remove-item', 'add-to-cart', 'clear-cart']);

// Nawigacja Krokowa
const currentStep = ref(1);

const goToStep = (step) => {
    if (step === 2 && props.cart.length === 0) return;
    if (step === 3) {
        if (!form.phone || (form.type === 'dostawa' && !form.delivery_address)) return;
    }
    currentStep.value = step;
};

// Automatyczne Promocje
const autoDiscount = ref(0.00);
const freeItemsFromPromo = ref([]);

watch(() => props.cart, async (newCart) => {
    if (!newCart || newCart.length === 0) {
        autoDiscount.value = 0.00;
        freeItemsFromPromo.value = [];
        return;
    }

    try {
        const payload = newCart.map(item => ({
            product_variant_id: item.variantId,
            quantity: item.quantity,
            price: item.price
        }));

        const response = await axios.post(route('promotions.calculate'), { items: payload });
        autoDiscount.value = parseFloat(response.data.discount_amount || 0);
        freeItemsFromPromo.value = response.data.free_items || [];
    } catch (e) {
        autoDiscount.value = 0.00;
        freeItemsFromPromo.value = [];
    }
}, { deep: true, immediate: true });

// Obliczenia finansowe
const subtotal = computed(() => props.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0));

const freeDeliveryRemaining = computed(() => {
    if (!props.freeDeliverySettings?.enabled) return 0;
    return Math.max(0, props.freeDeliverySettings.minAmount - subtotal.value);
});

const freeDeliveryProgress = computed(() => {
    if (!props.freeDeliverySettings?.enabled) return 0;
    const target = props.freeDeliverySettings.minAmount;
    if (target <= 0) return 100;
    return Math.min(100, Math.round((subtotal.value / target) * 100));
});

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
    phone: '',
    delivery_address: '',
    discount_code: '',
    loyalty_discount: 0,
    items: []
});

// Program Lojalnościowy
const earnRate = ref(1.00);
const isLoyaltyEligible = ref(false);
const showOtpInput = ref(false);
const otpCode = ref('');
const loyaltyDiscountAmount = ref(0);
const loyaltyMessage = ref('');
const isSendingOtp = ref(false);
const isVerifyingOtp = ref(false);

onMounted(async () => {
    try {
        const res = await axios.post(route('loyalty.check'), { phone: '' });
        if (res.data.earn_rate) earnRate.value = parseFloat(res.data.earn_rate);
    } catch (e) {}
});

const pointsToEarn = computed(() => Math.floor(cartTotal.value * earnRate.value));

watch(() => form.phone, async (newPhone) => {
    if (newPhone && newPhone.length >= 9) {
        try {
            const res = await axios.post(route('loyalty.check'), { phone: newPhone });
            isLoyaltyEligible.value = res.data.eligible;
            if (res.data.earn_rate) earnRate.value = parseFloat(res.data.earn_rate);
        } catch (e) {
            isLoyaltyEligible.value = false;
        }
    } else {
        isLoyaltyEligible.value = false;
        showOtpInput.value = false;
    }
});

const handleSendOtp = async () => {
    if (!form.phone) return;
    isSendingOtp.value = true;
    loyaltyMessage.value = '';
    try {
        const res = await axios.post(route('loyalty.send-otp'), { phone: form.phone });
        showOtpInput.value = true;
        loyaltyMessage.value = res.data.message;
    } catch (e) {
        loyaltyMessage.value = e.response?.data?.message || 'Błąd wysyłki kodu SMS.';
    } finally {
        isSendingOtp.value = false;
    }
};

const handleVerifyOtp = async () => {
    if (!otpCode.value) return;
    isVerifyingOtp.value = true;
    loyaltyMessage.value = '';
    try {
        const res = await axios.post(route('loyalty.verify-otp'), { 
            phone: form.phone,
            code: otpCode.value 
        });
        loyaltyDiscountAmount.value = parseFloat(res.data.discount_amount);
        form.loyalty_discount = loyaltyDiscountAmount.value;
        loyaltyMessage.value = res.data.message;
        showOtpInput.value = false;
    } catch (e) {
        loyaltyMessage.value = e.response?.data?.message || 'Nieprawidłowy kod SMS.';
    } finally {
        isVerifyingOtp.value = false;
    }
};

// Kody Rabatowe
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
    const totalAfterDiscounts = subtotal.value - autoDiscount.value - discountValue.value - loyaltyDiscountAmount.value;
    return Math.max(0, totalAfterDiscounts);
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
            // Czyszczenie pamięci przeglądarki oraz powiadomienie nadrzędnego komponentu
            localStorage.removeItem('savona_cart');
            emit('clear-cart');

            // Reset pól formularza oraz dodatkowych wartości
            form.reset('delivery_address', 'discount_code', 'phone', 'loyalty_discount');
            appliedDiscount.value = null;
            discountCodeInput.value = '';
            loyaltyDiscountAmount.value = 0;
            isLoyaltyEligible.value = false;
            autoDiscount.value = 0;
            freeItemsFromPromo.value = [];
            currentStep.value = 1;

            alert('Super! Twoje zamówienie zostało przekazane do realizacji.');
        }
    });
};
</script>

<template>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sticky top-24 shadow-2xl space-y-4">
        
        <!-- NAGŁÓWEK KOSZYKA Z PASEM KROKÓW -->
        <div class="border-b border-slate-800 pb-3 space-y-2">
            <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider flex items-center justify-between">
                <span>Twoje Zamówienie</span>
                <ShoppingBag class="w-4 h-4 text-amber-500" />
            </h3>

            <div class="grid grid-cols-3 gap-1.5 pt-1">
                <button 
                    @click="goToStep(1)"
                    :class="currentStep === 1 ? 'bg-amber-500 text-slate-950 font-black' : (currentStep > 1 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-[#0B0F19] text-slate-500 border border-slate-800')"
                    class="py-1.5 rounded-lg text-[10px] uppercase font-bold transition flex items-center justify-center space-x-1 cursor-pointer"
                >
                    <span>1. Koszyk</span>
                </button>

                <button 
                    @click="goToStep(2)"
                    :disabled="cart.length === 0"
                    :class="currentStep === 2 ? 'bg-amber-500 text-slate-950 font-black' : (currentStep > 2 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-[#0B0F19] text-slate-500 border border-slate-800')"
                    class="py-1.5 rounded-lg text-[10px] uppercase font-bold transition flex items-center justify-center space-x-1 cursor-pointer disabled:opacity-40"
                >
                    <span>2. Dostawa</span>
                </button>

                <button 
                    @click="goToStep(3)"
                    :disabled="!form.phone || (form.type === 'dostawa' && !form.delivery_address)"
                    :class="currentStep === 3 ? 'bg-amber-500 text-slate-950 font-black' : 'bg-[#0B0F19] text-slate-500 border border-slate-800'"
                    class="py-1.5 rounded-lg text-[10px] uppercase font-bold transition flex items-center justify-center space-x-1 cursor-pointer disabled:opacity-40"
                >
                    <span>3. Płatność</span>
                </button>
            </div>
        </div>

        <!-- WIDOK KROKU 1 -->
        <CartStep1Cart 
            v-if="currentStep === 1"
            :cart="cart"
            :subtotal="subtotal"
            :free-delivery-settings="freeDeliverySettings"
            :free-delivery-remaining="freeDeliveryRemaining"
            :free-delivery-progress="freeDeliveryProgress"
            :auto-discount="autoDiscount"
            :free-items-from-promo="freeItemsFromPromo"
            :upsell-settings="upsellSettings"
            :upsell-products="upsellProducts"
            :points-to-earn="pointsToEarn"
            @remove-item="$emit('remove-item', $event)"
            @add-upsell="addUpsellItem"
            @go-to-step2="goToStep(2)"
        />

        <!-- WIDOK KROKU 2 -->
        <CartStep2Delivery 
            v-if="currentStep === 2"
            :form="form"
            :min-order-warning="minOrderWarning"
            @go-to-step1="goToStep(1)"
            @go-to-step3="goToStep(3)"
        />

        <!-- WIDOK KROKU 3 -->
        <CartStep3Payment 
            v-if="currentStep === 3"
            :form="form"
            :is-loyalty-eligible="isLoyaltyEligible"
            :loyalty-discount-amount="loyaltyDiscountAmount"
            :show-otp-input="showOtpInput"
            v-model:otp-code="otpCode"
            :is-sending-otp="isSendingOtp"
            :is-verifying-otp="isVerifyingOtp"
            :loyalty-message="loyaltyMessage"
            :applied-discount="appliedDiscount"
            v-model:discount-code-input="discountCodeInput"
            :is-validating-code="isValidatingCode"
            :points-to-earn="pointsToEarn"
            :cart-total="cartTotal"
            :min-order-warning="minOrderWarning"
            @send-otp="handleSendOtp"
            @verify-otp="handleVerifyOtp"
            @apply-discount="applyDiscountCode"
            @remove-discount="removeDiscountCode"
            @go-to-step2="goToStep(2)"
            @checkout="checkout"
        />

    </div>
</template>